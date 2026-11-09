<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bagi_hasils', function (Blueprint $table) {
            $table->id();
            // Satu penyewaan hanya boleh disettle satu kali.
            $table->foreignId('penyewaan_id')->unique()->constrained('penyewaans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedTinyInteger('persentase_hasil_pemilik');
            $table->unsignedTinyInteger('persentase_hasil_admin');
            $table->decimal('nominal_pemilik', 12, 2);
            $table->decimal('nominal_admin', 12, 2);
            $table->timestamp('settled_at')->nullable();
            $table->date('tanggal');
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bagi_hasils');
    }
};
