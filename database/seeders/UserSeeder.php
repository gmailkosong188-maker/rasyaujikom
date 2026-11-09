<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Membuat akun awal untuk ketiga role.
 *
 * Password di-hash dengan Hash::make() (bcrypt) sesuai aturan
 * hashing Laravel — tidak pernah disimpan sebagai plain text.
 */
class UserSeeder extends Seeder
{
    /**
     * Akun yang dibuat. Password default: "password".
     */
    public const AKUN = [
        [
            'nama' => 'Administrator',
            'email' => 'admin@rentalmotor.test',
            'no_tlpn' => '081200000001',
            'role' => User::ROLE_ADMIN,
        ],
        [
            'nama' => 'Budi Santoso',
            'email' => 'pemilik@rentalmotor.test',
            'no_tlpn' => '081200000002',
            'role' => User::ROLE_PEMILIK,
        ],
        [
            'nama' => 'Siti Aminah',
            'email' => 'pemilik2@rentalmotor.test',
            'no_tlpn' => '081200000003',
            'role' => User::ROLE_PEMILIK,
        ],
        [
            'nama' => 'Andi Pratama',
            'email' => 'penyewa@rentalmotor.test',
            'no_tlpn' => '081200000004',
            'role' => User::ROLE_PENYEWA,
        ],
        [
            'nama' => 'Rina Wulandari',
            'email' => 'penyewa2@rentalmotor.test',
            'no_tlpn' => '081200000005',
            'role' => User::ROLE_PENYEWA,
        ],
    ];

    public function run(): void
    {
        foreach (self::AKUN as $akun) {
            User::updateOrCreate(
                ['email' => $akun['email']],
                [
                    'nama' => $akun['nama'],
                    'no_tlpn' => $akun['no_tlpn'],
                    'role' => $akun['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('UserSeeder: '.count(self::AKUN).' akun dibuat.');
    }
}
