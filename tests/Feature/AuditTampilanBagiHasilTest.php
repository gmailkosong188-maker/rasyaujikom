<?php

namespace Tests\Feature;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\BagiHasilService;
use App\Services\PengembalianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTampilanBagiHasilTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'nama' => 'Admin Penanggung Jawab']);
        $pemilik = User::factory()->create(['role' => 'pemilik', 'nama' => 'Budi Pemilik']);
        $penyewa = User::factory()->create(['role' => 'penyewa', 'nama' => 'Sari Penyewa']);

        $motor = Motor::factory()->create(['pemilik_id' => $pemilik->id, 'no_plat' => 'B 1234 XY']);
        $sewa = Penyewaan::factory()->create([
            'motor_id' => $motor->id, 'penyewa_id' => $penyewa->id,
            'status' => 'berjalan', 'harga' => 1000000,
        ]);
        Transaksi::factory()->create([
            'penyewaan_id' => $sewa->id, 'jumlah' => 1000000,
            'status' => 'dibayar', 'metode_pembayaran' => 'cash', 'tanggal' => now(),
        ]);
        $ps = app(PengembalianService::class);
        $ps->ajukan($sewa->fresh());
        $ps->konfirmasi($sewa->fresh());

        return [$admin, $pemilik, BagiHasil::first()];
    }

    public function test_index_admin_menampilkan_menunggu_dan_tombol(): void
    {
        [$admin, , $bagi] = $this->siapkan();

        $r = $this->actingAs($admin)->get(route('admin.bagi-hasil.index'));
        $r->assertOk();
        $r->assertSee('Menunggu pencairan');
        $r->assertSee('Konfirmasi Pencairan');
        $r->assertSee('800.000');
        $r->assertSee('200.000');
        $r->assertSee('Budi Pemilik');
    }

    public function test_detail_admin_menampilkan_tombol_lalu_status_setelah_dicairkan(): void
    {
        [$admin, , $bagi] = $this->siapkan();

        // Sebelum cair: tombol + peringatan + nominal pemilik.
        $r = $this->actingAs($admin)->get(route('admin.bagi-hasil.show', $bagi));
        $r->assertOk();
        $r->assertSee('Menunggu pencairan');
        $r->assertSee('Konfirmasi Pencairan');
        $r->assertSee('Rp 800.000');
        $r->assertSee('Budi Pemilik');
        $r->assertDontSee('Dicairkan Oleh');

        // Cairkan lewat halaman detail (form action = service yang sama).
        $this->actingAs($admin)
            ->patch(route('admin.bagi-hasil.cairkan', $bagi))
            ->assertRedirect();

        $r = $this->actingAs($admin)->get(route('admin.bagi-hasil.show', $bagi->fresh()));
        $r->assertOk();
        $r->assertSee('Sudah dicairkan');
        $r->assertSee('Waktu Pencairan');
        $r->assertSee('Dicairkan Oleh');
        $r->assertSee('Admin Penanggung Jawab');
        $r->assertDontSee('Konfirmasi Pencairan');
    }

    public function test_pemilik_melihat_status_dan_nominal_haknya_sendiri(): void
    {
        [$admin, $pemilik, $bagi] = $this->siapkan();

        $r = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $r->assertOk();
        $r->assertSee('Menunggu pencairan');
        $r->assertSee('800.000');

        app(BagiHasilService::class)->cairkan($bagi, $admin);

        $r = $this->actingAs($pemilik)->get(route('pemilik.dashboard'));
        $r->assertOk();
        $r->assertSee('Sudah dicairkan');
        $r->assertSee('800.000');
    }

    public function test_laporan_pemilik_menampilkan_kolom_status_pencairan(): void
    {
        [$admin, $pemilik, $bagi] = $this->siapkan();

        $r = $this->actingAs($pemilik)->get(route('pemilik.laporan.index'));
        $r->assertOk();
        $r->assertSee('Status Pencairan');
        $r->assertSee('Menunggu pencairan');
        $r->assertSee('800.000');

        app(BagiHasilService::class)->cairkan($bagi, $admin);

        $r = $this->actingAs($pemilik)->get(route('pemilik.laporan.index'));
        $r->assertOk();
        $r->assertSee('Sudah dicairkan');
    }
}
