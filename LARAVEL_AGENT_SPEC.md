# SPESIFIKASI PROYEK LARAVEL --- SISTEM PENYEWAAN MOTOR

## 1. TUJUAN

Bangun aplikasi web **Sistem Penyewaan Motor** menggunakan:

-   Laravel
-   PHP
-   MySQL/MariaDB
-   Laragon
-   Blade untuk UI
-   Laravel Eloquent ORM
-   Authentication dan Role/Permission berbasis `role`

Aplikasi memiliki 3 role utama:

1.  `admin` / petugas
2.  `pemilik`
3.  `penyewa`

Implementasi harus mengikuti alur bisnis pada ERD, Flowchart, dan DFD
yang diberikan. Jangan membuat fitur bisnis baru yang tidak diperlukan
sebelum fitur inti selesai.

------------------------------------------------------------------------

# 2. ATURAN UTAMA UNTUK AI AGENT

Saat mengembangkan project:

1.  Gunakan Laravel sesuai struktur standar Laravel.
2.  Jangan mengubah nama tabel/field secara sembarangan.
3.  Gunakan migration untuk seluruh struktur database.
4.  Gunakan Model Eloquent dan relasi Eloquent.
5.  Gunakan Controller untuk proses bisnis.
6.  Gunakan Form Request untuk validasi jika diperlukan.
7.  Gunakan middleware/authorization untuk membatasi akses berdasarkan
    role.
8.  Password harus menggunakan hashing Laravel.
9.  Validasi semua input dari user.
10. Gunakan CSRF protection untuk form web.
11. Jangan menghapus data penting secara permanen jika fitur membutuhkan
    histori; gunakan status atau soft delete bila sesuai.
12. Jangan menambahkan tabel baru hanya karena lebih mudah secara
    teknis. Evaluasi dulu kebutuhan dari requirement.
13. Setelah membuat perubahan database, buat/update migration dan
    jalankan migration dengan benar.
14. Setelah fitur selesai, lakukan pengujian manual sesuai skenario.
15. Jangan menganggap proses selesai hanya karena route/controller sudah
    dibuat. Pastikan UI, validasi, database, authorization, dan alur
    bisnis terhubung.

------------------------------------------------------------------------

# 3. ROLE DAN HAK AKSES

## ADMIN / PETUGAS

Admin bertugas mengelola operasional sistem.

Hak akses:

-   login
-   melihat dashboard admin
-   mengelola data user
-   memverifikasi motor yang didaftarkan pemilik
-   mengelola data motor
-   menentukan tarif rental
-   mengelola penyewaan
-   mengelola pembayaran
-   mengonfirmasi pengembalian motor
-   menghitung/mencatat bagi hasil
-   melihat laporan
-   melihat jumlah motor terdaftar
-   melihat jumlah motor disewa
-   melihat total pendapatan
-   melihat grafik penyewaan berdasarkan periode
-   melihat laporan pembayaran
-   logout

## PEMILIK

Pemilik adalah pemilik motor yang menyewakan kendaraan melalui sistem.

Hak akses:

-   registrasi
-   login
-   logout
-   mendaftarkan motor
-   mengisi data motor
-   mengunggah foto motor
-   mengunggah dokumen kepemilikan
-   melihat status motor
-   melihat transaksi/sewa motor miliknya
-   melihat pendapatan
-   melihat laporan bagi hasil

Pemilik tidak boleh mengakses data motor milik pemilik lain.

## PENYEWA

Penyewa menggunakan sistem untuk mencari dan menyewa motor.

Hak akses:

-   registrasi
-   login
-   logout
-   melihat motor tersedia
-   melihat detail motor
-   memilih durasi sewa
-   melakukan pemesanan/penyewaan
-   melihat biaya sewa
-   melakukan pembayaran
-   melihat status penyewaan
-   melihat riwayat penyewaan
-   melihat status pengembalian

Penyewa tidak boleh mengelola motor milik orang lain.

------------------------------------------------------------------------

# 4. DATABASE / ERD

Gunakan 6 tabel bisnis utama.

## 4.1 USERS

Tabel: `users`

Field minimal:

-   `id` PK
-   `nama`
-   `email`
-   `no_tlpn`
-   `password`
-   `role`
-   `created_at`
-   `updated_at`

