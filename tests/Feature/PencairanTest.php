<?php

namespace Tests\Feature;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BagiHasilService;
use App\Services\PengembalianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Bagi hasil & pencairan dana.
 *
 * Aturan yang diuji:
 * - Bagi hasil dibuat HANYA setelah pengembalian dikonfirmasi admin.
 * - Pencairan hanya oleh admin, tidak boleh ganda, tidak sebelum selesai.
 * - 80% pemilik / 20% admin.
 */
class PencairanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pemilik(): User
    {
        return User::factory()->create(['role' => 'pemilik']);
    }

    private function penyewa(): User
    {
        return User::factory()->create(['role' => 'penyewa']);
    }

    /**
     * Buat penyewaan berstatus tertentu beserta motor & tarifnya.
     */
    private function buatPenyewaan(User $pemilik, User $penyewa, string $status = Penyewaan::STATUS_BERJALAN, float $harga = 1000000): Penyewaan
    {
        $motor = Motor::factory()->create([
            'pemilik_id' => $pemilik->id,
            'status' => Motor::STATUS_DISEWA,
        ]);

        TarifRental::factory()->create([
            'motor_id' => $motor->id,
            'tarif_harian' => 100000,
            'tarif_mingguan' => 600000,
            'tarif_bulanan' => 2000000,
        ]);

        $penyewaan = Penyewaan::factory()->create([
            'motor_id' => $motor->id,
            'penyewa_id' => $penyewa->id,
            'status' => $status,
            'harga' => $harga,
        ]);

        return $penyewaan;
    }

    private function bayar(Penyewaan $penyewaan, float $jumlah): Transaksi
    {
        return Transaksi::factory()->create([
            'penyewaan_id' => $penyewaan->id,
            'jumlah' => $jumlah,
            'metode_pembayaran' => Transaksi::METODE_CASH,
            'status' => Transaksi::STATUS_DIBAYAR,
            'tanggal' => now()->toDateString(),
        ]);
    }

    /**
     * Jalankan alur penuh sampai penyewaan selesai:
     * bayar -> penyewa ajukan pengembalian -> admin konfirmasi.
     */
    private function selesaikan(Penyewaan $penyewaan, float $jumlah = 1000000): void
    {
        $this->bayar($penyewaan, $jumlah);

        $service = app(PengembalianService::class);
        $service->ajukan($penyewaan->fresh());
        $service->konfirmasi($penyewaan->fresh());
    }

    // 1. Pembayaran berhasil tetapi motor belum dikembalikan -> belum ada pencairan.
    public function test_skenario_1_bayar_tapi_belum_kembali_tidak_ada_bagi_hasil(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->bayar($penyewaan, 1000000);

        $this->assertSame(0, BagiHasil::count());
        $this->assertSame(0, BagiHasil::query()->sudahDicairkan()->count());
        $this->assertSame(0, BagiHasil::query()->menungguPencairan()->count());
    }

    // 2. Motor masih disewa: pencairan ditolak (belum ada catatan untuk dicairkan).
    public function test_skenario_2_motor_masih_disewa_pencairan_ditolak(): void
    {
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa, Penyewaan::STATUS_BERJALAN);

        $this->bayar($penyewaan, 1000000);

        // Belum ada bagi hasil, jadi tidak ada yang bisa dicairkan.
        $this->assertSame(0, BagiHasil::count());

        // Meski dipaksa membuat manual, service menolak karena belum selesai.
        $service = app(BagiHasilService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->catat($penyewaan);
    }

    // 3. Pengembalian dikonfirmasi: bagi hasil dibuat.
    public function test_skenario_3_pengembalian_dikonfirmasi_membuat_bagi_hasil(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $this->assertSame(1, BagiHasil::count());

        $bagiHasil = BagiHasil::first();
        $this->assertSame($penyewaan->id, $bagiHasil->penyewaan_id);        $this->assertNull($bagiHasil->settled_at, 'Bagi hasil baru harus menunggu pencairan.');
        $this->assertNull($bagiHasil->dicairkan_oleh);
    }

    // 4. Perhitungan 80% dan 20% benar.
    public function test_skenario_4_perhitungan_80_dan_20_persen(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        $this->assertSame(80, (int) $bagiHasil->persentase_hasil_pemilik);
        $this->assertSame(20, (int) $bagiHasil->persentase_hasil_admin);
        $this->assertEquals(800000, (float) $bagiHasil->nominal_pemilik);
        $this->assertEquals(200000, (float) $bagiHasil->nominal_admin);
        $this->assertEquals(
            1000000,
            (float) $bagiHasil->nominal_pemilik + (float) $bagiHasil->nominal_admin
        );
    }

    // 5. Bagi hasil tidak dibuat dua kali.
    public function test_skenario_5_bagi_hasil_tidak_dibuat_dua_kali(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $this->assertSame(1, BagiHasil::count());

        // Paksa catat ulang -> harus gagal.
        try {
            app(BagiHasilService::class)->catat($penyewaan->fresh());
            $this->fail('Pencatatan ganda seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('sudah pernah dicatat', $e->getMessage());
        }

        $this->assertSame(1, BagiHasil::count());
    }

    // 6. Admin berhasil mengonfirmasi pencairan.
    public function test_skenario_6_admin_berhasil_mencairkan(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        $this->actingAs($admin)
            ->patch(route('admin.bagi-hasil.cairkan', $bagiHasil))
            ->assertRedirect();

        $bagiHasil->refresh();
        $this->assertNotNull($bagiHasil->settled_at);
        $this->assertSame($admin->id, $bagiHasil->dicairkan_oleh);
        // Nominal tidak berubah setelah pencairan.
        $this->assertEquals(800000, (float) $bagiHasil->nominal_pemilik);
        $this->assertEquals(200000, (float) $bagiHasil->nominal_admin);
    }

    // 7. Pencairan kedua kali ditolak.
    public function test_skenario_7_pencairan_kedua_ditolak(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();
        app(BagiHasilService::class)->cairkan($bagiHasil, $admin);

        $waktuPertama = $bagiHasil->fresh()->settled_at;

        // Via service.
        try {
            app(BagiHasilService::class)->cairkan($bagiHasil->fresh(), $admin);
            $this->fail('Pencairan ganda seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('sudah dicairkan', $e->getMessage());
        }

        // Via HTTP juga harus ditolak (403 karena policy).
        $this->actingAs($admin)
            ->patch(route('admin.bagi-hasil.cairkan', $bagiHasil))
            ->assertForbidden();

        $this->assertEquals($waktuPertama, $bagiHasil->fresh()->settled_at);
    }

    // 8. Pemilik tidak bisa mengonfirmasi pencairan.
    public function test_skenario_8_pemilik_tidak_bisa_mencairkan(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        // Pemilik pemilik motor tersebut. Route ada di grup role:admin,
        // jadi middleware menolak lebih dulu (redirect/403) — keduanya berarti ditolak.
        $this->actingAs($pemilik)
            ->patch(route('admin.bagi-hasil.cairkan', $bagiHasil))
            ->assertStatus(302);

        // Penyewa juga.
        $this->actingAs($penyewa)
            ->patch(route('admin.bagi-hasil.cairkan', $bagiHasil))
            ->assertStatus(302);

        $this->assertNull($bagiHasil->fresh()->settled_at);

        // Service juga menolak non-admin.
        try {
            app(BagiHasilService::class)->cairkan($bagiHasil, $pemilik);
            $this->fail('Pemilik seharusnya tidak bisa mencairkan.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Hanya admin', $e->getMessage());
        }
    }

    // 9. Pemilik hanya melihat data bagi hasil miliknya.
    public function test_skenario_9_pemilik_hanya_melihat_data_miliknya(): void
    {
        $admin = $this->admin();
        $pemilikA = $this->pemilik();
        $pemilikB = $this->pemilik();
        $penyewa = $this->penyewa();

        $sewaA = $this->buatPenyewaan($pemilikA, $penyewa);
        $this->selesaikan($sewaA, 1000000);

        $sewaB = $this->buatPenyewaan($pemilikB, $penyewa);
        $this->selesaikan($sewaB, 500000);

        $this->assertSame(2, BagiHasil::count());

        $response = $this->actingAs($pemilikA)->get(route('pemilik.dashboard'));
        $response->assertOk();

        $this->assertSame(1, $pemilikA->motors()->count());
        $jumlahA = BagiHasil::query()
            ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilikA->id))
            ->count();
        $jumlahB = BagiHasil::query()
            ->whereHas('penyewaan.motor', fn ($q) => $q->where('pemilik_id', $pemilikB->id))
            ->count();

        $this->assertSame(1, $jumlahA);
        $this->assertSame(1, $jumlahB);

        // Data B tidak terlihat di dashboard A: nominal pemilik B adalah 400.000.
        $response->assertDontSee('400.000');
    }

    // 10. Penyewaan yang belum selesai tidak dapat dicairkan.
    public function test_skenario_10_penyewaan_belum_selesai_tidak_bisa_dicairkan(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa, Penyewaan::STATUS_BERJALAN);

        $this->bayar($penyewaan, 1000000);

        // Buat catatan secara paksa di DB (mensimulasikan data tidak konsisten),
        // lalu pastikan service menolak pencairan karena penyewaan belum selesai.
        $bagiHasil = BagiHasil::create([
            'penyewaan_id' => $penyewaan->id,
            'persentase_hasil_pemilik' => 80,
            'persentase_hasil_admin' => 20,
            'nominal_pemilik' => 800000,
            'nominal_admin' => 200000,
            'settled_at' => null,
            'dicairkan_oleh' => null,
            'tanggal' => now()->toDateString(),
        ]);

        try {
            app(BagiHasilService::class)->cairkan($bagiHasil, $admin);
            $this->fail('Pencairan untuk penyewaan belum selesai seharusnya ditolak.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('pengembalian dikonfirmasi', $e->getMessage());
        }

        $this->assertNull($bagiHasil->fresh()->settled_at);
    }

    // Tambahan: total Admin tidak menghitung dana menunggu pencairan.
    public function test_total_admin_tidak_menghitung_dana_menunggu_pencairan(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        // Sebelum dicairkan: admin belum menerima apa pun.
        $ringkasan = app(\App\Services\DashboardService::class)->admin($admin);
        $this->assertEquals(0, $ringkasan['bagianAdmin']);
        $this->assertEquals(0, $ringkasan['bagianPemilik']);
        $this->assertEquals(800000, $ringkasan['danaMenungguPencairan']);
        $this->assertSame(1, $ringkasan['jumlahMenungguPencairan']);

        // Setelah dicairkan: baru masuk pendapatan.
        app(BagiHasilService::class)->cairkan($bagiHasil, $admin);

        $ringkasan = app(\App\Services\DashboardService::class)->admin($admin);
        $this->assertEquals(200000, $ringkasan['bagianAdmin']);
        $this->assertEquals(800000, $ringkasan['bagianPemilik']);
        $this->assertEquals(0, $ringkasan['danaMenungguPencairan']);
    }

    // Tambahan: API pencairan memakai aturan yang sama.
    public function test_api_pencairan_admin_berhasil_dan_menolak_ganda(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/pencairan/{$bagiHasil->id}/cairkan")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status_pencairan', 'sudah_dicairkan');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/pencairan/{$bagiHasil->id}/cairkan")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // Tambahan: pemilik ditolak di API pencairan.
    public function test_api_pencairan_ditolak_untuk_pemilik(): void
    {
        $admin = $this->admin();
        $pemilik = $this->pemilik();
        $penyewa = $this->penyewa();
        $penyewaan = $this->buatPenyewaan($pemilik, $penyewa);

        $this->selesaikan($penyewaan, 1000000);

        $bagiHasil = BagiHasil::first();

        $this->actingAs($pemilik, 'sanctum')
            ->patchJson("/api/admin/pencairan/{$bagiHasil->id}/cairkan")
            ->assertForbidden();
    }
}
