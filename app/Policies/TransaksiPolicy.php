<?php

namespace App\Policies;

use App\Models\Transaksi;
use App\Models\User;

/**
 * Authorization modul pembayaran.
 *
 * - Penyewa : membuat & melihat transaksi miliknya sendiri.
 * - Admin   : melihat semua + memverifikasi status pembayaran.
 * - Pemilik : hanya melihat transaksi atas motor miliknya (read-only).
 */
class TransaksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_PEMILIK, User::ROLE_PENYEWA);
    }

    public function view(User $user, Transaksi $transaksi): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPenyewa()) {
            return $transaksi->penyewaan?->penyewa_id === $user->id;
        }

        if ($user->isPemilik()) {
            return $transaksi->penyewaan?->motor?->pemilik_id === $user->id;
        }

        return false;
    }

    /**
     * Membuat pembayaran — khusus penyewa.
     */
    public function create(User $user): bool
    {
        return $user->isPenyewa();
    }

    /**
     * Memverifikasi pembayaran — khusus admin.
     */
    public function proses(User $user, Transaksi $transaksi): bool
    {
        return $user->isAdmin();
    }
}
