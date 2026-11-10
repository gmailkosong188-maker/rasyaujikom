<?php

namespace Tests\Feature\Api;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OwnerApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function payloadMotor(array $over = []): array
    {
        return array_merge([
            'merk' => 'Honda Vario',
            'tipe_cc' => '150',
            'no_plat' => 'B 9999 ZZ',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
            'dokumen_kepemilikan' => UploadedFile::fake()->create('stnk.pdf', 100, 'application/pdf'),
        ], $over);
    }

    public function test_pemilik_melihat_motor_miliknya_sendiri(): void
    {
        $p1 = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $p2 = $this->pengguna(User::ROLE_PEMILIK, 'p2@t.test');

        $this->motor($p1, 'B 1111 AA');
        $this->motor($p2, 'B 2222 BB');

        $res = $this->sebagai($p1)->getJson('/api/owner/motors');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no_plat', 'B 1111 AA');

        $this->assertNotContains(
            'B 2222 BB',
            collect($res->json('data'))->pluck('no_plat')->all()
        );
    }

    public function test_pemilik_menambah_motor_dan_status_pending(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');

        $res = $this->sebagai($pemilik)->post('/api/owner/motors', $this->payloadMotor());

        $res->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Motor::STATUS_PENDING)
            ->assertJsonPath('data.pemilik_id', $pemilik->id);

        $motor = Motor::where('no_plat', 'B 9999 ZZ')->firstOrFail();
        $this->assertSame(Motor::STATUS_PENDING, $motor->status);
        $this->assertSame($pemilik->id, $motor->pemilik_id);
        Storage::disk('public')->assertExists($motor->photo);
        Storage::disk('public')->assertExists($motor->dokumen_kepemilikan);
    }

    public function test_tambah_motor_tanpa_berkas_ditolak(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');

        $this->sebagai($pemilik)
            ->postJson('/api/owner/motors', [
                'merk' => 'Beat',
                'tipe_cc' => '125',
                'no_plat' => 'B 1 AA',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['photo', 'dokumen_kepemilikan']);

        $this->assertDatabaseCount('motors', 0);
    }

    public function test_pemilik_tidak_melihat_pendapatan_pemilik_lain(): void
    {
        $p1 = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $p2 = $this->pengguna(User::ROLE_PEMILIK, 'p2@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $motor = $this->motor($p1, 'B 1111 AA');
        $penyewaan = $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_SELESAI);

        Transaksi::create([
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => 250000,
            'metode_pembayaran' => Transaksi::METODE_TRANSFER,
            'status' => Transaksi::STATUS_DIBAYAR,
            'tanggal' => now()->toDateString(),
        ]);

        BagiHasil::create([
            'penyewaan_id' => $penyewaan->id,
            'persentase_hasil_pemilik' => 80,
            'persentase_hasil_admin' => 20,
            'nominal_pemilik' => 200000,
            'nominal_admin' => 50000,
            'settled_at' => now(),
            'tanggal' => now()->toDateString(),
        ]);

        $resPemilik = $this->sebagai($p1)->getJson('/api/owner/revenue'.$this->rentang());
        $resPemilik->assertOk()->assertJsonPath('success', true);
        $this->assertSame(200000.0, (float) $resPemilik->json('data.total_bagi_hasil_pemilik'));
        $this->assertSame(250000.0, (float) $resPemilik->json('data.total_pendapatan'));

        $resPemilikLain = $this->sebagai($p2)->getJson('/api/owner/revenue'.$this->rentang());
        $resPemilikLain->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.jumlah_motor', 0);

        $this->assertSame(0.0, (float) $resPemilikLain->json('data.total_bagi_hasil_pemilik'));
        $this->assertSame(0.0, (float) $resPemilikLain->json('data.total_pendapatan'));
        $this->assertSame([], $resPemilikLain->json('data.bagi_hasil'));
    }

    public function test_role_penyewa_tidak_bisa_akses_endpoint_pemilik(): void
    {
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's@t.test');

        $this->sebagai($penyewa)
            ->getJson('/api/owner/motors')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->sebagai($penyewa)
            ->getJson('/api/owner/revenue')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    // ---------- AKTIF / NONAKTIF (API) ----------

    public function test_pemilik_bisa_nonaktifkan_motor_lewat_api(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $motor = $this->motor($pemilik, 'B 9301 AA', Motor::STATUS_TERSEDIA);

        $res = $this->sebagai($pemilik)
            ->postJson("/api/owner/motors/{$motor->id}/nonaktifkan");

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertFalse(Motor::find($motor->id)->isAktif());
    }

    public function test_pemilik_bisa_aktifkan_kembali_motor_lewat_api(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $motor = $this->motor($pemilik, 'B 9302 AA', Motor::STATUS_TERSEDIA);
        $motor->update(['is_aktif' => false]);

        $res = $this->sebagai($pemilik)
            ->postJson("/api/owner/motors/{$motor->id}/aktifkan");

        $res->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(Motor::find($motor->id)->isAktif());
    }

    public function test_pemilik_tidak_bisa_nonaktifkan_motor_yang_disewa_lewat_api(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $penyewa = $this->pengguna(User::ROLE_PENYEWA, 's1@t.test');
        $motor = $this->motor($pemilik, 'B 9303 AA', Motor::STATUS_TERSEDIA);

        $this->penyewaan($penyewa, $motor, Penyewaan::STATUS_BERJALAN);

        $res = $this->sebagai($pemilik)
            ->postJson("/api/owner/motors/{$motor->id}/nonaktifkan");

        $res->assertStatus(422)->assertJsonPath('success', false);
        $this->assertTrue(Motor::find($motor->id)->isAktif());
    }

    public function test_bukan_pemilik_tidak_bisa_nonaktifkan_motor_lewat_api(): void
    {
        $pemilik = $this->pengguna(User::ROLE_PEMILIK, 'p1@t.test');
        $pemilikLain = $this->pengguna(User::ROLE_PEMILIK, 'p2@t.test');
        $motor = $this->motor($pemilik, 'B 9304 AA', Motor::STATUS_TERSEDIA);

        $this->sebagai($pemilikLain)
            ->postJson("/api/owner/motors/{$motor->id}/nonaktifkan")
            ->assertForbidden();

        $this->assertTrue(Motor::find($motor->id)->isAktif());
    }
}
