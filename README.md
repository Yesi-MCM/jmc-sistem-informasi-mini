# JMC Sistem Informasi Mini (Portal HRD)

Sistem Informasi Mini Portal HRD ini dikembangkan menggunakan **Laravel (Backend)** dan **Nuxt.js (Frontend)** dengan database **MySQL/MariaDB**. Portal ini mengelola database pegawai, audit activity logs, rekapitulasi presensi bulanan (melalui import Excel), pengaturan tunjangan transport, serta perhitungan otomatis tunjangan bulanan bagi pegawai berstatus tetap (PKWTT).

---

## Prasyarat System

Pastikan perangkat Anda sudah terinstal:
- **PHP >= 8.3**
- **Composer >= 2.x**
- **Node.js >= 22.x (LTS)**
- **MySQL / MariaDB** (Disarankan Laragon untuk mempermudah)
- **Mailpit** (Bawaan Laragon untuk menangkap email OTP di port SMTP `1025`)

---

## Panduan Setup Backend (Laravel)

1. Masuk ke folder backend:
   ```bash
   cd backend
   ```

2. Salin file environment:
   ```bash
   copy .env.example .env
   ```

3. Sesuaikan konfigurasi `.env` untuk database dan Mailpit. File `.env` bawaan sudah kami set ke:
   - Database: `jmc_sistem_informasi_mini` (username: `root`, password: ``)
   - Mailer: `smtp`, host: `127.0.0.1`, port: `1025` (Mailpit)

4. Instal dependencies backend:
   ```bash
   composer install
   ```

5. Generate application key:
   ```bash
   php artisan key:generate
   ```

6. Jalankan migrasi database dan seed data awal:
   ```bash
   php artisan migrate:fresh --seed
   ```

7. Buat symlink storage agar foto pegawai dapat diakses:
   ```bash
   php artisan storage:link
   ```

8. Jalankan server Laravel:
   ```bash
   php artisan serve
   ```
   Server backend akan berjalan di: **`http://localhost:8000`**
   Dokumentasi API (Swagger) dapat diakses di: **`http://localhost:8000/api/documentation`**

---

## Panduan Setup Frontend (Nuxt.js)

1. Masuk ke folder frontend:
   ```bash
   cd ../frontend
   ```

2. Salin file environment:
   ```bash
   copy .env.example .env
   ```
   Pastikan variabel `NUXT_PUBLIC_API_BASE` mengarah ke backend:
   ```env
   NUXT_PUBLIC_API_BASE=http://localhost:8000/api
   ```

3. Instal dependencies Node:
   ```bash
   npm install
   ```

4. Jalankan server pembangunan Nuxt:
   ```bash
   npm run dev
   ```
   Server frontend akan berjalan di: **`http://localhost:3000`**

---

## Detail Akun Seeder & Demo

Terdapat 3 akun yang dibuat otomatis melalui seeder untuk pengujian (password default: **`password`**):

1. **Superadmin**
   - Username/Email/No.HP: `superadmin` / `superadmin@jmc-mini.local` / `+6281111111111`
   - Fungsi: Mengelola Akun User, Melihat RBAC, Meninjau Audit Logs.

2. **Manager HRD** (Andri Eko Prasetyo)
   - Username/Email/No.HP: `manager` / `andri.eko@jmc-mini.local` / `+6282218458888`
   - Fungsi: Meninjau Dashboard Manager (Charts & Widgets), Melihat Data Pegawai (Read-only), Melihat Presensi, Melihat Hasil Kalkulasi Tunjangan.

3. **Admin HRD** (Yessi Maria)
   - Username/Email/No.HP: `admin` / `yessi.maria@jmc-mini.local` / `+6281234567890`
   - Fungsi: CRUD Pegawai, Mengunduh PDF/Excel Pegawai, Upload/Import Presensi via Excel, Mengelola Setting Tunjangan, Menjalankan "Hitung Tunjangan" kalkulasi transport bulanan.

---

## Alur Autentikasi (Captcha & OTP)
1. **Langkah 1**: Input credentials + captcha. Jika valid, sistem mengirim 4-digit OTP ke email yang terhubung.
2. **Buka Mailpit Dashboard** di browser: `http://localhost:8025` untuk menyalin kode OTP.
3. **Langkah 2**: Masukkan 4-digit OTP untuk mendapatkan token akses JWT utama.
4. **Session Timeout**: Sesi akan ditutup otomatis setelah 3 menit tidak ada aktivitas keyboard/mouse (kecuali checkbox 'Remember me' dicentang saat login).
