# Panduan Setup & Manual Instalasi
## JMC Sistem Informasi Mini (Portal HRD)

Dokumen ini berisi panduan lengkap untuk melakukan instalasi, konfigurasi, dan menjalankan aplikasi Portal HRD JMC secara lokal di mesin pengembangan Anda.

---

## 1. Arsitektur Sistem

Aplikasi ini dibangun menggunakan arsitektur modern yang memisahkan Backend dan Frontend secara independen:
*   **Backend**: Laravel 11 (RESTful API, JWT Authentication, Custom Captcha & OTP Mailer, Background Job Queue, PDF/Excel Exports).
*   **Frontend**: Nuxt.js 4 (State management, Client-side session timeout, Apexcharts Dashboard, Autocomplete/Autosuggest, Multi-filtering, Bulk Actions).
*   **Database**: MySQL / MariaDB.
*   **Email Tracker**: Mailpit (untuk menangkap email OTP secara offline di server lokal).

---

## 2. Prasyarat Lingkungan (Prerequisites)

Sebelum memulai, pastikan mesin Anda telah terpasang:
1.  **PHP >= 8.3** dengan ekstensi berikut aktif: `pdo_mysql`, `gd` (untuk Captcha), `zip`, `mbstring`.
2.  **Composer >= 2.x** (Dependency manager PHP).
3.  **Node.js >= 22.x** (LTS) & **npm** (Dependency manager JavaScript).
4.  **MySQL** atau **MariaDB Server** (Disarankan menggunakan **Laragon** untuk mempermudah manajemen MySQL, PHP, dan Mailpit sekaligus).
5.  **Mailpit** (Bawaan Laragon. Jika tidak menggunakan Laragon, silakan unduh binary Mailpit secara terpisah).

---

## 3. Panduan Setup Backend (Laravel)

Ikuti langkah-langkah di bawah ini untuk mengaktifkan RESTful API:

### Langkah 3.1: Install Dependencies
Masuk ke direktori `backend` dan jalankan instalasi composer:
```bash
cd backend
composer install
```

### Langkah 3.2: Konfigurasi Environment File
Salin file konfigurasi `.env.example` menjadi `.env`:
```bash
copy .env.example .env
```
Buka file `.env` yang baru dibuat dan sesuaikan konfigurasi database Anda. Contoh konfigurasi default menggunakan MySQL lokal:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jmc_sistem_informasi_mini
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi SMTP untuk Mailpit (OTP Mailer)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@jmc-mini.local"
MAIL_FROM_NAME="JMC Mini Portal"
```

### Langkah 3.3: Generate App Key
Generate key enkripsi unik untuk Laravel:
```bash
php artisan key:generate
```

### Langkah 3.4: Migrasi & Seeding Database
Pastikan server database MySQL Anda sudah aktif dan database `jmc_sistem_informasi_mini` telah dibuat.
Jalankan migrasi tabel beserta pengisian data awal (seeder):
```bash
php artisan migrate:fresh --seed
```

### Langkah 3.5: Buat Symlink Storage
Agar foto profil pegawai yang diunggah dapat diakses langsung oleh frontend, buat link direktori public:
```bash
php artisan storage:link
```

### Langkah 3.6: Jalankan Laravel Server
Aktifkan server web lokal Laravel:
```bash
php artisan serve --port=8000
```
Server backend akan aktif di **`http://localhost:8000`**.  
*   **Dokumentasi Swagger**: Anda dapat mengakses visualisasi dokumentasi API Swagger di **`http://localhost:8000/api/documentation`**.

### Langkah 3.7: Jalankan Queue Worker (PENTING!)
Proses import rekap absensi dari file Excel dilakukan secara asinkron (background process) agar tidak membebani server. **Anda wajib menjalankan queue worker** agar job asinkron ini diproses:
```bash
php artisan queue:work
```

---

## 4. Panduan Setup Frontend (Nuxt.js)

Ikuti langkah-langkah di bawah ini untuk mengaktifkan UI Aplikasi:

### Langkah 4.1: Install Dependencies
Masuk ke direktori `frontend` dan jalankan instalasi paket node:
```bash
cd ../frontend
npm install
```

