<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penanganan berkas motor (foto & dokumen kepemilikan).
 *
 * Berkas tidak disimpan sebagai blob di database — yang masuk ke
 * kolom `photo` / `dokumen_kepemilikan` hanyalah path relatif pada
 * disk `public` (storage/app/public/motor-*).
 */
class MotorFileService
{
    public const DISK = 'public';

    public const DIR_FOTO = 'motor-foto';

    public const DIR_DOKUMEN = 'motor-dokumen';

    public function simpanFoto(UploadedFile $file, ?string $lama = null): string
    {
        return $this->simpan($file, self::DIR_FOTO, $lama);
    }

    public function simpanDokumen(UploadedFile $file, ?string $lama = null): string
    {
        return $this->simpan($file, self::DIR_DOKUMEN, $lama);
    }

    public function hapus(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    protected function simpan(UploadedFile $file, string $dir, ?string $lama): string
    {
        $path = $file->store($dir, self::DISK);

        // Hapus berkas lama supaya tidak menumpuk sampah di storage.
        if ($lama && $lama !== $path) {
            $this->hapus($lama);
        }

        return $path;
    }
}
