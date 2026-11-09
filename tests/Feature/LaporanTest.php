<?php

namespace Tests\Feature;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['nama' => ucfirst($role), 'no_tlpn' => '0812', 'password' => 'password', 'role' => $role]
        );
    }

    private function admin(): User
    {
        return $this->user('admin', 'a@t.test');
    }

    private function pemilik(string $email = 'p@t.test', string $nama = 'Pemilik'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['nama' => $nama, 'no_tlpn' => '0812', 'password' => 'password', 'role' => 'pemilik']
        );
    }

    private function penyewa(string $email = 's@t.test', string $nama = 'Penyewa'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['nama' => $nama, 'no_tlpn' => '0812', 'password' => 'password', 'role' => 'penyewa']
        );
    }

    private function motor(User $pemilik, string $plat, string $status = Motor::STATUS_TERSEDIA): Motor
    {
        $motor = Motor::create([
            'pemilik_id' => $pemilik->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => $plat, 'status' => $status,
        ]);
        TarifRental::create([
            'motor_id' => $motor->id, 'tarif_harian' => 50000, 'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000,
        ]);

        return $motor;
    }

    private function dataLengkap(float $harga = 250000): void
    {
        $motor = $this->motor($this->pemilik(), 'B 1234 XY', Motor::STATUS_TERSEDIA);

        $p = Penyewaan::create([
            'penyewa_id' => $this->penyewa()->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->startOfMonth()->addDay(), 'tanggal_selesai' => now()->startOfMonth()->addDays(5),
            'tipe_durasi' => 'harian', 'harga' => $harga, 'status' => Penyewaan::STATUS_SELESAI,
        ]);

        Transaksi::create([
            'penyewaan_id' => $p->id, 'jumlah' => $harga, 'metode_pembayaran' => 'transfer',
            'status' => Transaksi::STATUS_DIBAYAR, 'tanggal' => now()->toDateString(),
        ]);

        BagiHasil::create([
            'penyewaan_id' => $p->id,
            'persentase_hasil_pemilik' => 80, 'persentase_hasil_admin' => 20,
            'nominal_pemilik' => $harga * 0.8, 'nominal_admin' => $harga * 0.2,
            'settled_at' => now(), 'tanggal' => now()->toDateString(),
        ]);
    }

    // ---------- ADMIN ----------

    public function test_admin_melihat_laporan_dengan_angka_dari_database(): void
    {
        $this->dataLengkap(250000);

        $this->actingAs($this->admin())
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Laporan Admin')
            ->assertSee('250.000')   // total pendapatan
            ->assertSee('200.000')   // bagian pemilik
            ->assertSee('50.000');   // bagian admin
    }

    public function test_angka_laporan_berubah_sesuai_data(): void
    {
        // Harga berbeda -> angka laporan harus ikut, bukan hardcode.
        $this->dataLengkap(400000);

        $this->actingAs($this->admin())
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('400.000')
            ->assertSee('320.000')
            ->assertSee('80.000');
    }

    public function test_admin_melihat_grafik_penyewaan_harian_mingguan_bulanan(): void
    {
        $this->dataLengkap();

        $this->actingAs($this->admin())
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Grafik Penyewaan')
            ->assertSee('Harian')
            ->assertSee('Mingguan')
            ->assertSee('Bulanan');
    }

    public function test_laporan_admin_kosong_tidak_error(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/laporan')
            ->assertOk()
            ->assertSee('Tidak ada penyewaan pada periode ini');
    }

    public function test_filter_rentang_tanggal_bekerja(): void
    {
        $this->dataLengkap();

        // Periode di masa depan yang tidak ada datanya.
        $this->actingAs($this->admin())
            ->get('/admin/laporan?dari=2030-01-01&sampai=2030-01-31')
            ->assertOk()
            ->assertSee('Tidak ada penyewaan pada periode ini');
    }

    public function test_rentang_terbalik_dinormalkan(): void
    {
        $service = app(LaporanService::class);
        [$dari, $sampai] = $service->rentang('2025-12-31', '2025-01-01');

        $this->assertTrue($dari->lessThanOrEqualTo($sampai));
    }

    // ---------- PEMILIK ----------

    public function test_pemilik_melihat_pendapatan_motornya(): void
    {
        $this->dataLengkap(250000);

        $this->actingAs($this->pemilik())
            ->get('/pemilik/laporan')
            ->assertOk()
            ->assertSee('Laporan Pendapatan Saya')
            ->assertSee('B 1234 XY')
            ->assertSee('200.000'); // bagian pemilik
    }

    public function test_pemilik_tidak_melihat_data_pemilik_lain(): void
    {
        $pemilikLain = $this->pemilik('lain@t.test', 'Pemilik Lain');
        $motorLain = $this->motor($pemilikLain, 'B 9999 ZZ');
        $penyewa = $this->penyewa();

        $p = Penyewaan::create([
            'penyewa_id' => $penyewa->id, 'motor_id' => $motorLain->id,
            'tanggal_mulai' => now(), 'tanggal_selesai' => now()->addDays(3),
            'tipe_durasi' => 'harian', 'harga' => 999000, 'status' => Penyewaan::STATUS_SELESAI,
        ]);
        Transaksi::create([
            'penyewaan_id' => $p->id, 'jumlah' => 999000, 'metode_pembayaran' => 'cash',
            'status' => Transaksi::STATUS_DIBAYAR, 'tanggal' => now()->toDateString(),
        ]);

        $this->actingAs($this->pemilik())
            ->get('/pemilik/laporan')
            ->assertOk()
            ->assertDontSee('B 9999 ZZ')
            ->assertDontSee('999.000');
    }

    public function test_pemilik_tidak_bisa_akses_laporan_admin(): void
    {
        $this->actingAs($this->pemilik())->get('/admin/laporan')->assertRedirect();
    }

    // ---------- PENYEWA ----------

    public function test_penyewa_melihat_riwayat_sendiri(): void
    {
        $this->dataLengkap(250000);

        $this->actingAs($this->penyewa())
            ->get('/penyewa/laporan')
            ->assertOk()
            ->assertSee('Riwayat Penyewaan Saya')
            ->assertSee('B 1234 XY')
            ->assertSee('250.000');
    }

    public function test_penyewa_tidak_melihat_penyewaan_penyewa_lain(): void
    {
        $lain = $this->penyewa('lain@t.test', 'Orang Lain');
        $motor = $this->motor($this->pemilik(), 'B 7777 YY');

        $p = Penyewaan::create([
            'penyewa_id' => $lain->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now(), 'tanggal_selesai' => now()->addDays(3),
            'tipe_durasi' => 'harian', 'harga' => 777000, 'status' => Penyewaan::STATUS_SELESAI,
        ]);
        Transaksi::create([
            'penyewaan_id' => $p->id, 'jumlah' => 777000, 'metode_pembayaran' => 'cash',
            'status' => Transaksi::STATUS_DIBAYAR, 'tanggal' => now()->toDateString(),
        ]);

        $this->actingAs($this->penyewa())
            ->get('/penyewa/laporan')
            ->assertOk()
            ->assertDontSee('B 7777 YY')
            ->assertDontSee('777.000');
    }

    public function test_penyewa_tidak_bisa_akses_laporan_admin_atau_pemilik(): void
    {
        $penyewa = $this->penyewa();

        $this->actingAs($penyewa)->get('/admin/laporan')->assertRedirect();
        $this->actingAs($penyewa)->get('/pemilik/laporan')->assertRedirect();
    }

    // ---------- SERVICE ----------

    public function test_total_pendapatan_hanya_menghitung_lunas(): void
    {
        $motor = $this->motor($this->pemilik(), 'B 5555 AA');
        $penyewa = $this->penyewa();

        foreach ([Transaksi::STATUS_DIBAYAR, Transaksi::STATUS_DIKEMBALIKAN, Transaksi::STATUS_PENDING, Transaksi::STATUS_DITOLAK] as $i => $status) {
            $p = Penyewaan::create([
                'penyewa_id' => $penyewa->id, 'motor_id' => $motor->id,
                'tanggal_mulai' => now(), 'tanggal_selesai' => now()->addDays(2),
                'tipe_durasi' => 'harian', 'harga' => 100000 * ($i + 1), 'status' => Penyewaan::STATUS_SELESAI,
            ]);
            Transaksi::create([
                'penyewaan_id' => $p->id, 'jumlah' => 100000 * ($i + 1),
                'metode_pembayaran' => 'cash', 'status' => $status, 'tanggal' => now()->toDateString(),
            ]);
        }

        $laporan = app(LaporanService::class)->admin(now()->startOfMonth(), now()->endOfDay());

        // Hanya dibayar (100.000) + dikembalikan (200.000) = 300.000
        $this->assertEquals(300000, $laporan['totalPendapatan']);
    }

    public function test_grafik_menghitung_jumlah_penyewaan(): void
    {
        $this->dataLengkap();

        $service = app(LaporanService::class);
        $grafik = $service->grafikPenyewaan(now()->startOfMonth(), now()->endOfDay(), 'bulan');

        $this->assertNotEmpty($grafik['label']);
        $this->assertSame(1, array_sum($grafik['nilai']));
    }
}
