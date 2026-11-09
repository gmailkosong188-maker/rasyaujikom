<?php

namespace App\Policies;

use App\Models\Motor;
use App\Models\User;

/**
 * Authorization modul motor.
 *
 * Aturan penting:
 * - Pemilik hanya boleh menyentuh motor miliknya sendiri.
 * - Penyewa sama sekali tidak boleh mengubah motor.
 * - Verifikasi/perubahan status bebas hanya milik admin.
 */
class MotorPolicy
{
    /**
     * Melihat daftar motor (route terpisah per role, jadi cukup cek role).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_PEMILIK, User::ROLE_PENYEWA);
    }

    /**
     * Melihat detail motor.
     *
     * Admin: semua. Pemilik: miliknya. Penyewa: hanya yang tersedia.
     */
    public function view(User $user, Motor $motor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPemilik()) {
            return $motor->pemilik_id === $user->id;
        }

        if ($user->isPenyewa()) {
            return $motor->isTersedia();
        }

        return false;
    }

    /**
     * Pemilik membuat motor baru (penyewa & admin tidak).
     */
    public function create(User $user): bool
    {
        return $user->isPemilik();
    }

    /**
     * Mengubah data motor.
     *
     * Admin: semua. Pemilik: hanya miliknya dan tidak sedang disewa.
     */
    public function update(User $user, Motor $motor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPemilik()
            && $motor->pemilik_id === $user->id
            && $motor->bolehDieditPemilik();
    }

    /**
     * Menghapus motor.
     *
     * Pemilik: miliknya sendiri dan belum punya riwayat penyewaan.
     */
    public function delete(User $user, Motor $motor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPemilik()
            && $motor->pemilik_id === $user->id
            && $motor->bolehDihapusPemilik();
    }

    /**
     * Verifikasi / menerima / menolak motor — khusus admin.
     */
    public function verifikasi(User $user, Motor $motor): bool
    {
        return $user->isAdmin() && $motor->status === Motor::STATUS_PENDING;
    }

    /**
     * Mengubah status motor secara bebas — khusus admin.
     */
    public function ubahStatus(User $user, Motor $motor): bool
    {
        return $user->isAdmin();
    }

    /**
     * Melihat dokumen kepemilikan — hanya admin dan pemilik motor itu.
     * Dokumen berisi data pribadi, jadi tidak boleh diakses penyewa.
     */
    public function lihatDokumen(User $user, Motor $motor): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isPemilik() && $motor->pemilik_id === $user->id;
    }
}
