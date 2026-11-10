<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $role = $request->query('role');
        $cari = trim((string) $request->query('cari'));

        $users = User::query()
            ->withCount(['motors', 'penyewaans'])
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
            ->paginate(10)
            ->withQueryString();

        return view('admin.user.index', [
            'users' => $users,
            'role' => $role,
            'cari' => $cari,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.user.create', [
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'no_tlpn' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'role' => ['required', 'string', 'in:' . implode(',', User::ROLES)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        DB::transaction(function () use ($validated) {
            User::create($validated);
        });

        return redirect()
            ->route('admin.user.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.user.edit', [
            'user' => $user,
            'roles' => User::ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'no_tlpn' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'role' => ['required', 'string', 'in:' . implode(',', User::ROLES)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->isAdminUtama() && isset($validated['role']) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()
                ->withInput()
                ->with('error', 'Role akun admin utama tidak dapat diubah.');
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function () use ($user, $validated) {
            $user->update($validated);
        });

        return redirect()
            ->route('admin.user.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->id === $request->user()->id) {
            return back()
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isAdminUtama()) {
            return back()
                ->with('error', 'Akun admin utama tidak dapat dihapus.');
        }

        if ($user->penyewaans()->exists()) {
            return back()
                ->with('error', 'User tidak dapat dihapus karena masih memiliki riwayat penyewaan dan transaksi.');
        }

        if ($user->motors()->exists()) {
            return back()
                ->with('error', 'User tidak dapat dihapus karena masih memiliki motor terdaftar beserta tarifnya.');
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });

        return redirect()
            ->route('admin.user.index')
            ->with('success', 'User berhasil dihapus.');
    }
}