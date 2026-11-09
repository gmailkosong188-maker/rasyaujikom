<?php

namespace App\Policies;

use App\Models\Penyewaan;
use App\Models\User;

/**
 * Authorization modul penyewaan.
 *
 * - Penyewa : hanya penyewaan miliknya.
 * - Admin   : semua penyewaan + memproses status.
 * - Pemilik : hanya penyewaan atas motor miliknya, READ-ONLY
 *             (tidak boleh mengubah status — bukan bagian alurnya).
 */
class PenyewaanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_PEMILIK, User::ROLE_PENYEWA);
    }

    public function view(User $user, Penyewaan $penyewaan): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPenyewa()) {
            return $penyewaan->penyewa_id === $user->id;
        }

        if ($user->isPemilik()) {
            return $penyewaan->motor?->pemilik_id === $user->id;
        }

        return false;
    }

    /**
     * Membuat penyewaan — khusus penyewa.
     */
    public function create(User $user): bool
    {
        return $user->isPenyewa();
    }

    /**
     * Penyewa membatalkan penyewaannya sendiri selama belum berjalan.
     * Pemilik tidak boleh mengubah apa pun.
     */
    public function batalkan(User $user, Penyewaan $penyewaan): bool
    {
        return $user->isPenyewa()
            && $penyewaan->penyewa_id === $user->id
            && $penyewaan->bolehDibatalkan();
    }

    /**
     * Admin memproses status (konfirmasi / berjalan / batalkan / selesai).
     */
    public function proses(User $user, Penyewaan $penyewaan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Penyewa mengajukan pengembalian motor — hanya penyewaan miliknya
     * yang sedang berjalan.
     */
    public function ajukanPengembalian(User $user, Penyewaan $penyewaan): bool
    {
        return $user->isPenyewa()
            && $penyewaan->penyewa_id === $user->id
            && $penyewaan->status === Penyewaan::STATUS_BERJALAN;
    }
}
