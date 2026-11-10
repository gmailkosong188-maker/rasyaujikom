<?php

namespace App\Console\Commands;

use App\Models\BagiHasil;
use App\Models\Motor;
use App\Models\Penyewaan;
use App\Models\TarifRental;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoReset extends Command
{
    protected $signature = 'demo:reset
        {--emails= : Email pengguna demo/testing yang datanya akan dihapus (pisahkan koma, wajib diisi)}
        {--dry-run : Tampilkan saja data yang akan dihapus tanpa benar-benar menghapus}';

    protected $description = 'Membersihkan data demo/testing berdasarkan email pengguna yang diberikan secara eksplisit (child dulu, baru parent).';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('>>> DITOLAK: command demo:reset tidak boleh dijalankan di environment production.');
            $this->line('  Jalankan hanya di environment local/testing.');
            return self::FAILURE;
        }

        $emailsOption = $this->option('emails');
        $emails = $emailsOption
            ? array_values(array_filter(array_map('trim', explode(',', $emailsOption))))
            : [];

        if (empty($emails)) {
            $this->error('>>> DITOLAK: daftar email target wajib diisi.');
            $this->line('  Contoh: php artisan demo:reset --emails="demo@test.com,tester@test.com"');
            $this->line('  Tanpa --emails, tidak ada data yang dihapus (agar user manual tidak ikut terhapus).');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $targetUsers = User::whereIn('email', $emails)->get()->keyBy('email');

        $notFound = array_diff($emails, $targetUsers->keys()->all());
        if ($notFound) {
            $this->warn('>>> Email berikut tidak ditemukan di database, dilewati: ' .
                implode(', ', $notFound));
        }

        if ($targetUsers->isEmpty()) {
            $this->info('>>> Tidak ada user target yang ditemukan. Tidak ada data yang dihapus.');
            return self::SUCCESS;
        }

        $adminUtamaId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->min('id');

        $deletableUsers = $targetUsers->reject(fn ($u) => $adminUtamaId !== null && (int) $u->id === (int) $adminUtamaId);
        $protectedUsers = $targetUsers->filter(fn ($u) => $adminUtamaId !== null && (int) $u->id === (int) $adminUtamaId);

        if ($protectedUsers->isNotEmpty()) {
            $this->warn('>>> Admin utama dilindungi (tidak dihapus): ' .
                $protectedUsers->pluck('email')->implode(', '));
        }

        if ($deletableUsers->isEmpty()) {
            $this->info('>>> Semua user target adalah admin utama atau tidak ada yang bisa dihapus.');
            return self::SUCCESS;
        }

        $targetUserIds = $deletableUsers->pluck('id')->toArray();

        $motors = Motor::whereIn('pemilik_id', $targetUserIds)->get()->keyBy('id');
        $motorIds = $motors->keys()->all();

        $blockedMotors = [];
        foreach ($motors as $motorId => $motor) {
            $hasExternalPenyewaan = Penyewaan::where('motor_id', $motorId)
                ->whereNotIn('penyewa_id', $targetUserIds)
                ->exists();

            if ($hasExternalPenyewaan) {
                $blockedMotors[$motorId] = 'Masih memiliki penyewaan dari pengguna non-target.';
            }
        }

        $targetPenyewaanIds = Penyewaan::query()
            ->where(function ($q) use ($targetUserIds, $motorIds) {
                $q->whereIn('penyewa_id', $targetUserIds)
                    ->orWhereIn('motor_id', $motorIds);
            })
            ->pluck('id')
            ->all();

        $blockedPencairan = [];
        if (! empty($targetUserIds)) {
            $rows = DB::table('bagi_hasils')
                ->whereIn('dicairkan_oleh', $targetUserIds)
                ->whereNotIn('penyewaan_id', empty($targetPenyewaanIds) ? [0] : $targetPenyewaanIds)
                ->pluck('id')
                ->all();

            foreach ($rows as $id) {
                $blockedPencairan[] = "bagi_hasil #{$id} dicairkan oleh user target tetapi milik penyewaan non-target.";
            }
        }

        if (! empty($blockedMotors) || ! empty($blockedPencairan)) {
            $this->error('>>> DIBATALKAN: ada dependency target yang tidak bisa dihapus.');
            foreach ($blockedMotors as $motorId => $reason) {
                $this->line("  - motor #{$motorId}: {$reason}");
            }
            foreach ($blockedPencairan as $reason) {
                $this->line("  - {$reason}");
            }
            $this->line('  Hapus/bersihkan dependency tersebut terlebih dahulu, atau jangan sertakan user terkait.');
            return self::FAILURE;
        }

        $counts = [
            'bagi_hasils'   => BagiHasil::whereIn('penyewaan_id', $targetPenyewaanIds)->count(),
            'transaksis'    => Transaksi::whereIn('penyewaan_id', $targetPenyewaanIds)->count(),
            'penyewaans'    => count($targetPenyewaanIds),
            'tarif_rentals' => TarifRental::whereIn('motor_id', $motorIds)->count(),
            'motors'        => count($motorIds),
            'users'         => $deletableUsers->count(),
        ];

        if ($dryRun) {
            $this->info('>>> MODE DRY-RUN: tidak ada data yang dihapus.');
            $this->tampilkanRingkasan($counts, $protectedUsers);
            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($targetPenyewaanIds, $motorIds, $targetUserIds) {
                BagiHasil::whereIn('penyewaan_id', $targetPenyewaanIds)->delete();
                Transaksi::whereIn('penyewaan_id', $targetPenyewaanIds)->delete();
                Penyewaan::whereIn('id', $targetPenyewaanIds)->delete();
                TarifRental::whereIn('motor_id', $motorIds)->delete();
                Motor::whereIn('id', $motorIds)->delete();
                User::whereIn('id', $targetUserIds)->delete();
            });

            $this->info('>>> Data demo berhasil dihapus dalam satu transaksi.');
        } catch (\Throwable $e) {
            $this->error('>>> Gagal menghapus data: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->tampilkanRingkasan($counts, $protectedUsers);

        return self::SUCCESS;
    }

    protected function tampilkanRingkasan(array $counts, $protectedUsers): void
    {
        $this->table(
            ['Tabel', 'Jumlah dihapus'],
            [
                ['bagi_hasils', $counts['bagi_hasils']],
                ['transaksis', $counts['transaksis']],
                ['penyewaans', $counts['penyewaans']],
                ['tarif_rentals', $counts['tarif_rentals']],
                ['motors', $counts['motors']],
                ['users', $counts['users']],
            ]
        );

        if ($protectedUsers->isNotEmpty()) {
            $this->line('Admin utama dilindungi: ' . $protectedUsers->pluck('email')->implode(', '));
        }
    }
}
