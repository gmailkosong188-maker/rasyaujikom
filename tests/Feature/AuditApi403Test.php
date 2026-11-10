<?php

namespace Tests\Feature;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PengembalianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditApi403Test extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pemilik = User::factory()->create(['role' => 'pemilik']);
        $penyewa = User::factory()->create(['role' => 'penyewa']);

        $motor = Motor::factory()->create(['pemilik_id' => $pemilik->id]);
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

        return [$pemilik, $penyewa, BagiHasil::first()];
    }

    public function test_pemilik_dan_penyewa_ditolak_json_403_di_semua_endpoint_pencairan(): void
    {
        [$pemilik, $penyewa, $bagi] = $this->siapkan();

        foreach ([['pemilik', $pemilik], ['penyewa', $penyewa]] as [$label, $user]) {
            // TANPA header Accept/JSON -> harus tetap JSON 403, bukan redirect 302.
            $r = $this->actingAs($user, 'sanctum')->get('/api/admin/pencairan');
            fwrite(STDERR, "\n[$label GET /api/admin/pencairan] status={$r->status()} ct=".($r->headers->get('content-type') ?? '-')."\n");
            $this->assertSame(403, $r->status(), "$label harus 403 di index");
            $this->assertStringContainsString('application/json', $r->headers->get('content-type'));
            $r->assertJsonPath('success', false);

            $r = $this->actingAs($user, 'sanctum')->get('/api/admin/pencairan/riwayat');
            fwrite(STDERR, "[$label GET /api/admin/pencairan/riwayat] status={$r->status()}\n");
            $this->assertSame(403, $r->status(), "$label harus 403 di riwayat");
            $this->assertStringContainsString('application/json', $r->headers->get('content-type'));

            $r = $this->actingAs($user, 'sanctum')->patch('/api/admin/pencairan/'.$bagi->id.'/cairkan');
            fwrite(STDERR, "[$label PATCH .../cairkan] status={$r->status()}\n");
            $this->assertSame(403, $r->status(), "$label harus 403 di cairkan");
            $this->assertStringContainsString('application/json', $r->headers->get('content-type'));
            $this->assertNull($bagi->fresh()->settled_at, "$label tidak boleh mengubah data");
        }
    }

    public function test_tanpa_token_json_401(): void
    {
        $r = $this->get('/api/admin/pencairan');
        fwrite(STDERR, "\n[guest GET /api/admin/pencairan] status={$r->status()}\n");
        $this->assertSame(401, $r->status());
        $this->assertStringContainsString('application/json', $r->headers->get('content-type'));
    }
}
