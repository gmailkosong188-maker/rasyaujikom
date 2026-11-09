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
        Schema::create('penyewaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyewa_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('motor_id')->constrained('motors')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('tipe_durasi', ['harian', 'mingguan', 'bulanan']);
            $table->decimal('harga', 12, 2);
            $table->enum('status', ['pending', 'dikonfirmasi', 'berjalan', 'selesai', 'dibatalkan'])->default('pending');
            $table->timestamps();

            $table->index(['penyewa_id', 'status']);
            $table->index(['motor_id', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyewaans');
    }
};
