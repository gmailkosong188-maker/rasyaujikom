<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Aturan Bagi Hasil
    |--------------------------------------------------------------------------
    |
    | Persentase pembagian pendapatan penyewaan antara pemilik motor dan
    | admin/petugas. Total kedua nilai ini WAJIB berjumlah 100.
    |
    | Ubah di sini (atau lewat .env) tanpa menyentuh controller.
    |
    */

    'persentase_pemilik' => (int) env('BAGI_HASIL_PEMILIK', 80),

    'persentase_admin' => (int) env('BAGI_HASIL_ADMIN', 20),

];
