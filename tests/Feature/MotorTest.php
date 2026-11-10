<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MotorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function pemilik(string $email = 'p@t.test'): User
    {
        return User::create(['nama' => 'Pemilik', 'email' => $email, 'no_tlpn' => '0812', 'password' => 'password', 'role' => 'pemilik']);
    }

    private function admin(): User
    {
        return User::create(['nama' => 'Admin', 'email' => 'a@t.test', 'no_tlpn' => '0811', 'password' => 'password', 'role' => 'admin']);
    }

    private function penyewa(): User
    {
        return User::create(['nama' => 'Sewa', 'email' => 's@t.test', 'no_tlpn' => '0813', 'password' => 'password', 'role' => 'penyewa']);
    }

    private function motorUntuk(User $pemilik, string $plat = 'B 1111 AA', string $status = Motor::STATUS_PENDING): Motor
    {
        return Motor::create([
            'pemilik_id' => $pemilik->id, 'merk' => 'Honda Beat', 'tipe_cc' => '125',
            'no_plat' => $plat, 'status' => $status, 'photo' => 'f.jpg', 'dokumen_kepemilikan' => 'd.pdf',
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'merk' => 'Honda Beat', 'tipe_cc' => '125', 'no_plat' => 'B 9999 ZZ',
            'photo' => UploadedFile::fake()->image('f.jpg'),
            'dokumen_kepemilikan' => UploadedFile::fake()->create('d.pdf', 100, 'application/pdf'),
        ], $over);
    }

    // ---------- PEMILIK ----------

    public function test_pemilik_bisa_tambah_motor_dan_status_pending(): void
    {
        $p = $this->pemilik();
        $this->actingAs($p)->post('/pemilik/motor', $this->payload())->assertRedirect();
        $m = Motor::firstOrFail();
        $this->assertSame(Motor::STATUS_PENDING, $m->status);
        $this->assertSame($p->id, $m->pemilik_id);
        Storage::disk('public')->assertExists($m->photo);
        Storage::disk('public')->assertExists($m->dokumen_kepemilikan);
    }

    public function test_tambah_motor_tanpa_berkas_ditolak(): void
    {
        $p = $this->pemilik();
        $this->actingAs($p)->post('/pemilik/motor', [
            'merk' => 'Beat', 'tipe_cc' => '125', 'no_plat' => 'B 1 AA',
        ])->assertSessionHasErrors(['photo', 'dokumen_kepemilikan']);
        $this->assertDatabaseCount('motors', 0);
    }

    public function test_tipe_cc_tidak_valid_ditolak(): void
    {
        $p = $this->pemilik();
        $this->actingAs($p)->post('/pemilik/motor', $this->payload(['tipe_cc' => '200']))
            ->assertSessionHasErrors('tipe_cc');
        $this->assertDatabaseCount('motors', 0);
    }

    public function test_no_plat_duplikat_ditolak(): void
    {
        $p = $this->pemilik();
        $this->motorUntuk($p, 'B 1234 XY');
        $this->actingAs($p)->post('/pemilik/motor', $this->payload(['no_plat' => 'b 1234 xy']))
            ->assertSessionHasErrors('no_plat');
    }

    public function test_pemilik_tidak_bisa_akses_motor_milik_orang_lain(): void
    {
        $p1 = $this->pemilik('p1@t.test');
        $p2 = $this->pemilik('p2@t.test');
        $motor = $this->motorUntuk($p1);
        $this->actingAs($p2)->get("/pemilik/motor/{$motor->id}")->assertForbidden();
        $this->actingAs($p2)->get("/pemilik/motor/{$motor->id}/edit")->assertForbidden();
        $this->actingAs($p2)->put("/pemilik/motor/{$motor->id}", $this->payload())->assertForbidden();
        $this->actingAs($p2)->delete("/pemilik/motor/{$motor->id}")->assertForbidden();
    }

    public function test_pemilik_bisa_edit_motor(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 2222 BB');
        $this->actingAs($p)->put("/pemilik/motor/{$motor->id}", [
            'merk' => 'Honda Vario', 'tipe_cc' => '150', 'no_plat' => 'B 2222 BB',
        ])->assertRedirect();
        $motor->refresh();
        $this->assertSame('Honda Vario', $motor->merk);
        $this->assertSame('150', $motor->tipe_cc);
    }

    public function test_pemilik_list_hanya_motor_miliknya(): void
    {
        $p1 = $this->pemilik('p1@t.test');
        $p2 = $this->pemilik('p2@t.test');
        $this->motorUntuk($p1, 'B 1111 AA');
        $this->motorUntuk($p2, 'B 2222 BB');
        $res = $this->actingAs($p1)->get('/pemilik/motor');
        $res->assertOk();
        $res->assertSee('B 1111 AA');
        $res->assertDontSee('B 2222 BB');
    }

    public function test_pemilik_tidak_bisa_edit_motor_yang_sedang_disewa(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 3000 CC', Motor::STATUS_DISEWA);
        $this->actingAs($p)->get("/pemilik/motor/{$motor->id}/edit")->assertForbidden();
    }

    // ---------- ADMIN ----------

    public function test_admin_melihat_semua_motor(): void
    {
        $p1 = $this->pemilik('p1@t.test');
        $p2 = $this->pemilik('p2@t.test');
        $this->motorUntuk($p1, 'B 1111 AA');
        $this->motorUntuk($p2, 'B 2222 BB');
        $this->actingAs($this->admin())->get('/admin/motor')
            ->assertOk()->assertSee('B 1111 AA')->assertSee('B 2222 BB');
    }

    public function test_admin_terima_motor_pending_menjadi_tersedia(): void
    {
        $motor = $this->motorUntuk($this->pemilik());
        $this->actingAs($this->admin())->post("/admin/motor/{$motor->id}/terima")->assertRedirect();
        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    public function test_admin_tolak_motor_menjadi_ditolak(): void
    {
        $motor = $this->motorUntuk($this->pemilik());
        $this->actingAs($this->admin())->post("/admin/motor/{$motor->id}/tolak", ['alasan' => 'STNK tidak jelas'])
            ->assertRedirect();
        $this->assertSame(Motor::STATUS_DITOLAK, $motor->fresh()->status);
    }

    public function test_admin_ubah_status_sesuai_transisi(): void
    {
        $motor = $this->motorUntuk($this->pemilik(), 'B 1000 AA', Motor::STATUS_TERSEDIA);
        $this->actingAs($this->admin())->patch("/admin/motor/{$motor->id}/status", ['status' => 'perawatan'])
            ->assertRedirect();
        $this->assertSame(Motor::STATUS_PERAWATAN, $motor->fresh()->status);
    }

    public function test_transisi_status_tidak_sah_ditolak(): void
    {
        $motor = $this->motorUntuk($this->pemilik(), 'B 1001 AA', Motor::STATUS_DISEWA);
        $this->actingAs($this->admin())->patch("/admin/motor/{$motor->id}/status", ['status' => 'pending'])
            ->assertSessionHasErrors('status');
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $motor = $this->motorUntuk($this->pemilik(), 'B 1002 AA', Motor::STATUS_TERSEDIA);
        $this->actingAs($this->admin())->patch("/admin/motor/{$motor->id}/status", ['status' => 'SELESAI'])
            ->assertSessionHasErrors('status');
    }

    public function test_admin_bisa_lihat_dokumen(): void
    {
        $motor = $this->motorUntuk($this->pemilik());
        $this->actingAs($this->admin())->get("/admin/motor/{$motor->id}/dokumen")->assertOk();
    }

    // ---------- PENYEWA ----------

    public function test_penyewa_hanya_melihat_motor_tersedia(): void
    {
        $p = $this->pemilik();
        $this->motorUntuk($p, 'B 3333 CC', Motor::STATUS_TERSEDIA);
        $this->motorUntuk($p, 'B 4444 DD', Motor::STATUS_PENDING);
        $this->motorUntuk($p, 'B 5555 EE', Motor::STATUS_DISEWA);
        $this->motorUntuk($p, 'B 6666 FF', Motor::STATUS_DITOLAK);
        $this->motorUntuk($p, 'B 7777 GG', Motor::STATUS_PERAWATAN);

        $this->actingAs($this->penyewa())->get('/penyewa/motor')
            ->assertOk()->assertSee('B 3333 CC')
            ->assertDontSee('B 4444 DD')
            ->assertDontSee('B 5555 EE')
            ->assertDontSee('B 6666 FF')
            ->assertDontSee('B 7777 GG');
    }

    public function test_penyewa_tidak_bisa_buka_detail_motor_tidak_tersedia(): void
    {
        $motor = $this->motorUntuk($this->pemilik(), 'B 8888 HH', Motor::STATUS_PENDING);
        $this->actingAs($this->penyewa())->get("/penyewa/motor/{$motor->id}")->assertForbidden();
    }

    public function test_penyewa_bisa_buka_detail_motor_tersedia(): void
    {
        $motor = $this->motorUntuk($this->pemilik(), 'B 8889 HH', Motor::STATUS_TERSEDIA);
        $this->actingAs($this->penyewa())->get("/penyewa/motor/{$motor->id}")->assertOk();
    }

    public function test_penyewa_tidak_bisa_akses_route_pemilik_atau_admin(): void
    {
        $s = $this->penyewa();
        $this->actingAs($s)->get('/admin/motor')->assertRedirect();
        $this->actingAs($s)->get('/pemilik/motor')->assertRedirect();
    }

    public function test_pemilik_tidak_bisa_verifikasi_motor(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p);
        $this->actingAs($p)->post("/admin/motor/{$motor->id}/terima")->assertRedirect();
        $this->assertSame(Motor::STATUS_PENDING, $motor->fresh()->status);
    }

    // ---------- AKTIF / NONAKTIF ----------

    public function test_pemilik_bisa_nonaktifkan_motor(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 9001 AA', Motor::STATUS_TERSEDIA);

        $this->actingAs($p)
            ->post("/pemilik/motor/{$motor->id}/nonaktifkan")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($motor->fresh()->isAktif());
    }

    public function test_pemilik_bisa_aktifkan_kembali_motor(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 9002 AA', Motor::STATUS_TERSEDIA);
        $motor->update(['is_aktif' => false]);

        $this->actingAs($p)
            ->post("/pemilik/motor/{$motor->id}/aktifkan")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($motor->fresh()->isAktif());
    }

    public function test_pemilik_tidak_bisa_nonaktifkan_motor_yang_disewa(): void
    {
        $p = $this->pemilik();
        $penyewa = $this->penyewa();
        $motor = $this->motorUntuk($p, 'B 9003 AA', Motor::STATUS_TERSEDIA);

        // Buat penyewaan yang masih aktif (berjalan) untuk motor ini.
        Penyewaan::create([
            'penyewa_id' => $penyewa->id,
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(5)->toDateString(),
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
            'harga' => 250000,
            'status' => Penyewaan::STATUS_BERJALAN,
        ]);

        $this->actingAs($p)
            ->post("/pemilik/motor/{$motor->id}/nonaktifkan")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($motor->fresh()->isAktif());
    }

    public function test_penyewa_tidak_bisa_nonaktifkan_motor(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 9004 AA', Motor::STATUS_TERSEDIA);
        $penyewa = $this->penyewa();

        $this->actingAs($penyewa)
            ->post("/pemilik/motor/{$motor->id}/nonaktifkan")
            ->assertRedirect(); // route diproteksi role:pemilik -> dialihkan
    }

    public function test_admin_tidak_bisa_nonaktifkan_motor_hanya_pemilik(): void
    {
        $p = $this->pemilik();
        $motor = $this->motorUntuk($p, 'B 9005 AA', Motor::STATUS_TERSEDIA);

        $this->actingAs($this->admin())
            ->post("/pemilik/motor/{$motor->id}/nonaktifkan")
            ->assertRedirect(); // bukan route admin
    }

    public function test_motor_nonaktif_tidak_muncul_di_katalog_penyewa(): void
    {
        $p = $this->pemilik();
        $this->motorUntuk($p, 'B 9101 AA', Motor::STATUS_TERSEDIA);
        $nonaktif = $this->motorUntuk($p, 'B 9102 AA', Motor::STATUS_TERSEDIA);
        $nonaktif->update(['is_aktif' => false]);

        $this->actingAs($this->penyewa())->get('/penyewa/motor')
            ->assertOk()
            ->assertSee('B 9101 AA')
            ->assertDontSee('B 9102 AA');
    }

    public function test_motor_aktif_kembali_muncul_di_katalog_jika_syarat_terpenuhi(): void
    {
        $p = $this->pemilik();
        $penyewa = $this->penyewa();
        $motor = $this->motorUntuk($p, 'B 9103 AA', Motor::STATUS_TERSEDIA);
        $motor->update(['is_aktif' => false]);

        // Nonaktif -> tidak muncul
        $this->actingAs($penyewa)->get('/penyewa/motor')
            ->assertDontSee('B 9103 AA');

        // Aktifkan kembali -> muncul lagi
        $this->actingAs($p)
            ->post("/pemilik/motor/{$motor->id}/aktifkan")
            ->assertRedirect();

        $this->actingAs($penyewa)->get('/penyewa/motor')
            ->assertOk()
            ->assertSee('B 9103 AA');
    }

    public function test_booking_motor_nonaktif_ditolak_di_web(): void
    {
        $p = $this->pemilik();
        $penyewa = $this->penyewa();
        $motor = Motor::create([
            'pemilik_id' => $p->id, 'merk' => 'Beat', 'tipe_cc' => '125',
            'no_plat' => 'B 9201 AA', 'status' => Motor::STATUS_TERSEDIA, 'is_aktif' => false,
        ]);
        TarifRental::create([
            'motor_id' => $motor->id, 'tarif_harian' => 50000, 'tarif_mingguan' => 300000, 'tarif_bulanan' => 1000000,
        ]);

        $this->actingAs($penyewa)->post('/penyewa/penyewaan', [
            'motor_id' => $motor->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(5)->toDateString(),
            'tipe_durasi' => Penyewaan::TIPE_HARIAN,
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('penyewaans', 0);
    }
}