Nilai `role`:

-   `admin`
-   `pemilik`
-   `penyewa`

Jika project menggunakan migration users bawaan Laravel, modifikasi
migration tersebut daripada membuat tabel user kedua.

------------------------------------------------------------------------

## 4.2 MOTORS

Tabel: `motors`

Field:

-   `id` PK
-   `pemilik_id` FK → `users.id`
-   `merk`
-   `tipe_cc`
-   `no_plat`
-   `status`
-   `photo`
-   `dokumen_kepemilikan`
-   `created_at`
-   `updated_at`

`tipe_cc` mengikuti requirement:

-   100
-   125
-   150

Status motor minimal harus mendukung:

-   `pending`
-   `tersedia`
-   `disewa`
-   `perawatan`
-   `ditolak`

Relasi:

-   User/pemilik memiliki banyak motor.
-   Motor dimiliki oleh satu pemilik.

------------------------------------------------------------------------

## 4.3 TARIF_RENTALS

Tabel: `tarif_rentals`

Field:

-   `id` PK
-   `motor_id` FK → `motors.id`
-   `tarif_harian`
-   `tarif_mingguan`
-   `tarif_bulanan`
-   `created_at`
-   `updated_at`

Satu motor memiliki tarif rental yang digunakan sistem untuk menghitung
biaya penyewaan.

Jika aturan bisnis menetapkan satu tarif aktif per motor, `motor_id`
dapat dibuat unique.

------------------------------------------------------------------------

## 4.4 PENYEWAANS

Tabel: `penyewaans`

Field:

-   `id` PK
-   `penyewa_id` FK → `users.id`
-   `motor_id` FK → `motors.id`
-   `tanggal_mulai`
-   `tanggal_selesai`
-   `tipe_durasi`
-   `harga`
-   `status`
-   `created_at`
-   `updated_at`

`tipe_durasi`:

-   `harian`
-   `mingguan`
-   `bulanan`

Status penyewaan minimal:

-   `pending`
-   `dikonfirmasi`
-   `berjalan`
-   `selesai`
-   `dibatalkan`

Catatan penting:

`harga` harus menyimpan harga yang berlaku pada saat transaksi dibuat.
Jangan hanya mengambil tarif saat laporan dibuat karena tarif dapat
berubah.

Relasi:

-   satu penyewa dapat memiliki banyak penyewaan
-   satu motor dapat memiliki banyak histori penyewaan
-   satu penyewaan dimiliki satu penyewa
-   satu penyewaan menggunakan satu motor

------------------------------------------------------------------------

## 4.5 TRANSAKSIS

Tabel: `transaksis`

Field:

-   `id` PK
-   `penyewaan_id` FK → `penyewaans.id`
-   `jumlah`
-   `metode_pembayaran`
-   `status`
-   `tanggal`
-   `created_at`
-   `updated_at`

Status pembayaran minimal:

-   `pending`
-   `dibayar`
-   `ditolak`
-   `dikembalikan`

Metode pembayaran dapat menggunakan:

-   `cash`
-   `transfer`
-   atau metode lain yang memang diperlukan project.

------------------------------------------------------------------------

## 4.6 BAGI_HASILS

Tabel: `bagi_hasils`

Field:

-   `id` PK
-   `penyewaan_id` FK → `penyewaans.id`
-   `persentase_hasil_pemilik`
-   `persentase_hasil_admin`
-   `nominal_pemilik`
-   `nominal_admin`
-   `settled_at`
-   `tanggal`
-   `created_at`
-   `updated_at`

Tujuan tabel:

Menyimpan hasil pembagian pendapatan dari penyewaan kepada pemilik dan
admin.

Contoh jika total pembayaran Rp300.000 dan pembagian:

-   pemilik 80% = Rp240.000
-   admin 20% = Rp60.000

Maka sistem menyimpan persentase dan nominal tersebut.

Jangan meng-hardcode persentase di banyak controller. Letakkan aturan
pembagian pada konfigurasi/logic bisnis yang terpusat sehingga mudah
diubah.

------------------------------------------------------------------------

# 5. CATATAN PENAMAAN ERD

Pada gambar ERD, beberapa field terlihat menggunakan nama
`pemesanan_id`, terutama pada `transaksi` dan `bagi_hasil`.

