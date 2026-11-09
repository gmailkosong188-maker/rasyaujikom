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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyewaan_id')->constrained('penyewaans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->enum('metode_pembayaran', ['cash', 'transfer']);
            $table->enum('status', ['pending', 'dibayar', 'ditolak', 'dikembalikan'])->default('pending');
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['penyewaan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
