<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PEMILIK = 'pemilik';

    public const ROLE_PENYEWA = 'penyewa';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PEMILIK,
        self::ROLE_PENYEWA,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'email',
        'no_tlpn',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Motor yang dimiliki user ini (role: pemilik).
     */
    public function motors(): HasMany
    {
        return $this->hasMany(Motor::class, 'pemilik_id');
    }

    /**
     * Penyewaan yang dilakukan user ini (role: penyewa).
     */
    public function penyewaans(): HasMany
    {
        return $this->hasMany(Penyewaan::class, 'penyewa_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPemilik(): bool
    {
        return $this->role === self::ROLE_PEMILIK;
    }

    public function isPenyewa(): bool
    {
        return $this->role === self::ROLE_PENYEWA;
    }

    /**
     * Admin utama = akun admin paling awal (akun yang dibuat seeder).
     * Akun ini dilindungi agar sistem tidak kehilangan administrator.
     */
    public function isAdminUtama(): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        $adminPertama = static::query()
            ->where('role', self::ROLE_ADMIN)
            ->min('id');

        return $adminPertama !== null && (int) $adminPertama === (int) $this->id;
    }

    /**
     * Role yang boleh didaftarkan sendiri lewat halaman register.
     * Admin TIDAK boleh mendaftar sendiri (spesifikasi §7.1) — hanya
     * dibuat lewat seeder atau oleh admin yang sudah ada.
     */
    public const ROLE_REGISTRABLE = [
        self::ROLE_PEMILIK,
        self::ROLE_PENYEWA,
    ];

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Nama route dashboard sesuai role user.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_PEMILIK => 'pemilik.dashboard',
            self::ROLE_PENYEWA => 'penyewa.dashboard',
            default => 'login',
        };
    }

    /**
     * URL dashboard sesuai role user.
     */
    public function dashboardUrl(): string
    {
        return route($this->dashboardRoute());
    }
}
