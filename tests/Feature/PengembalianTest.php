<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengembalianTest extends TestCase
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

    private function pemilik(): User
    {
        return $this->user('pemilik', 'p@t.test');
    }

    /**
     * Skenario lengkap: penyewaan berjalan + pembayaran lunas.
     *
     * @return array{0: Penyewaan, 1: Motor, 2: Transaksi}
     */
    private function penyewaanBerjalan(string $status = Penyewaan::STATUS_BERJALAN, string $statusPembayaran = Transaksi::STATUS_DIBAYAR): array
    {
        $motor = Motor::create([
            'pemilik_id' => $this->pemilik()->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => 'B 1234 XY', 'status' => Motor::STATUS_DISEWA,
        ]);
        TarifRental::create([
            'motor_id' => $motor->id, 'tarif_harian' => 50000, 'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000,
        ]);

        $penyewaan = Penyewaan::create([
            'penyewa_id' => $this->penyewa()->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->subDays(3), 'tanggal_selesai' => now()->addDays(1),
            'tipe_durasi' => 'harian', 'harga' => 250000, 'status' => $status,
        ]);

        $transaksi = Transaksi::create([
            'penyewaan_id' => $penyewaan->id, 'jumlah' => 250000, 'metode_pembayaran' => 'transfer',
            'status' => $statusPembayaran, 'tanggal' => now()->subDays(3)->toDateString(),
        ]);

        return [$penyewaan, $motor, $transaksi];
    }

    // ---------- PENGAJUAN OLEH PENYEWA ----------

    public function test_penyewa_mengajukan_pengembalian(): void
    {
        [$p, , $t] = $this->penyewaanBerjalan();

        $this->actingAs($this->penyewa())
            ->patch("/penyewa/penyewaan/{$p->id}/kembalikan")
            ->assertRedirect(route('penyewa.penyewaan.show', $p));

        // Penanda pengajuan tersimpan, status penyewaan belum berubah.
        $this->assertSame(Transaksi::STATUS_DIKEMBALIKAN, $t->fresh()->status);
        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
        // Nominal & metode tidak boleh berubah.
        $this->assertEquals(250000, (float) $t->fresh()->jumlah);
        $this->assertSame('transfer', $t->fresh()->metode_pembayaran);
    }

    public function test_tidak_bisa_mengajukan_dua_kali(): void
    {
        [$p] = $this->penyewaanBerjalan();

        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");
        $this->actingAs($this->penyewa())
            ->patch("/penyewa/penyewaan/{$p->id}/kembalikan")
            ->assertSessionHas('error');
    }

    public function test_pengajuan_ditolak_bila_penyewaan_belum_berjalan(): void
    {
        foreach ([Penyewaan::STATUS_PENDING, Penyewaan::STATUS_DIKONFIRMASI] as $i => $status) {
            [$p] = $this->penyewaanBerjalan($status);
            $p->motor->update(['no_plat' => "B 90{$i}0 ZZ"]);

            $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");
            $this->assertSame($status, $p->fresh()->status);
        }
    }

    public function test_pengajuan_ditolak_bila_belum_lunas(): void
    {
        [$p] = $this->penyewaanBerjalan(Penyewaan::STATUS_BERJALAN, Transaksi::STATUS_PENDING);

        $this->actingAs($this->penyewa())
            ->patch("/penyewa/penyewaan/{$p->id}/kembalikan")
            ->assertSessionHas('error');

        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
    }

    public function test_penyewa_lain_tidak_bisa_mengajukan(): void
    {
        [$p] = $this->penyewaanBerjalan();

        $this->actingAs($this->penyewa('lain@t.test'))
            ->patch("/penyewa/penyewaan/{$p->id}/kembalikan")
            ->assertForbidden();

        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
    }

    // ---------- KONFIRMASI ADMIN ----------

    public function test_admin_konfirmasi_penyewaan_selesai_dan_motor_tersedia(): void
    {
        [$p, $motor] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        $this->actingAs($this->admin())
            ->patch("/admin/pengembalian/{$p->id}/konfirmasi")
            ->assertRedirect();

        $this->assertSame(Penyewaan::STATUS_SELESAI, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    public function test_konfirmasi_ditolak_bila_penyewa_belum_mengajukan(): void
    {
        [$p, $motor] = $this->penyewaanBerjalan();

        $this->actingAs($this->admin())
            ->patch("/admin/pengembalian/{$p->id}/konfirmasi")
            ->assertSessionHas('error');

        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_konfirmasi_ditolak_bila_penyewaan_bukan_berjalan(): void
    {
        [$p, $motor] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        // Penyewaan sudah selesai -> konfirmasi kedua harus gagal.
        $this->actingAs($this->admin())->patch("/admin/pengembalian/{$p->id}/konfirmasi");
        $this->actingAs($this->admin())
            ->patch("/admin/pengembalian/{$p->id}/konfirmasi")
            ->assertSessionHas('error');

        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    public function test_motor_tetap_tersedia_bila_disewa_oleh_penyewaan_lain(): void
    {
        [$p, $motor] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        // Kondisi tepi: motor sudah tidak berstatus disewa (mis. dipindah ke perawatan).
        $motor->update(['status' => Motor::STATUS_PERAWATAN]);

        $this->actingAs($this->admin())->patch("/admin/pengembalian/{$p->id}/konfirmasi");

        $this->assertSame(Penyewaan::STATUS_SELESAI, $p->fresh()->status);
        // Tidak dipaksa jadi tersedia — menghormati status motor yang sudah diubah admin.
        $this->assertSame(Motor::STATUS_PERAWATAN, $motor->fresh()->status);
    }

    // ---------- AUTHORIZATION ----------

    public function test_pemilik_tidak_bisa_mengajukan_atau_mengonfirmasi(): void
    {
        [$p] = $this->penyewaanBerjalan();
        $pemilik = $this->pemilik();

        $this->actingAs($pemilik)->patch("/penyewa/penyewaan/{$p->id}/kembalikan")->assertRedirect();
        $this->actingAs($pemilik)->patch("/admin/pengembalian/{$p->id}/konfirmasi")->assertRedirect();

        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
    }

    public function test_penyewa_tidak_bisa_mengonfirmasi_pengembalian(): void
    {
        [$p, $motor] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        $this->actingAs($this->penyewa())
            ->patch("/admin/pengembalian/{$p->id}/konfirmasi")
            ->assertRedirect();

        $this->assertSame(Penyewaan::STATUS_BERJALAN, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_admin_melihat_halaman_pengembalian(): void
    {
        [$p] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        $this->actingAs($this->admin())
            ->get('/admin/pengembalian')
            ->assertOk()
            ->assertSee('B 1234 XY')
            ->assertSee('Konfirmasi');
    }

    public function test_halaman_pengembalian_hanya_menampilkan_yang_diajukan(): void
    {
        // Penyewaan berjalan TANPA pengajuan tidak boleh muncul di daftar menunggu.
        [$p] = $this->penyewaanBerjalan();

        $this->actingAs($this->admin())
            ->get('/admin/pengembalian')
            ->assertOk()
            ->assertDontSee('B 1234 XY');
    }

    // ---------- UI PENYEWA ----------

    public function test_tombol_ajukan_pengembalian_tampil_saat_berjalan(): void
    {
        [$p] = $this->penyewaanBerjalan();

        $this->actingAs($this->penyewa())
            ->get("/penyewa/penyewaan/{$p->id}")
            ->assertOk()
            ->assertSee('Ajukan Pengembalian');
    }

    public function test_tombol_tidak_tampil_setelah_diajukan(): void
    {
        [$p] = $this->penyewaanBerjalan();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/kembalikan");

        $this->actingAs($this->penyewa())
            ->get("/penyewa/penyewaan/{$p->id}")
            ->assertOk()
            ->assertDontSee('Ajukan Pengembalian')
            ->assertSee('menunggu pemeriksaan admin');
    }
}