Requirement juga menggunakan istilah `penyewaan`.

Gunakan satu istilah secara konsisten dalam implementasi.

Rekomendasi canonical name:

`penyewaan_id`

Jika project sudah terlanjur menggunakan `pemesanan_id`, jangan membuat
dua FK untuk hal yang sama. Lakukan migration rename secara aman.

------------------------------------------------------------------------

# 6. RELASI DATABASE

Relasi utama:

``` text
users
  |
  | 1:N
  v
motors
  |
  | 1:1 / 1:N sesuai aturan tarif
  v
tarif_rentals

users
  |
  | 1:N
  v
penyewaans
  |
  | N:1
  v
motors

penyewaans
  |
  | 1:N
  v
transaksis

penyewaans
  |
  | 1:1
  v
bagi_hasils
```

Secara bisnis:

``` text
PEMILIK
   |
   +---- MOTORS
             |
             +---- TARIF_RENTALS
             |
             +---- PENYEWAANS
                       |
                       +---- TRANSAKSIS
                       |
                       +---- BAGI_HASILS

PENYEWA
   |
   +---- PENYEWAANS
```

`admin_id` tidak perlu ditambahkan ke tabel `motors`, karena admin hanya
memverifikasi/mengelola motor.

`pemilik_id` juga tidak perlu ditambahkan ke `penyewaans`, karena
pemilik dapat diketahui melalui
`penyewaans.motor_id -> motors.pemilik_id`.

------------------------------------------------------------------------

# 7. ALUR BISNIS UTAMA

## 7.1 Registrasi

``` text
User membuka registrasi
        |
        v
Mengisi nama, email, no_tlpn, password, role
        |
        v
Validasi
        |
        v
Simpan ke users
        |
        v
Login
```

Role harus dibatasi agar user tidak dapat memilih role admin secara
bebas jika sistem membutuhkan admin yang dibuat oleh administrator.

------------------------------------------------------------------------

# 8. LOGIN

``` text
MULAI
  |
  v
LOGIN
  |
  v
VALID?
  |---- TIDAK ----> tampilkan pesan login/register
  |
  YA
  |
  v
DASHBOARD SESUAI ROLE
  |
  +---- ADMIN
  |
  +---- PEMILIK
  |
  +---- PENYEWA
```

Setelah logout:

``` text
LOGOUT
  |
  v
SELESAI
```

------------------------------------------------------------------------

# 9. ALUR PEMILIK

``` text
Pemilik login
     |
     v
Dashboard Pemilik
     |
     +--> Daftar motor
     |
     +--> Tambah motor
     |
     +--> Upload foto
     |
     +--> Upload dokumen kepemilikan
     |
     +--> Menunggu verifikasi admin
     |
     +--> Melihat status motor
     |
     +--> Melihat transaksi
     |
     +--> Melihat pendapatan
     |
     +--> Melihat bagi hasil
```

Motor baru tidak langsung tersedia untuk disewa sebelum diverifikasi
admin.

------------------------------------------------------------------------

# 10. ALUR ADMIN

``` text
Admin login
    |
    v
Dashboard Admin
    |
    +--> Kelola user
    |
    +--> Kelola/verifikasi motor
    |
    +--> Tentukan tarif rental
    |
    +--> Kelola penyewaan
    |
    +--> Kelola pembayaran
    |
    +--> Konfirmasi pengembalian
    |
    +--> Hitung/catat bagi hasil
    |
    +--> Lihat laporan
```

------------------------------------------------------------------------

# 11. ALUR PENYEWAAN

``` text
Penyewa login
     |
     v
Lihat motor tersedia
     |
     v
Pilih motor
     |
     v
Pilih durasi
     |
     v
Sistem membaca tarif
     |
     v
Hitung biaya
     |
     v
Buat penyewaan
     |
     v
Pembayaran
     |
     v
Admin melakukan konfirmasi
     |
     v
Status penyewaan = dikonfirmasi / berjalan
     |
     v
Status motor = disewa
```

------------------------------------------------------------------------

# 12. PERHITUNGAN BIAYA

Gunakan tarif sesuai tipe durasi.

Contoh:

``` text
harian:
jumlah_hari × tarif_harian

mingguan:
jumlah_minggu × tarif_mingguan

bulanan:
jumlah_bulan × tarif_bulanan
```

