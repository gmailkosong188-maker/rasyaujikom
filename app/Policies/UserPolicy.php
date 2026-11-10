<?php

namespace App\Policies;

use App\Models\User;

/**
 * Authorization modul user (admin).
 *
 * Hanya admin yang boleh mengelola user.
 * Penyewa & pemilik tidak memiliki akses ke CRUD user.
 */
class UserPolicy
{
    /**
     * Melihat daftar user — hanya admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Melihat detail user — hanya admin.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Membuat user baru — hanya admin.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Mengubah user — hanya admin.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menghapus user — hanya admin.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}