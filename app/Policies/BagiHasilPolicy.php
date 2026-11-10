<?php

namespace App\Policies;

use App\Models\BagiHasil;
use App\Models\User;

/**
 * Authorization modul bagi hasil.
 *
 * - Admin   : melihat semua settlement + mencatat.
 * - Pemilik : hanya bagi hasil atas motor miliknya (read-only).
 * - Penyewa : tidak berhak melihat bagi hasil.
 */
class BagiHasilPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_PEMILIK);
    }

    public function view(User $user, BagiHasil $bagiHasil): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPemilik()) {
            return $bagiHasil->penyewaan?->motor?->pemilik_id === $user->id;
        }

        return false;
    }

    /**
     * Mencatat settlement — khusus admin.
     */
    public function catat(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Mengonfirmasi pencairan dana ke pemilik — khusus admin dan
     * hanya untuk catatan yang belum dicairkan.
     */
    public function cairkan(User $user, BagiHasil $bagiHasil): bool
    {
        return $user->isAdmin() && $bagiHasil->isMenungguPencairan();
    }
}
