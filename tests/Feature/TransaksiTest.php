<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiTest extends TestCase
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

    /** Penyewaan pending milik penyewa dengan harga 250.000. */
    private function penyewaan(): array
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
            'tanggal_mulai' => now()->addDay(), 'tanggal_selesai' => now()->addDays(5),
            'tipe_durasi' => 'harian', 'harga' => 250000, 'status' => Penyewaan::STATUS_PENDING,
        ]);

        return [$penyewaan, $motor];
    }

    private function payload(Penyewaan $p, array $over = []): array
    {
        return array_merge(['penyewaan_id' => $p->id, 'metode_pembayaran' => 'transfer'], $over);
    }

    // ---------- PERHITUNGAN & INTEGRASI PENYEWAAN ----------

    public function test_penyewa_membayar_dan_jumlah_mengikuti_harga_penyewaan(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p))->assertRedirect();

        $t = Transaksi::firstOrFail();
        $this->assertSame($p->id, (int) $t->penyewaan_id);
        $this->assertEquals(250000, (float) $t->jumlah); // dari Penyewaan::harga, bukan input
        $this->assertSame('transfer', $t->metode_pembayaran);
        $this->assertSame(Transaksi::STATUS_PENDING, $t->status);
        $this->assertSame(now()->toDateString(), $t->tanggal->toDateString());
    }

    public function test_nominal_dari_input_user_diabaikan(): void
    {
        [$p] = $this->penyewaan();

        // User menyisipkan harga/nominal kecil untuk menipu.
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p, [
            'jumlah' => 1,
            'harga' => 1,
            'total' => 1,
        ]))->assertRedirect();

        $this->assertEquals(250000, (float) Transaksi::firstOrFail()->jumlah);
    }

    public function test_metode_pembayaran_tidak_valid_ditolak(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->penyewa())
            ->post('/penyewa/transaksi', $this->payload($p, ['metode_pembayaran' => 'paypal']))
            ->assertSessionHasErrors('metode_pembayaran');
        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_tidak_bisa_membayar_penyewaan_orang_lain(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->penyewa('lain@t.test'))
            ->post('/penyewa/transaksi', $this->payload($p))
            ->assertSessionHasErrors('penyewaan_id');
        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_tidak_bisa_membayar_penyewaan_yang_dibatalkan(): void
    {
        [$p] = $this->penyewaan();
        $p->update(['status' => Penyewaan::STATUS_DIBATALKAN]);

        $this->actingAs($this->penyewa())
            ->post('/penyewa/transaksi', $this->payload($p))
            ->assertSessionHasErrors('penyewaan_id');
        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_tidak_bisa_membayar_dua_kali(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));

        $this->actingAs($this->penyewa())
            ->post('/penyewa/transaksi', $this->payload($p))
            ->assertSessionHasErrors('penyewaan_id');

        $this->assertSame(1, Transaksi::count());
    }

    // ---------- AUTHORIZATION ----------

    public function test_penyewa_hanya_melihat_transaksinya_sendiri(): void
    {
        [$p] = $this->penyewaan();
        $p2 = Penyewaan::create([
            'penyewa_id' => $this->penyewa('b@t.test')->id, 'motor_id' => $p->motor_id,
            'tanggal_mulai' => now()->addDays(10), 'tanggal_selesai' => now()->addDays(12),
            'tipe_durasi' => 'harian', 'harga' => 150000, 'status' => Penyewaan::STATUS_PENDING,
        ]);
        $milikLain = Transaksi::create([
            'penyewaan_id' => $p2->id, 'jumlah' => 150000, 'metode_pembayaran' => 'cash',
            'status' => 'pending', 'tanggal' => now()->toDateString(),
        ]);

        $this->actingAs($this->penyewa())->get('/penyewa/transaksi')->assertOk();
        $this->actingAs($this->penyewa())->get("/penyewa/transaksi/{$milikLain->id}")->assertForbidden();
    }

    public function test_pemilik_tidak_bisa_membuat_transaksi(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->pemilik())->post('/penyewa/transaksi', $this->payload($p))->assertRedirect();
        $this->assertDatabaseCount('transaksis', 0);
    }

    public function test_admin_bisa_melihat_transaksi_dan_memverifikasi(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));
        $t = Transaksi::firstOrFail();

        $this->actingAs($this->admin())->get('/admin/transaksi')->assertOk();
        $this->actingAs($this->admin())->get("/admin/transaksi/{$t->id}")->assertOk();

        $this->actingAs($this->admin())
            ->patch("/admin/transaksi/{$t->id}/status", ['status' => 'dibayar'])
            ->assertRedirect();

        $this->assertSame(Transaksi::STATUS_DIBAYAR, $t->fresh()->status);
    }

    public function test_penyewa_tidak_bisa_memverifikasi_transaksi(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));
        $t = Transaksi::firstOrFail();

        $this->actingAs($this->penyewa())
            ->patch("/admin/transaksi/{$t->id}/status", ['status' => 'dibayar'])
            ->assertRedirect();

        $this->assertSame(Transaksi::STATUS_PENDING, $t->fresh()->status);
    }

    // ---------- SINKRONISASI STATUS ----------

    public function test_pembayaran_dibayar_mengonfirmasi_penyewaan_dan_motor(): void
    {
        [$p, $motor] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));
        $t = Transaksi::firstOrFail();

        $this->actingAs($this->admin())
            ->patch("/admin/transaksi/{$t->id}/status", ['status' => 'dibayar']);

        $this->assertSame(Penyewaan::STATUS_DIKONFIRMASI, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_DISEWA, $motor->fresh()->status);
    }

    public function test_pembayaran_ditolak_mengembalikan_penyewaan_ke_pending(): void
    {
        [$p, $motor] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));
        $t = Transaksi::firstOrFail();

        $admin = $this->admin();
        $this->actingAs($admin)->patch("/admin/transaksi/{$t->id}/status", ['status' => 'dibayar']);
        $this->actingAs($admin)->patch("/admin/transaksi/{$t->id}/status", ['status' => 'ditolak']);

        $this->assertSame(Transaksi::STATUS_DITOLAK, $t->fresh()->status);
        $this->assertSame(Penyewaan::STATUS_PENDING, $p->fresh()->status);
        $this->assertSame(Motor::STATUS_TERSEDIA, $motor->fresh()->status);
    }

    // ---------- UI ----------

    public function test_form_pembayaran_tampil_dengan_jumlah_benar(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->penyewa())
            ->get("/penyewa/transaksi/create?penyewaan_id={$p->id}")
            ->assertOk()
            ->assertSee('250.000');
    }

    public function test_form_pembayaran_untuk_penyewaan_dengan_transaksi_dialihkan(): void
    {
        [$p] = $this->penyewaan();
        $this->actingAs($this->penyewa())->post('/penyewa/transaksi', $this->payload($p));

        $this->actingAs($this->penyewa())
            ->get("/penyewa/transaksi/create?penyewaan_id={$p->id}")
            ->assertRedirect(route('penyewa.penyewaan.show', $p));
    }

    public function test_metode_cash_juga_diterima(): void
    {
        [$p] = $this->penyewaan();

        $this->actingAs($this->penyewa())
            ->post('/penyewa/transaksi', $this->payload($p, ['metode_pembayaran' => 'cash']))
            ->assertRedirect();

        $this->assertSame('cash', Transaksi::firstOrFail()->metode_pembayaran);
    }
}