Sistem harus memvalidasi tanggal mulai dan tanggal selesai.

Jangan mengizinkan tanggal selesai lebih kecil dari tanggal mulai.

------------------------------------------------------------------------

# 13. ALUR PEMBAYARAN

``` text
Penyewa membuat penyewaan
        |
        v
Sistem menentukan total harga
        |
        v
Penyewa melakukan pembayaran
        |
        v
Data disimpan ke TRANSAKSIS
        |
        v
Status pembayaran
        |
        +--> pending
        |
        +--> dibayar
        |
        +--> ditolak
```

Admin dapat memverifikasi/mengelola pembayaran sesuai kebutuhan
aplikasi.

------------------------------------------------------------------------

# 14. ALUR PENGEMBALIAN MOTOR

Pengembalian harus menjadi proses bisnis tersendiri.

``` text
Masa sewa habis
      |
      v
Penyewa mengembalikan motor
      |
      v
Admin melakukan pemeriksaan
      |
      v
Admin konfirmasi pengembalian
      |
      v
Update penyewaan
status = selesai
      |
      v
Update motor
status = tersedia
      |
      v
Sistem mencatat pendapatan
      |
      v
Proses bagi hasil
```

Jangan hanya membuat tombol "selesai" tanpa mengubah status motor.

------------------------------------------------------------------------

# 15. ALUR BAGI HASIL

Setelah transaksi/penyewaan memenuhi kondisi yang ditentukan untuk
settlement:

``` text
Total pembayaran
       |
       v
Hitung persentase pemilik
       |
       v
Hitung persentase admin
       |
       v
Simpan BAGI_HASILS
       |
       +----> laporan pemilik
       |
       +----> laporan admin
```

Pastikan:

``` text
persentase_pemilik + persentase_admin = 100%
```

Nominal:

``` text
nominal_pemilik = total_pendapatan × persentase_pemilik / 100

nominal_admin = total_pendapatan × persentase_admin / 100
```

Gunakan transaksi database (`DB::transaction`) ketika proses settlement
mengubah beberapa data sekaligus.

------------------------------------------------------------------------

# 16. DFD LEVEL 0

Entitas eksternal:

-   Admin/Petugas
-   Pemilik
-   Penyewa

Proses utama:

``` text
SISTEM PENYEWAAN MOTOR
```

Input/output utama:

### Penyewa → Sistem

-   registrasi
-   login
-   melihat motor
-   memilih motor
-   memilih durasi
-   membuat penyewaan
-   pembayaran
-   pengembalian
-   melihat status
-   melihat riwayat

### Sistem → Penyewa

-   daftar motor
-   detail motor
-   tarif
-   total biaya
-   status pembayaran
-   status penyewaan
-   status pengembalian
-   riwayat

### Pemilik → Sistem

-   registrasi
-   login
-   data motor
-   foto motor
-   dokumen kepemilikan

### Sistem → Pemilik

-   status motor
-   transaksi
-   pendapatan
-   bagi hasil

### Admin → Sistem

-   verifikasi motor
-   tarif
-   konfirmasi penyewaan
-   pembayaran
-   pengembalian
-   bagi hasil

### Sistem → Admin

-   data user
-   data motor
-   data penyewaan
-   data pembayaran
-   laporan
-   pendapatan
-   grafik

------------------------------------------------------------------------

# 17. DFD LEVEL 1

Gunakan proses berikut:

``` text
1.0 Kelola Data Master
2.0 Proses Penyewaan
3.0 Kelola Pembayaran
4.0 Proses Pengembalian
5.0 Konfirmasi Pengembalian
6.0 Laporan Bagi Hasil
```

Data Store:

``` text
D1 Data Users
D2 Data Motors
D3 Data Tarif Rental
D4 Data Penyewaan
D5 Data Transaksi
D6 Data Bagi Hasil
```

Alur ringkas:

