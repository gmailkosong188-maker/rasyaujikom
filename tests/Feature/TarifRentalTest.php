<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifRentalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'a@t.test'],
            ['nama' => 'Admin', 'no_tlpn' => '0811', 'password' => 'password', 'role' => 'admin']
        );
    }

    private function pemilik(string $email = 'p@t.test'): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['nama' => 'Pemilik', 'no_tlpn' => '0812', 'password' => 'password', 'role' => 'pemilik']
        );
    }

    private function penyewa(): User
    {
        return User::firstOrCreate(
            ['email' => 's@t.test'],
            ['nama' => 'Sewa', 'no_tlpn' => '0813', 'password' => 'password', 'role' => 'penyewa']
        );
    }

    private function motor(User $pemilik, string $plat = 'B 1111 AA', string $status = Motor::STATUS_TERSEDIA): Motor
    {
        return Motor::create([
            'pemilik_id' => $pemilik->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => $plat, 'status' => $status,
        ]);
    }

    private function payload(Motor $motor, array $over = []): array
    {
        return array_merge([
            'motor_id' => $motor->id,
            'tarif_harian' => 50000,
            'tarif_mingguan' => 300000,
            'tarif_bulanan' => 1000000,
        ], $over);
    }

    // ---------- ADMIN CRUD ----------

    public function test_admin_bisa_lihat_daftar_tarif(): void
    {
        $motor = $this->motor($this->pemilik());
        TarifRental::create($this->payload($motor));

        $this->actingAs($this->admin())->get('/admin/tarif')
            ->assertOk()->assertSee('B 1111 AA');
    }

    public function test_admin_bisa_tambah_tarif(): void
    {
        $motor = $this->motor($this->pemilik());

        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($motor))->assertRedirect();

        $t = TarifRental::firstOrFail();
        $this->assertSame($motor->id, $t->motor_id);
        $this->assertEquals(50000, $t->tarif_harian);
        $this->assertEquals(300000, $t->tarif_mingguan);
        $this->assertEquals(1000000, $t->tarif_bulanan);
    }

    public function test_admin_bisa_ubah_tarif(): void
    {
        $motor = $this->motor($this->pemilik());
        $t = TarifRental::create($this->payload($motor));

        $this->actingAs($this->admin())->put("/admin/tarif/{$t->id}", [
            'tarif_harian' => 60000, 'tarif_mingguan' => 360000, 'tarif_bulanan' => 1200000,
        ])->assertRedirect();

        $t->refresh();
        $this->assertEquals(60000, $t->tarif_harian);
    }

    public function test_satu_motor_tidak_boleh_punya_dua_tarif(): void
    {
        $motor = $this->motor($this->pemilik());
        TarifRental::create($this->payload($motor));

        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($motor))
            ->assertSessionHasErrors('motor_id');

        $this->assertDatabaseCount('tarif_rentals', 1);
    }

    public function test_motor_id_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin())->post('/admin/tarif', [
            'motor_id' => 99999, 'tarif_harian' => 50000,
            'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000,
        ])->assertSessionHasErrors('motor_id');
    }

    public function test_tarif_nol_atau_negatif_ditolak(): void
    {
        $p = $this->pemilik();

        // Tiap percobaan memakai motor berbeda agar yang diuji murni
        // validasi angka, bukan unique motor_id.
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($this->motor($p, 'B 1001 NA'), ['tarif_harian' => 0]))
            ->assertSessionHasErrors('tarif_harian');
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($this->motor($p, 'B 1002 NA'), ['tarif_mingguan' => -1]))
            ->assertSessionHasErrors('tarif_mingguan');
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($this->motor($p, 'B 1003 NA'), ['tarif_bulanan' => 0]))
            ->assertSessionHasErrors('tarif_bulanan');

        $this->assertDatabaseCount('tarif_rentals', 0);
    }

    public function test_tarif_bukan_angka_ditolak(): void
    {
        $motor = $this->motor($this->pemilik());
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($motor, ['tarif_harian' => 'abc']))
            ->assertSessionHasErrors('tarif_harian');
    }

    public function test_tarif_mingguan_lebih_kecil_dari_harian_ditolak(): void
    {
        $motor = $this->motor($this->pemilik());
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($motor, [
            'tarif_harian' => 100000, 'tarif_mingguan' => 50000, 'tarif_bulanan' => 200000,
        ]))->assertSessionHasErrors('tarif_mingguan');
    }

    public function test_motor_pending_belum_boleh_diberi_tarif(): void
    {
        $motor = $this->motor($this->pemilik(), 'B 2222 BB', Motor::STATUS_PENDING);
        $this->actingAs($this->admin())->post('/admin/tarif', $this->payload($motor))
            ->assertSessionHasErrors('motor_id');
        $this->assertDatabaseCount('tarif_rentals', 0);
    }

    public function test_admin_bisa_lihat_detail_tarif(): void
    {
        $motor = $this->motor($this->pemilik());
        $t = TarifRental::create($this->payload($motor));
        $this->actingAs($this->admin())->get("/admin/tarif/{$t->id}")->assertOk();
    }

    // ---------- AUTHORIZATION ----------

    public function test_pemilik_tidak_bisa_tambah_tarif(): void
    {
        $p = $this->pemilik();
        $motor = $this->motor($p);

        $this->actingAs($p)->post('/admin/tarif', $this->payload($motor))->assertRedirect();
        $this->assertDatabaseCount('tarif_rentals', 0);
    }

    public function test_pemilik_tidak_bisa_ubah_tarif(): void
    {
        $p = $this->pemilik();
        $t = TarifRental::create($this->payload($this->motor($p)));

        $this->actingAs($p)->put("/admin/tarif/{$t->id}", [
            'tarif_harian' => 1, 'tarif_mingguan' => 1, 'tarif_bulanan' => 1,
        ])->assertRedirect();

        $this->assertEquals(50000, $t->fresh()->tarif_harian);
    }

    public function test_penyewa_tidak_bisa_akses_admin_tarif(): void
    {
        $this->actingAs($this->penyewa())->get('/admin/tarif')->assertRedirect();
    }

    public function test_pemilik_hanya_lihat_tarif_motornya_sendiri(): void
    {
        $p1 = $this->pemilik('p1@t.test');
        $p2 = $this->pemilik('p2@t.test');
        $m1 = $this->motor($p1, 'B 1111 AA');
        $m2 = $this->motor($p2, 'B 2222 BB');
        TarifRental::create($this->payload($m1));
        $m2tarif = TarifRental::create([
            'motor_id' => $m2->id, 'tarif_harian' => 70000, 'tarif_mingguan' => 420000, 'tarif_bulanan' => 1400000,
        ]);

        $res = $this->actingAs($p1)->get('/pemilik/tarif');
        $res->assertOk()->assertSee('B 1111 AA')->assertDontSee('B 2222 BB');

        // Buka tarif milik orang lain -> 403
        $this->actingAs($p1)->get("/pemilik/tarif/{$m2tarif->id}")->assertForbidden();
    }

    public function test_penyewa_bisa_lihat_tarif_read_only(): void
    {
        TarifRental::create($this->payload($this->motor($this->pemilik())));
        $t = TarifRental::firstOrFail();

        $this->actingAs($this->penyewa())->get('/penyewa/tarif')->assertOk()->assertSee($t->motor->no_plat);
        $this->actingAs($this->penyewa())->get("/penyewa/tarif/{$t->id}")->assertOk();
    }

    // ---------- INTEGRASI DENGAN PERHITUNGAN HARGA ----------

    public function test_tarif_dipakai_untuk_menghitung_harga_penyewaan(): void
    {
        $motor = $this->motor($this->pemilik());
        $tarif = TarifRental::create($this->payload($motor));

        $mulai = \Carbon\Carbon::parse('2025-03-01');
        $selesai = \Carbon\Carbon::parse('2025-03-05'); // 5 hari inklusif

        // 5 hari harian = 5 x 50.000
        $this->assertEquals(250000, Penyewaan::hitungHarga($tarif, 'harian', $mulai, $selesai));
        // 5 hari dibulatkan ke atas menjadi 1 minggu = 1 x 300.000
        $this->assertEquals(300000, Penyewaan::hitungHarga($tarif, 'mingguan', $mulai, $selesai));
        // 61 hari = 3 bulan (dibulatkan ke atas) = 3 x 1.000.000
        $this->assertEquals(3000000, Penyewaan::hitungHarga($tarif, 'bulanan', $mulai, \Carbon\Carbon::parse('2025-04-30')));
    }

    public function test_tarif_ikut_berubah_setelah_diubah_admin(): void
    {
        $motor = $this->motor($this->pemilik());
        $t = TarifRental::create($this->payload($motor));

        $this->actingAs($this->admin())->put("/admin/tarif/{$t->id}", [
            'tarif_harian' => 80000, 'tarif_mingguan' => 480000, 'tarif_bulanan' => 1600000,
        ]);

        $mulai = \Carbon\Carbon::parse('2025-03-01');
        $this->assertEquals(80000, Penyewaan::hitungHarga($t->fresh(), 'harian', $mulai, $mulai));
    }
}