### Langkah 4.2: Konfigurasi Environment File
Salin file konfigurasi `.env.example` menjadi `.env`:
```bash
copy .env.example .env
```
Pastikan file `.env` mengarah ke URL API Laravel:
```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_APP_NAME="Portal HRD JMC"
```

### Langkah 4.3: Jalankan Nuxt Server
Mulai server pembangunan Nuxt lokal:
```bash
npm run dev
```
Server frontend akan aktif di **`http://localhost:3000`**. Buka alamat tersebut di browser Anda.

---

## 5. Detail Akun Pengujian (Seeded Accounts)

Aplikasi ini dilengkapi dengan data seeder untuk mensimulasikan sistem RBAC (Role Based Access Control).  
*Semua akun di bawah menggunakan password default:* **`password`**

| Nama Pengguna | Role | Username / Email | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Superadmin** | Superadmin | `superadmin` / `superadmin@jmc-mini.local` | Manajemen User Sistem, Riwayat Logs Audit |
| **Andri Eko Prasetyo** | Manager HRD | `manager` / `andri.eko@jmc-mini.local` | Dashboard Charts, Read-only Pegawai, Read-only Presensi |
| **Yessi Maria** | Admin HRD | `admin` / `yessi.maria@jmc-mini.local` | CRUD Pegawai, Upload/Import Presensi, Konfigurasi Tarif & Perhitungan Tunjangan |

---

## 6. Uji Coba Alur Autentikasi (OTP & Mailpit)

1.  Buka web browser dan akses halaman login di **`http://localhost:3000/Login`**.
2.  Masukkan Username (contoh: `admin`) dan Password (`password`).
3.  Ketik kode Captcha visual yang tertera pada kotak gambar (jika buram, klik tombol reload di sampingnya). Klik **Login**.
4.  Jika kredensial benar, layar kedua untuk memasukkan **OTP 4-digit** akan muncul.
5.  Untuk mendapatkan OTP, buka dashboard **Mailpit** di browser Anda: **`http://localhost:8025`**.
6.  Salin 4-digit OTP dari email terbaru yang ditangkap oleh Mailpit, lalu masukkan ke form login Nuxt.
7.  **Inactivity Timeout**: Jika Anda membiarkan aplikasi terbuka tanpa interaksi keyboard atau mouse selama 3 menit, sistem akan otomatis melakukan logout paksa dan menghapus session token (kecuali kotak "Remember me" dicentang saat login).

---

## 7. Aturan Bisnis yang Diterapkan

### A. Aturan Presensi Harian
*   Jam kerja normal adalah pukul **08:00 s.d 17:00** (istirahat 12:00 - 13:00) dengan total **8 jam kerja bersih**.
*   **Beda Lokasi**: Jika lokasi check-in tidak sama dengan lokasi check-out, kehadiran hari tersebut dihitung **0 jam (Absen)**.
*   **Keterlambatan <= 15 menit** (check-in <= 08:15): Dianggap masuk normal (1.0 hari), status **"Terpenuhi"** jika checkout >= 17:00.
*   **Keterlambatan > 15 menit** (check-in > 08:15): Dihitung masuk setengah hari (**0.5 hari**), dengan syarat jam kerja bersih kumulatif hari tersebut tetap minimal **8 jam** (misal pulang telat untuk mengganti waktu). Jika kurang dari 8 jam, status kehadirannya otomatis menjadi **"Tidak terpenuhi"** dan dihitung absen (**0.0 hari**).

### B. Aturan Kalkulasi Tunjangan Transport
*   Hanya diberikan kepada pegawai dengan status kerja tetap (**PKWTT**).
*   Pegawai harus memiliki minimal **19 hari kerja efektif** berstatus **"Terpenuhi"** pada bulan berjalan.
*   Jarak rumah-kantor minimal **5 km** dan maksimal **25 km**. Jarak di atas 25 km akan dibatasi (capped) menjadi 25 km. Jarak <= 5 km tidak mendapat tunjangan.
*   Pembulatan desimal jarak KM menggunakan aturan: angka desimal di bawah 0.5 dibulatkan ke bawah, desimal 0.5 ke atas dibulatkan ke atas (**ROUND_HALF_UP**).
*   Nominal kalkulasi: `Tarif Dasar x Jarak KM Bulat x Jumlah Hari Kehadiran Terpenuhi`.
