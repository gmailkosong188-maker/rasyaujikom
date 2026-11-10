<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pencairan dana bagi hasil (PILIHAN A).
 *
 * Perubahan:
 * 1. Tambah `dicairkan_oleh` (nullable FK -> users.id): admin yang menyerahkan uang.
 * 2. Ubah semantik `settled_at`: dari "kapan bagi hasil dicatat" menjadi
 *    "kapan dana benar-benar dicairkan admin". NULL = menunggu pencairan.
 * 3. Data lama yang settled_at terisi di-NULL-kan karena belum ada bukti
 *    uang benar-benar diserahkan kepada pemilik (sesuai keputusan audit).
 *
 * TIDAK membuat tabel baru. TIDAK menghapus data transaksi/penyewaan/motor/user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bagi_hasils', function (Blueprint $table) {
            $table->foreignId('dicairkan_oleh')
                ->nullable()
                ->after('settled_at')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Belum pernah ada pencairan nyata -> semua catatan lama jadi "menunggu pencairan".
        DB::table('bagi_hasils')->whereNotNull('settled_at')->update(['settled_at' => null]);
    }

    public function down(): void
    {
        Schema::table('bagi_hasils', function (Blueprint $table) {
            $table->dropForeign(['dicairkan_oleh']);
            $table->dropColumn('dicairkan_oleh');
        });
    }
};
