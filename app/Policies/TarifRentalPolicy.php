<?php

namespace App\Policies;

use App\Models\TarifRental;
use App\Models\User;

/**
 * Authorization modul tarif rental.
 *
 * Sesuai spesifikasi §3: hanya ADMIN yang menentukan/mengubah tarif.
 * Pemilik & penyewa hanya boleh MELIHAT tarif.
 */
class TarifRentalPolicy
{
    /**
     * Melihat daftar tarif — semua role yang login boleh melihat.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_PEMILIK, User::ROLE_PENYEWA);
    }

    /**
     * Melihat detail tarif.
     *
     * Admin & penyewa: semua tarif.
     * Pemilik: hanya tarif motor miliknya.
     */
    public function view(User $user, TarifRental $tarif): bool
    {
        if ($user->isAdmin() || $user->isPenyewa()) {
            return true;
        }

        return $user->isPemilik() && $tarif->motor?->pemilik_id === $user->id;
    }

    /**
     * Membuat tarif — khusus admin.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Mengubah tarif — khusus admin.
     */
    public function update(User $user, TarifRental $tarif): bool
    {
        return $user->isAdmin();
    }

    /**
     * Menghapus tarif — khusus admin, dan tidak boleh bila motor
     * sedang disewa (agar transaksi berjalan tetap punya acuan).
     */
    public function delete(User $user, TarifRental $tarif): bool
    {
        return $user->isAdmin()
            && $tarif->motor?->status !== \App\Models\Motor::STATUS_DISEWA;
    }
}
