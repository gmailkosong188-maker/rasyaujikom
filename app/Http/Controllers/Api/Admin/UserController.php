<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD user oleh admin.
 *
 * Tidak ada tabel/kolom baru: seluruh operasi memakai tabel `users` yang
 * sudah ada. Penghapusan dilindungi agar akun admin utama dan data
 * historis (penyewaan/transaksi lewat motor) tidak ikut hilang.
 */
class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $cari = trim((string) $request->query('cari'));

        $users = User::query()
            ->when(
                in_array($role, User::ROLES, true),
                fn ($q) => $q->where('role', $role)
            )
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('nama', 'like', "%{$cari}%")
                        ->orWhere('email', 'like', "%{$cari}%")
                        ->orWhere('no_tlpn', 'like', "%{$cari}%");
                });
            })
            ->orderBy('id')
            ->get();

        return $this->sukses(
            $users->map(fn (User $user) => $this->dataUser($user))->values(),
            'Daftar user berhasil diambil.'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(fn () => User::create($request->validated()));

        return $this->sukses(
            $this->dataUser($user),
            'User berhasil dibuat.',
            201
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->loadCount(['motors', 'penyewaans']);

        return $this->sukses(array_merge($this->dataUser($user), [
            'jumlah_motor' => (int) $user->motors_count,
            'jumlah_penyewaan' => (int) $user->penyewaans_count,
            'admin_utama' => $user->isAdminUtama(),
            'dibuat_pada' => $user->created_at?->toIso8601String(),
        ]), 'Detail user berhasil diambil.');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        // Password kosong / tidak dikirim -> password lama dipertahankan.
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        // Admin utama tidak boleh diturunkan role-nya agar sistem tetap
        // memiliki administrator.
        if ($user->isAdminUtama() && isset($data['role']) && $data['role'] !== User::ROLE_ADMIN) {
            return $this->gagal(
                'Role akun admin utama tidak dapat diubah.',
                ['role' => ['Akun admin utama harus tetap berperan admin.']]
            );
        }

        $user->update($data);

        return $this->sukses(
            $this->dataUser($user->fresh()),
            'User berhasil diperbarui.'
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return $this->gagal(
                'Anda tidak dapat menghapus akun Anda sendiri.',
                ['user' => ['Akun yang sedang dipakai tidak dapat dihapus.']]
            );
        }

        if ($user->isAdminUtama()) {
            return $this->gagal(
                'Akun admin utama tidak dapat dihapus.',
                ['user' => ['Akun admin utama dilindungi dari penghapusan.']]
            );
        }

        // Data historis: penyewaan (beserta transaksi & bagi hasil di dalamnya)
        // dan motor (beserta tarifnya) tidak boleh terhapus bersama user.
        if ($user->penyewaans()->exists()) {
            return $this->gagal(
                'User tidak dapat dihapus karena masih memiliki riwayat penyewaan dan transaksi.',
                ['user' => ['Riwayat penyewaan/transaksi harus dipertahankan.']]
            );
        }

        if ($user->motors()->exists()) {
            return $this->gagal(
                'User tidak dapat dihapus karena masih memiliki motor terdaftar beserta tarifnya.',
                ['user' => ['Hapus atau pindahkan data motor terlebih dahulu.']]
            );
        }

        DB::transaction(function () use ($user) {
            // Token API ikut dibersihkan agar tidak ada token menggantung.
            $user->tokens()->delete();
            $user->delete();
        });

        return $this->sukses(null, 'User berhasil dihapus.');
    }
}
