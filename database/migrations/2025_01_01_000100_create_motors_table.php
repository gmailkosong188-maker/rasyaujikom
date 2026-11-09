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
        Schema::create('motors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemilik_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('merk');
            $table->enum('tipe_cc', ['100', '125', '150']);
            $table->string('no_plat')->unique();
            $table->enum('status', ['pending', 'tersedia', 'disewa', 'perawatan', 'ditolak'])->default('pending');
            $table->string('photo')->nullable();
            $table->string('dokumen_kepemilikan')->nullable();
            $table->timestamps();

            $table->index(['pemilik_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motors');
    }
};
