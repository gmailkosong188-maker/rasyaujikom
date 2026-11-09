<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenyewaanTest extends TestCase
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

    private function motorDenganTarif(
        ?User $pemilik = null,
        string $plat = 'B 1234 XY',
        string $status = Motor::STATUS_TERSEDIA,
        array $tarif = ['tarif_harian' => 50000, 'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000]
    ): Motor {
        $motor = Motor::create([
            'pemilik_id' => ($pemilik ?? $this->pemilik())->id,
            'merk' => 'Beat', 'tipe_cc' => '125', 'no_plat' => $plat, 'status' => $status,
        ]);

        TarifRental::create(['motor_id' => $motor->id] + $tarif);

        return $motor->fresh();
    }

    private function payload(Motor $motor, array $over = []): array
    {
        return array_merge([
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(5)->toDateString(),
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
        ], $over);
    }

    // ---------- PERHITUNGAN HARGA ----------

    public function test_harga_harian_dihitung_server_side(): void
    {
        $motor = $this->motorDenganTarif();
        $mulai = now()->addDay();
        $selesai = $mulai->copy()->addDays(4); // 5 hari inklusif

        $this->actingAs($this->penyewa())
            ->post('/penyewa/penyewaan', $this->payload($motor, [
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            ]))->assertRedirect();

        $p = Penyewaan::firstOrFail();
        $this->assertEquals(250000, (float) $p->harga); // 5 x 50.000
    }

    public function test_harga_mengabaikan_input_harga_dari_user(): void
    {
        $motor = $this->motorDenganTarif();

        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor, [
            'harga' => 1, // user mencoba menipu harga
        ]))->assertRedirect();

        $p = Penyewaan::firstOrFail();
        $this->assertNotEquals(1, (float) $p->harga);
        $this->assertGreaterThan(0, (float) $p->harga);
    }

    public function test_harga_mingguan_memakai_tarif_mingguan(): void
    {
        $motor = $this->motorDenganTarif();
        $mulai = now()->addDay();

        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor, [
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $mulai->copy()->addDays(6)->toDateString(), // 7 hari
            'tipe_durasi' => Penyewaan::TIPE_MINGGUAN,
        ]))->assertRedirect();

        $this->assertEquals(300000, (float) Penyewaan::firstOrFail()->harga);
    }

    public function test_harga_bulanan_61_hari_sesuai_aturan_tahap_7(): void
    {
        $motor = $this->motorDenganTarif();
        $mulai = \Carbon\Carbon::parse('2025-03-01');

        // hitungHarga sudah teruji di Tahap 7: 61 hari = 3 bulan -> 3 x 1.000.000
        $this->assertEquals(
            3000000,
            Penyewaan::hitungHarga($motor->tarifRental, Penyewaan::TIPE_BULANAN, $mulai, \Carbon\Carbon::parse('2025-04-30'))
        );
    }

    // ---------- VALIDASI ----------

    public function test_tanggal_selesai_sebelum_mulai_ditolak(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor, [
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('tanggal_selesai');

        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_tipe_durasi_tidak_valid_ditolak(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor, [
            'tipe_durasi' => 'tahunan',
        ]))->assertSessionHasErrors('tipe_durasi');
    }

    public function test_motor_tidak_tersedia_ditolak(): void
    {
        foreach ([Motor::STATUS_PENDING, Motor::STATUS_DISEWA, Motor::STATUS_PERAWATAN, Motor::STATUS_DITOLAK] as $i => $status) {
            $motor = $this->motorDenganTarif(null, 'B 900'.$i.' ZZ', $status);
            $this->actingAs($this->penyewa('s'.$i.'@t.test'))
                ->post('/penyewa/penyewaan', $this->payload($motor))
                ->assertSessionHasErrors('motor_id');
        }

        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_motor_tanpa_tarif_ditolak(): void
    {
        $motor = Motor::create([
            'pemilik_id' => $this->pemilik()->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => 'B 5555 NT', 'status' => Motor::STATUS_TERSEDIA,
        ]);

        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor))
            ->assertSessionHasErrors('motor_id');
        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_tanggal_mulai_masa_lalu_ditolak(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor, [
            'tanggal_mulai' => now()->subDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors('tanggal_mulai');
    }

    public function test_penyewaan_bentrok_ditolak(): void
    {
        $motor = $this->motorDenganTarif();
        $penyewa = $this->penyewa();

        $this->actingAs($penyewa)->post('/penyewa/penyewaan', $this->payload($motor))->assertRedirect();
        $this->assertDatabaseCount('penyewaans', 1);

        // Periode beririsan
        $this->actingAs($this->penyewa('s2@t.test'))->post('/penyewa/penyewaan', $this->payload($motor, [
            'tanggal_mulai' => now()->addDays(2)->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
        ]))->assertSessionHasErrors('motor_id');

        $this->assertDatabaseCount('penyewaans', 1);
    }

    public function test_penyewaan_tidak_bentrok_diizinkan(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor))->assertRedirect();

        // Periode setelah penyewaan pertama selesai
        $this->actingAs($this->penyewa('s2@t.test'))->post('/penyewa/penyewaan', $this->payload($motor, [
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
        ]))->assertRedirect();

        $this->assertDatabaseCount('penyewaans', 2);
    }

    // ---------- AUTHORIZATION ----------

    public function test_penyewa_hanya_melihat_penyewaannya_sendiri(): void
    {
        $motor = $this->motorDenganTarif();
        $p1 = $this->penyewa('x1@t.test');
        $p2 = $this->penyewa('x2@t.test');

        Penyewaan::create([
            'penyewa_id' => $p1->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDay(), 'tanggal_selesai' => now()->addDays(3),
            'tipe_durasi' => 'harian', 'harga' => 150000, 'status' => Penyewaan::STATUS_PENDING,
        ]);
        $milikP2 = Penyewaan::create([
            'penyewa_id' => $p2->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDays(20), 'tanggal_selesai' => now()->addDays(22),
            'tipe_durasi' => 'harian', 'harga' => 150000, 'status' => Penyewaan::STATUS_PENDING,
        ]);

        $this->actingAs($p1)->get('/penyewa/penyewaan')->assertOk();
        // Buka detail milik penyewa lain -> 403
        $this->actingAs($p1)->get("/penyewa/penyewaan/{$milikP2->id}")->assertForbidden();
    }

    public function test_penyewa_tidak_bisa_membatalkan_penyewaan_orang_lain(): void
    {
        $motor = $this->motorDenganTarif();
        $p2 = $this->penyewa('x2@t.test');
        $milikP2 = Penyewaan::create([
            'penyewa_id' => $p2->id, 'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDays(20), 'tanggal_selesai' => now()->addDays(22),
            'tipe_durasi' => 'harian', 'harga' => 150000, 'status' => Penyewaan::STATUS_PENDING,
        ]);

        $this->actingAs($this->penyewa('x1@t.test'))
            ->patch("/penyewa/penyewaan/{$milikP2->id}/batalkan")
            ->assertForbidden();

        $this->assertSame(Penyewaan::STATUS_PENDING, $milikP2->fresh()->status);
    }

    public function test_pemilik_tidak_bisa_membuat_penyewaan(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->pemilik())->post('/penyewa/penyewaan', $this->payload($motor))->assertRedirect();
        $this->assertDatabaseCount('penyewaans', 0);
    }

    public function test_admin_bisa_melihat_semua_penyewaan(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));

        $this->actingAs($this->admin())->get('/admin/penyewaan')->assertOk();
    }

    public function test_pemilik_hanya_melihat_penyewaan_motornya(): void
    {
        $pemilikLain = $this->pemilik('lain@t.test');
        $motor = $this->motorDenganTarif();
        $milikLain = $this->motorDenganTarif($pemilikLain, 'B 7777 LL');

        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $this->actingAs($this->penyewa('s2@t.test'))->post('/penyewa/penyewaan', $this->payload($milikLain));

        $this->actingAs($this->pemilik())->get('/pemilik/penyewaan')->assertOk()->assertSee('B 1234 XY');
        $this->assertDatabaseCount('penyewaans', 2);
    }

    // ---------- STATUS & SINKRONISASI MOTOR ----------

    public function test_admin_konfirmasi_membuat_motor_disewa(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $p = Penyewaan::firstOrFail();

        $this->actingAs($this->admin())
            ->patch("/admin/penyewaan/{$p->id}/status", ['status' => 'dikonfirmasi'])
            ->assertRedirect();

        $this->assertSame(Penyewaan::STATUS_DIKONFIRMASI, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_penyewaan_selesai_mengembalikan_motor_tersedia(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $p = Penyewaan::firstOrFail();

        $admin = $this->admin();
        $this->actingAs($admin)->patch("/admin/penyewaan/{$p->id}/status", ['status' => 'dikonfirmasi']);
        $this->actingAs($admin)->patch("/admin/penyewaan/{$p->id}/status", ['status' => 'berjalan']);
        $this->actingAs($admin)->patch("/admin/penyewaan/{$p->id}/status", ['status' => 'selesai']);

        $this->assertSame(Penyewaan::STATUS_SELESAI, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    public function test_transisi_status_tidak_sah_ditolak(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $p = Penyewaan::firstOrFail();

        // pending -> selesai tidak diizinkan
        $this->actingAs($this->admin())
            ->patch("/admin/penyewaan/{$p->id}/status", ['status' => 'selesai'])
            ->assertSessionHas('error');

        $this->assertSame(Penyewaan::STATUS_PENDING, $p->fresh()->status);
    }

    public function test_penyewa_bisa_membatalkan_penyewaannya_sendiri(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $p = Penyewaan::firstOrFail();

        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/batalkan")->assertRedirect();
        $this->assertSame(Penyewaan::STATUS_DIBATALKAN, $p->fresh()->status);
    }

    public function test_dibatalkan_membebaskan_jadwal_motor(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())->post('/penyewa/penyewaan', $this->payload($motor));
        $p = Penyewaan::firstOrFail();
        $this->actingAs($this->penyewa())->patch("/penyewa/penyewaan/{$p->id}/batalkan");

        // Periode sama kini boleh dipesan penyewa lain
        $this->actingAs($this->penyewa('s2@t.test'))
            ->post('/penyewa/penyewaan', $this->payload($motor))
            ->assertRedirect();

        $this->assertSame(2, Penyewaan::count());
    }

    // ---------- UI ----------

    public function test_halaman_form_penyewaan_tampil(): void
    {
        $motor = $this->motorDenganTarif();
        $this->actingAs($this->penyewa())
            ->get("/penyewa/penyewaan/create?motor_id={$motor->id}")
            ->assertOk()->assertSee($motor->no_plat);
    }

    public function test_form_penyewaan_motor_tanpa_tarif_dialihkan(): void
    {
        $motor = Motor::create([
            'pemilik_id' => $this->pemilik()->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => 'B 6666 NT', 'status' => Motor::STATUS_TERSEDIA,
        ]);

        $this->actingAs($this->penyewa())
            ->get("/penyewa/penyewaan/create?motor_id={$motor->id}")
            ->assertRedirect(route('penyewa.motor.index'));
    }
}
