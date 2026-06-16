# Panduan Deployment ke Vercel (Backend & Frontend)
## JMC Sistem Informasi Mini (Portal HRD)

Panduan ini menjelaskan langkah-langkah untuk melakukan deployment **Backend Laravel** dan **Frontend Nuxt.js** secara terpisah pada platform **Vercel** menggunakan database **TiDB Cloud Serverless** dan **Google Drive Storage**.

---

## 1. Arsitektur Deployment (Serverless)

Karena Vercel adalah platform serverless, aplikasi Laravel monolitik dideploy menggunakan runtime komunitas PHP `vercel-php` untuk mengeksekusi kode PHP dan mengarahkan seluruh request web melalui entri serverless.
*   **Vercel Project 1 (Backend API)**: Menjalankan Laravel menggunakan runtime serverless PHP `vercel-php`.
*   **Vercel Project 2 (Frontend UI)**: Menjalankan Nuxt.js secara serverless.
*   **TiDB Cloud**: Database cloud serverless (MySQL-compatible) yang diakses dengan SSL.
*   **Google Drive**: Persistent cloud storage untuk foto pegawai dan data presensi (karena disk lokal Vercel bersifat *Read-Only* / ephemeral).

---

## 2. Setup Database TiDB Cloud (Serverless)

### Langkah 2.1: Buat Kluster di TiDB Cloud
1.  Daftar atau masuk ke [TiDB Cloud](https://pingcap.com/products/tidb-cloud).
2.  Buat kluster baru dengan memilih tipe **Serverless** (Gratis).
3.  Buat database baru di dalamnya dengan nama `jmc_sistem_informasi_mini`.
4.  Pada panel **Connection**, klik **Connect**, lalu pilih **Laravel** atau **General Connection** untuk melihat informasi host, port, username, dan password.

### Langkah 2.2: Catat Parameter Koneksi
*   `DB_CONNECTION`: `mysql`
*   `DB_HOST`: `gateway01.xxxx.shared.aws.tidbcloud.com`
*   `DB_PORT`: `4000` (port default TiDB Cloud)
*   `DB_DATABASE`: `jmc_sistem_informasi_mini`
*   `DB_USERNAME`: `xxxxxx.root`
*   `DB_PASSWORD`: `PasswordKlusterAnda`

### Langkah 2.3: Konfigurasi SSL/TLS (Wajib)
TiDB Cloud Serverless mewajibkan koneksi aman menggunakan TLS/SSL. Untuk mengaktifkannya di Laravel pada lingkungan produksi Vercel, tambahkan variabel berikut pada **Environment Variables Vercel**:
*   **Key**: `MYSQL_ATTR_SSL_CA`
*   **Value**: `/etc/ssl/certs/ca-certificates.crt` (Path standar sertifikat CA pada runtime Vercel Linux)

---

## 3. Setup Persistent Storage (Google Drive API)

Karena disk Vercel bersifat read-only (kecuali folder `/tmp` yang terhapus berkala), foto pegawai harus disimpan secara persisten di Google Drive.

### Langkah 3.1: Setup Google Cloud Console
1.  Buka [Google Cloud Console](https://console.cloud.google.com/).
2.  Buat project baru dan aktifkan **Google Drive API**.
3.  Konfigurasikan **OAuth Consent Screen** (User Type: **External**). Masukkan email support Anda.
4.  Masuk ke menu **Credentials**, klik **Create Credentials > OAuth Client ID**.
    *   Application Type: **Web Application**.
    *   Authorized redirect URIs: `https://developers.google.com/oauthplayground`
    *   Klik **Create** dan salin **Client ID** serta **Client Secret**.

### Langkah 3.2: Dapatkan Refresh Token
1.  Buka [Google OAuth Playground](https://developers.google.com/oauthplayground).
2.  Klik ikon gerigi di pojok kanan atas, centang **Use your own OAuth credentials**, lalu isi **Client ID** & **Client Secret** Anda.
3.  Masukkan scope `https://www.googleapis.com/auth/drive` di kolom input scope.
4.  Klik **Authorize APIs**, login dengan akun Google Anda, dan berikan ijin akses.
5.  Klik **Exchange authorization code for tokens** untuk mendapatkan **Refresh Token**. Salin token tersebut.

### Langkah 3.3: Dapatkan Folder ID
1.  Buat folder baru di Google Drive Anda (misal: `jmc_hrd_uploads`).
2.  Ubah akses folder menjadi **Siapa saja yang memiliki link** dengan peran **Editor**.
3.  Buka folder tersebut dan salin kode ID folder dari URL browser Anda.  
    *Contoh*: Jika URL `https://drive.google.com/drive/folders/ID_FOLDER_GOOGLE_DRIVE`  
    *ID Foldernya*: `ID_FOLDER_GOOGLE_DRIVE`

---

## 4. Deploy Backend Laravel ke Vercel

### Langkah 4.1: File Konfigurasi di Folder `/backend`
Pastikan file-file berikut telah berada di direktori `/backend`:
1.  **`vercel.json`**: Berfungsi untuk mengatur path cache Laravel ke direktori `/tmp` (satu-satunya folder writable di Vercel) dan memetakan route ke runtime `vercel-php@0.7.4`.
2.  **`api/index.php`**: Berfungsi sebagai jembatan serverless untuk memanggil bootstrap file `/public/index.php`.

### Langkah 4.2: Jalankan Migrasi di Produksi
Karena Anda tidak memiliki akses SSH langsung ke Vercel untuk menjalankan artisan command, lakukan migrasi dari PC lokal Anda:
1.  Ubah sementara `.env` lokal Anda untuk mengarah ke host TiDB Cloud produksi.
2.  Jalankan perintah migrasi:
    ```bash
    php artisan migrate:fresh --seed
    ```
3.  Setelah tabel berhasil dibuat di TiDB Cloud, kembalikan konfigurasi `.env` lokal Anda ke database lokal.

### Langkah 4.3: Deploy ke Vercel
1.  Masuk ke dashboard [Vercel](https://vercel.com).
2.  Klik **Add New** -> **Project**, lalu impor repositori proyek Anda.
3.  Atur **Root Directory** ke folder `backend`.
4.  Pada bagian **Environment Variables**, masukkan variabel-variabel berikut:

| Key | Value | Keterangan |
| :--- | :--- | :--- |
| `APP_NAME` | `Portal HRD JMC` | Nama aplikasi Anda |
| `APP_ENV` | `production` | Set ke produksi |
| `APP_KEY` | `base64:SALIN_APP_KEY_DARI_ENV_LOKAL_ANDA` | Salin kunci aplikasi Anda (dapat dilihat dari berkas .env Anda) |
| `APP_DEBUG` | `false` | Nonaktifkan debug untuk keamanan |
| `DB_CONNECTION` | `mysql` | Driver database |
| `DB_HOST` | `gateway01.xxxx.shared.aws.tidbcloud.com` | Host database TiDB Cloud Anda |
| `DB_PORT` | `4000` | Port database TiDB Cloud |
| `DB_DATABASE` | `jmc_sistem_informasi_mini` | Nama database |
| `DB_USERNAME` | `xxxxxx.root` | Username database TiDB Cloud |
| `DB_PASSWORD` | `PasswordDatabase` | Password database TiDB Cloud |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` | Wajib diisi untuk enkripsi SSL TiDB |
| `FILESYSTEM_DISK` | `google` | Driver media penyimpanan |
| `GOOGLE_DRIVE_CLIENT_ID` | `CLIENT_ID_GOOGLE_DRIVE` | Client ID Google Cloud |
| `GOOGLE_DRIVE_CLIENT_SECRET` | `CLIENT_SECRET_GOOGLE_DRIVE` | Client Secret Google Cloud |
| `GOOGLE_DRIVE_REFRESH_TOKEN` | `REFRESH_TOKEN_GOOGLE_DRIVE` | Refresh Token dari Playground |
| `GOOGLE_DRIVE_FOLDER_ID` | `ID_FOLDER_GOOGLE_DRIVE` | Folder ID di Google Drive |
| `SESSION_DRIVER` | `database` | Harus database agar tidak menggunakan file |
| `CACHE_STORE` | `database` | Harus database agar tidak menggunakan file |
| `MAIL_MAILER` | `smtp` | Driver pengiriman email |
| `MAIL_SCHEME` | `null` | Kosongkan untuk port 587 (STARTTLS otomatis) |
| `MAIL_HOST` | `smtp.gmail.com` | Host SMTP Gmail |
| `MAIL_PORT` | `587` | Port SMTP Gmail |
| `MAIL_USERNAME` | `email-anda@gmail.com` | Akun Gmail Anda |
| `MAIL_PASSWORD` | `sandi-aplikasi-google` | Sandi Aplikasi Google (16 karakter tanpa spasi) |
| `MAIL_FROM_ADDRESS` | `email-anda@gmail.com` | Alamat email pengirim |
| `MAIL_FROM_NAME` | `Portal HRD JMC` | Nama pengirim email |

> [!IMPORTANT]
> **Cara Mendapatkan Sandi Aplikasi Google (App Password):**
> 1. Pastikan **Verifikasi 2 Langkah (2-Step Verification)** aktif di Akun Google Anda.
> 2. Buka [Keamanan Akun Google](https://myaccount.google.com/security).
> 3. Cari menu **Sandi Aplikasi (App Passwords)** di bagian pencarian atas atau di bagian Verifikasi 2 Langkah.
> 4. Ketik nama aplikasi (misal: `JMC HRD`) lalu klik **Buat (Create)**.
> 5. Salin kode 16 karakter yang muncul (misal: `abcd efgh ijkl mnop`). Masukkan ke `MAIL_PASSWORD` tanpa spasi (`abcdefghijklmnop`).

5.  Klik **Deploy**. Vercel akan menghasilkan domain backend API Anda (contoh: `https://jmc-backend.vercel.app`).

---

## 5. Deploy Frontend Nuxt.js ke Vercel

### Langkah 5.1: Deploy Proyek Frontend
1.  Buka Vercel Dashboard, klik **Add New > Project**.
2.  Pilih repositori Git yang sama.
3.  Set **Root Directory** ke folder `frontend`.
4.  Vercel akan otomatis mengenali framework sebagai **Nuxt.js**.
5.  Pada bagian **Environment Variables**, tambahkan:
    *   `NUXT_PUBLIC_API_BASE`: Masukkan domain API backend Vercel yang telah dideploy sebelumnya (contoh: `https://jmc-backend.vercel.app/api`).
    *   `NUXT_PUBLIC_APP_NAME`: `Portal HRD JMC`.
6.  Klik **Deploy**. Vercel akan menghasilkan domain produksi untuk aplikasi web Anda (misal: `https://jmc-hrd.vercel.app`).

---

## 6. Sinkronisasi CORS (Cross-Origin Resource Sharing)

Agar request frontend Nuxt.js diijinkan oleh backend Laravel di Vercel:
1.  Buka file `backend/config/cors.php` di proyek Anda.
2.  Tambahkan domain produksi frontend Vercel ke bagian array `allowed_origins`:
    ```php
    'allowed_origins' => [
        'http://localhost:3000',
        'https://jmc-hrd.vercel.app', // Ganti dengan domain frontend Vercel Anda
    ],
    ```
3.  Push perubahan tersebut ke git untuk memicu auto-deploy ulang backend di Vercel.