``` text
ADMIN
  |
  v
1.0 KELOLA DATA MASTER
  |
  +----> D1 USERS
  +----> D2 MOTORS
  +----> D3 TARIF RENTAL

PENYEWA
  |
  v
2.0 PROSES PENYEWAAN
  |
  +----> D2 MOTORS
  +----> D3 TARIF RENTAL
  +----> D4 PENYEWAAN

PENYEWA
  |
  v
3.0 KELOLA PEMBAYARAN
  |
  +----> D5 TRANSAKSI

PENYEWA
  |
  v
4.0 PROSES PENGEMBALIAN
  |
  +----> D4 PENYEWAAN

ADMIN
  |
  v
5.0 KONFIRMASI PENGEMBALIAN
  |
  +----> D2 MOTORS
  +----> D4 PENYEWAAN

ADMIN
  |
  v
6.0 LAPORAN BAGI HASIL
  |
  +----> D6 BAGI HASIL
```

------------------------------------------------------------------------

# 18. MENU / UI BERDASARKAN ROLE

## ADMIN

Menu:

-   Dashboard
-   Data User
-   Data Motor
-   Verifikasi Motor
-   Tarif Rental
-   Penyewaan
-   Pembayaran
-   Pengembalian
-   Bagi Hasil
-   Laporan
-   Logout

Dashboard dapat menampilkan:

-   jumlah motor
-   motor tersedia
-   motor disewa
-   jumlah penyewaan
-   total pembayaran
-   total bagian pemilik
-   total bagian admin
-   grafik penyewaan per periode

------------------------------------------------------------------------

## PEMILIK

Menu:

-   Dashboard
-   Motor Saya
-   Tambah Motor
-   Status Motor
-   Transaksi
-   Pendapatan
-   Bagi Hasil
-   Logout

Dashboard:

-   jumlah motor
-   motor tersedia
-   motor disewa
-   pendapatan
-   total bagi hasil

------------------------------------------------------------------------

## PENYEWA

Menu:

-   Dashboard
-   Cari Motor
-   Motor Tersedia
-   Penyewaan Saya
-   Pembayaran
-   Riwayat Penyewaan
-   Status Pengembalian
-   Logout

------------------------------------------------------------------------

# 19. ROUTE / CONTROLLER YANG DISARANKAN

Gunakan struktur Laravel yang jelas.

Contoh controller:

``` text
AuthController
UserController
MotorController
TarifRentalController
PenyewaanController
TransaksiController
PengembalianController
BagiHasilController
LaporanController
DashboardController
```

Nama dapat disesuaikan dengan struktur Laravel yang digunakan.

Gunakan middleware role:

``` text
/admin/*
/pemilik/*
/penyewa/*
```

Jangan hanya menyembunyikan tombol berdasarkan UI. Authorization harus
tetap dilakukan di server/controller/policy.

------------------------------------------------------------------------

# 20. MODEL ELOQUENT

Model minimal:

``` text
User
Motor
TarifRental
Penyewaan
Transaksi
BagiHasil
```

Relasi yang diharapkan:

``` php
User::hasMany(Motor::class, 'pemilik_id');

User::hasMany(Penyewaan::class, 'penyewa_id');

Motor::belongsTo(User::class, 'pemilik_id');

Motor::hasOne(TarifRental::class);

Motor::hasMany(Penyewaan::class);

Penyewaan::belongsTo(User::class, 'penyewa_id');

Penyewaan::belongsTo(Motor::class);

Penyewaan::hasMany(Transaksi::class);

Penyewaan::hasOne(BagiHasil::class);
```

Sesuaikan relasi jika aturan bisnis akhirnya menetapkan satu penyewaan
dapat memiliki beberapa transaksi.

------------------------------------------------------------------------

# 21. STATUS YANG HARUS KONSISTEN

Gunakan status yang konsisten antara database, Model, Controller, dan
Blade.

Motor:

``` text
pending
tersedia
disewa
perawatan
ditolak
```

Penyewaan:

``` text
pending
dikonfirmasi
berjalan
selesai
dibatalkan
```

Pembayaran:

``` text
pending
dibayar
ditolak
dikembalikan
```

Jangan menggunakan variasi seperti:

``` text
selesai
Selesai
SELESAI
finish
done
```

secara bersamaan.

------------------------------------------------------------------------

# 22. VALIDASI PENTING

## Motor

-   merk wajib
-   tipe_cc harus 100/125/150
-   nomor plat wajib dan unik jika aturan bisnis mengharuskan
-   foto wajib sesuai kebutuhan
-   dokumen kepemilikan wajib
-   pemilik harus merupakan user dengan role `pemilik`

