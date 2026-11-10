<?php

namespace Tests\Feature;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BagiHasilService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BagiHasilTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['nama' => ucfirst($role), 'no_tlpn' => '0812', 'password' => 'password', 'role' => $role]
        );
    }

    private function penyewa(string $email = 's@t.test'): User
    {
        return $this->user('penyewa', $email);
    }

    private function admin(): User
    {
        return $this->user('admin', 'a@t.test');
    }

    private function pemilik(string $email = 'p@t.test'): User
    {
        return $this->user('pemilik', $email);
    }

    /**
     * @return array{0: Penyewaan, 1: Motor}
     */
    private function penyewaan(string $status = Penyewaan::STATUS_SELESAI, string $statusPembayaran = Transaksi::STATUS_DIBAYAR, float $harga = 250000): array
    {
        $motor = Motor::create([
            'pemilik_id' => $this->pemilik()->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => 'B 1234 XY', 'status' => Motor::STATUS_TERSEDIA,
        ]);
        TarifRental::create([
            'motor_id' => $motor->id, 'tarif_harian' => 50000, 'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000,
        ]);

        $penyewaan = Penyewaan::create([
            'penyewa_id' => $this->penyewa()->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->subDays(6), 'tanggal_selesai' => now()->subDay(),
            'tipe_durasi' => 'harian', 'harga' => $harga, 'status' => $status,
        ]);

        Transaksi::create([
            'penyewaan_id' => $penyewaan->id, 'jumlah' => $harga, 'metode_pembayaran' => 'transfer',
            'status' => $statusPembayaran, 'tanggal' => now()->subDays(6)->toDateString(),
        ]);

        return [$penyewaan, $motor];
    }

    // ---------- PERHITUNGAN ----------

    public function test_persentase_diambil_dari_config_bukan_hardcode(): void
    {
        config(['bagi_hasil.persentase_pemilik' => 70, 'bagi_hasil.persentase_admin' => 30]);

        $hasil = app(BagiHasilService::class)->hitung(100000);

        $this->assertSame(70, $hasil['persentase_hasil_pemilik']);
        $this->assertSame(30, $hasil['persentase_hasil_admin']);
        $this->assertEquals(70000, $hasil['nominal_pemilik']);
        $this->assertEquals(30000, $hasil['nominal_admin']);
    }

    public function test_nominal_pemilik_dan_admin_sesuai_80_20(): void
    {
        $hasil = app(BagiHasilService::class)->hitung(250000);

        $this->assertEquals(200000, $hasil['nominal_pemilik']); // 80%
        $this->assertEquals(50000, $hasil['nominal_admin']);    // 20%
        $this->assertEquals(250000, $hasil['nominal_pemilik'] + $hasil['nominal_admin']);
    }

    public function test_pembulatan_selalu_pas_ke_total(): void
    {
        $hasil = app(BagiHasilService::class)->hitung(333333.33);

        $this->assertEquals(
            333333.33,
            round($hasil['nominal_pemilik'] + $hasil['nominal_admin'], 2)
        );
    }

    public function test_persentase_total_bukan_100_ditolak(): void
    {
        config(['bagi_hasil.persentase_pemilik' => 80, 'bagi_hasil.persentase_admin' => 30]);

        $this->expectException(InvalidArgumentException::class);
        app(BagiHasilService::class)->hitung(100000);
    }

    // ---------- PENCATATAN ----------

    public function test_bagi_hasil_tercatat_saat_penyewaan_selesai(): void
    {
        [$p] = $this->penyewaan();

        $hasil = app(BagiHasilService::class)->catat($p);

        $this->assertSame($p->id, (int) $hasil->penyewaan_id);
        $this->assertEquals(200000, (float) $hasil->nominal_pemilik);
        $this->assertEquals(50000, (float) $hasil->nominal_admin);
        $this->assertSame(80, $hasil->persentase_hasil_pemilik);
        $this->assertSame(20, $hasil->persentase_hasil_admin);
        // Pencairan belum terjadi: catatan bagi hasil dibuat dengan status
        // MENUNGGU PENCAIRAN (settled_at NULL) sampai admin menyerahkan uang.
        $this->assertNull($hasil->settled_at);
        $this->assertNull($hasil->dicairkan_oleh);
        $this->assertTrue($hasil->isMenungguPencairan());
    }

    public function test_bagi_hasil_ganda_dicegah(): void
    {
        [$p] = $this->penyewaan();
        $service = app(BagiHasilService::class);
        $service->catat($p);

        try {
            $service->catat($p);
            $this->fail('Settlement ganda seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('sudah pernah dicatat', $e->getMessage());
        }

        $this->assertSame(1, BagiHasil::count());
    }

    public function test_penyewaan_belum_selesai_tidak_boleh_dicatat(): void
    {
        [$p] = $this->penyewaan(Penyewaan::STATUS_BERJALAN);

        $this->expectException(InvalidArgumentException::class);
        app(BagiHasilService::class)->catat($p);
    }

    public function test_penyewaan_selesai_tanpa_pembayaran_lunas_tidak_boleh_dicatat(): void
    {
        [$p] = $this->penyewaan(Penyewaan::STATUS_SELESAI, Transaksi::STATUS_PENDING);

        $this->expectException(InvalidArgumentException::class);
        app(BagiHasilService::class)->catat($p);
    }

    // ---------- INTEGRASI PENGEMBALIAN (TAHAP 10) ----------

    public function test_konfirmasi_pengembalian_otomatis_mencatat_bagi_hasil(): void
    {
        [$p, $motor] = $this->penyewaan(Penyewaan::STATUS_BERJALAN);
        $motor->update(['status' => Motor::STATUS_DISEWA]);

        // Penyewa ajukan, admin konfirmasi.
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");
        $this->actingAs($this->admin())->patch("/admin/pengembalian/{$p->id}/konfirmasi");

        $this->assertSame(Penyewaan::STATUS_SELESAI, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);

        $hasil = BagiHasil::where('penyewaan_id', $p->id)->first();
        $this->assertNotNull($hasil, 'Bagi hasil harus otomatis tercatat saat pengembalian dikonfirmasi.');
        $this->assertEquals(200000, (float) $hasil->nominal_pemilik);
        $this->assertEquals(50000, (float) $hasil->nominal_admin);
    }

    public function test_konfirmasi_pengembalian_ulang_tidak_menggandakan_bagi_hasil(): void
    {
        [$p, $motor] = $this->penyewaan(Penyewaan::STATUS_BERJALAN);
        $motor->update(['status' => Motor::STATUS_DISEWA]);

        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");
        $this->actingAs($this->admin())->patch("/admin/pengembalian/{$p->id}/konfirmasi");
        $this->actingAs($this->admin())->patch("/admin/pengembalian/{$p->id}/konfirmasi");

        $this->assertSame(1, BagiHasil::count());
    }

    // ---------- AUTHORIZATION ----------

    public function test_admin_melihat_halaman_bagi_hasil(): void
    {
        [$p] = $this->penyewaan();
        app(BagiHasilService::class)->catat($p);

        $this->actingAs($this->admin())
            ->get('/admin/bagi-hasil')
            ->assertOk()
            ->assertSee('B 1234 XY');

        $this->actingAs($this->admin())
            ->get('/admin/bagi-hasil/'.BagiHasil::first()->id)
            ->assertOk()
            ->assertSee('200.000');
    }

    public function test_admin_bisa_mencatat_bagi_hasil_lewat_tombol(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->admin())
            ->post("/admin/bagi-hasil/penyewaan/{$p->id}")
            ->assertRedirect();

        $this->assertSame(1, BagiHasil::count());
    }

    public function test_catat_ulang_lewat_tombol_ditolak(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->admin())->post("/admin/bagi-hasil/penyewaan/{$p->id}");

        $this->actingAs($this->admin())
            ->post("/admin/bagi-hasil/penyewaan/{$p->id}")
            ->assertSessionHas('error');

        $this->assertSame(1, BagiHasil::count());
    }

    public function test_penyewa_tidak_boleh_melihat_bagi_hasil(): void
    {
        [$p] = $this->penyewaan();
        app(BagiHasilService::class)->catat($p);

        $this->actingAs($this->penyewa())->get('/admin/bagi-hasil')->assertRedirect();
        $this->actingAs($this->penyewa())
            ->get('/admin/bagi-hasil/'.BagiHasil::firstOrFail()->id)
            ->assertRedirect();
    }

    public function test_penyewa_tidak_boleh_mencatat_bagi_hasil(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->penyewa())->post("/admin/bagi-hasil/penyewaan/{$p->id}")->assertRedirect();

        $this->assertSame(0, BagiHasil::count());
    }

    public function test_pemilik_tidak_boleh_mencatat_bagi_hasil(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->pemilik())->post("/admin/bagi-hasil/penyewaan/{$p->id}")->assertRedirect();

        $this->assertSame(0, BagiHasil::count());
    }

    public function test_halaman_menampilkan_penyewaan_selesai_belum_tercatat(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->admin())
            ->get('/admin/bagi-hasil')
            ->assertOk()
            ->assertSee('Catat Bagi Hasil');

        // Setelah dicatat, tidak lagi muncul di daftar belum tercatat.
        app(BagiHasilService::class)->catat($p);
        $this->actingAs($this->admin())
            ->get('/admin/bagi-hasil')
            ->assertOk()
            ->assertDontSee('Catat Bagi Hasil');
    }
}