## Penyewaan

-   motor harus tersedia
-   penyewa harus login
-   tanggal mulai valid
-   tanggal selesai \>= tanggal mulai
-   durasi valid
-   harga dihitung sistem
-   jangan izinkan motor yang sudah disewa untuk dipesan pada periode
    yang bentrok

## Pembayaran

-   jumlah \> 0
-   penyewaan valid
-   status pembayaran valid

## Bagi hasil

-   total persentase = 100%
-   nominal tidak negatif
-   penyewaan harus valid
-   hindari settlement ganda untuk transaksi yang sama

------------------------------------------------------------------------

# 23. ATURAN STATUS MOTOR

Alur utama:

``` text
motor didaftarkan
      |
      v
pending
      |
      +---- ditolak
      |
      v
tersedia
      |
      v
disewa
      |
      v
pengembalian dikonfirmasi
      |
      v
tersedia
```

Jika motor masuk perawatan:

``` text
tersedia
   |
   v
perawatan
   |
   v
tersedia
```

Motor yang `pending`, `ditolak`, `disewa`, atau `perawatan` tidak boleh
ditampilkan sebagai motor yang dapat disewa.

------------------------------------------------------------------------

# 24. LAPORAN

## Laporan Penyewa

-   riwayat penyewaan
-   status aktif
-   pembayaran
-   status pengembalian

## Laporan Pemilik

-   daftar motor disewakan
-   pendapatan per motor
-   total bagi hasil

## Laporan Admin

-   jumlah motor terdaftar
-   jumlah motor disewa
-   total pendapatan
-   bagian pemilik
-   bagian admin
-   grafik penyewaan harian
-   grafik penyewaan mingguan
-   grafik penyewaan bulanan
-   laporan pembayaran

------------------------------------------------------------------------

# 25. URUTAN IMPLEMENTASI LARAVEL

AI Agent harus mengerjakan project secara bertahap:

## Tahap 1 --- Database

Buat:

``` text
users
motors
tarif_rentals
penyewaans
transaksis
bagi_hasils
```

Kemudian:

``` bash
php artisan migrate
```

Pastikan foreign key benar.

## Tahap 2 --- Model

Buat model dan relationship Eloquent.

## Tahap 3 --- Authentication

Implementasikan:

-   login
-   register
-   logout
-   role

## Tahap 4 --- Authorization

Batasi halaman berdasarkan role.

## Tahap 5 --- CRUD Master

Implementasikan:

-   user
-   motor
-   tarif rental

## Tahap 6 --- Penyewaan

Implementasikan:

-   daftar motor
-   detail motor
-   pilih durasi
-   hitung biaya
-   buat penyewaan
-   status penyewaan

## Tahap 7 --- Pembayaran

Implementasikan transaksi pembayaran dan statusnya.

## Tahap 8 --- Pengembalian

Implementasikan:

-   pengajuan/penanda pengembalian
-   pemeriksaan admin
-   konfirmasi
-   update status motor
-   update status penyewaan

## Tahap 9 --- Bagi Hasil

Implementasikan:

-   perhitungan persentase
-   nominal pemilik
-   nominal admin
-   settlement
-   histori

## Tahap 10 --- Dashboard dan Laporan

Buat dashboard berbeda berdasarkan role.

## Tahap 11 --- Testing

Uji setiap flow.

------------------------------------------------------------------------

# 26. TESTING WAJIB

Minimal buat test manual untuk:

### Authentication

1.  Login sukses
2.  Password salah
3.  Email salah
4.  Register sukses
5.  Akses admin oleh penyewa harus ditolak

### Motor

1.  Pemilik tambah motor
2.  Data tidak lengkap
3.  Tipe CC tidak valid
4.  Admin verifikasi motor
5.  Admin menolak motor
6.  Motor tersedia setelah diverifikasi

### Penyewaan

1.  Penyewa melihat motor tersedia
2.  Penyewa memilih motor
3.  Penyewa memilih durasi
4.  Sistem menghitung harga
5.  Penyewaan berhasil
6.  Motor berubah menjadi disewa
7.  Motor yang sedang disewa tidak dapat dipesan ulang

### Pembayaran

1.  Pembayaran valid
2.  Jumlah salah
3.  Status pembayaran berubah
4.  Admin dapat melihat transaksi

### Pengembalian

1.  Penyewa mengembalikan motor
2.  Admin melihat permintaan
3.  Admin mengonfirmasi
4.  Penyewaan menjadi selesai
5.  Motor menjadi tersedia
6.  Bagi hasil tercatat

### Bagi hasil

1.  Persentase benar
2.  Nominal pemilik benar
3.  Nominal admin benar
4.  Total persentase = 100%
5.  Tidak terjadi settlement ganda

------------------------------------------------------------------------

# 27. CONTOH SKENARIO END-TO-END

Gunakan skenario ini untuk memastikan seluruh sistem terhubung:

``` text
Pemilik register
      |
      v
Pemilik login
      |
      v
Tambah motor
      |
      v
Admin verifikasi motor
      |
      v
Admin menentukan tarif
      |
      v
Motor menjadi tersedia
      |
      v
Penyewa login
      |
      v
Melihat motor
      |
      v
Memilih motor + durasi
      |
      v
Sistem menghitung harga
      |
      v
Penyewa membuat penyewaan
      |
      v
Penyewa membayar
      |
      v
Admin mengonfirmasi pembayaran/sewa
      |
      v
Motor menjadi disewa
      |
      v
Masa sewa selesai
      |
      v
Motor dikembalikan
      |
      v
Admin konfirmasi pengembalian
      |
      v
Motor menjadi tersedia
      |
      v
Penyewaan menjadi selesai
      |
      v
Sistem menghitung bagi hasil
      |
      +----> Pemilik melihat pendapatan
      |
      +----> Admin melihat pendapatan
      |
      v
Laporan tersimpan/ditampilkan
```

------------------------------------------------------------------------

# 28. OUTPUT YANG DIHARAPKAN DARI AI AGENT

AI Agent harus menghasilkan project Laravel yang minimal memiliki:

``` text
app/
  Models/
    User.php
    Motor.php
    TarifRental.php
    Penyewaan.php
    Transaksi.php
    BagiHasil.php

  Http/
    Controllers/
      AuthController.php
      MotorController.php
      TarifRentalController.php
      PenyewaanController.php
      TransaksiController.php
      PengembalianController.php
      BagiHasilController.php
      LaporanController.php
      DashboardController.php

database/
  migrations/
    users
    motors
    tarif_rentals
    penyewaans
    transaksis
    bagi_hasils

resources/
  views/
    layouts/
    auth/
    admin/
    pemilik/
    penyewa/

routes/
  web.php
```

Struktur aktual boleh disesuaikan dengan versi Laravel, tetapi pemisahan
tanggung jawab harus tetap jelas.

------------------------------------------------------------------------

# 29. PRIORITAS IMPLEMENTASI

Prioritaskan dalam urutan:

``` text
DATABASE
   ↓
MODEL + RELATIONSHIP
   ↓
AUTHENTICATION
   ↓
ROLE AUTHORIZATION
   ↓
MOTOR
   ↓
TARIF
   ↓
PENYEWAAN
   ↓
PEMBAYARAN
   ↓
PENGEMBALIAN
   ↓
BAGI HASIL
   ↓
LAPORAN
   ↓
UI/UX
   ↓
TESTING
```

Jangan membuat dashboard/laporan yang mengambil data palsu atau
hardcoded. Semua statistik harus berasal dari database.

------------------------------------------------------------------------

# 30. PRINSIP IMPLEMENTASI

Tujuan utama bukan sekadar membuat CRUD.

Sistem harus benar-benar mengikuti alur:

``` text
PEMILIK
  ↓
DAFTAR MOTOR
  ↓
ADMIN VERIFIKASI
  ↓
ADMIN TENTUKAN TARIF
  ↓
MOTOR TERSEDIA
  ↓
PENYEWA MEMESAN
  ↓
PEMBAYARAN
  ↓
ADMIN KONFIRMASI
  ↓
MOTOR DISEWA
  ↓
PENGEMBALIAN
  ↓
ADMIN KONFIRMASI
  ↓
MOTOR TERSEDIA KEMBALI
  ↓
BAGI HASIL
  ↓
LAPORAN
```

Setiap perubahan status harus mempunyai alasan bisnis yang jelas dan
tersimpan pada data yang relevan.

# SELESAI
