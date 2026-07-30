# 🚪 Panduan Pemasangan & Integrasi SiPintu (API Gateway)

Dokumen ini berisi panduan lengkap untuk melakukan pengunduhan, pemasangan, dan konfigurasi project **SiPintu Identity & API Gateway** di lingkungan lokal (*localhost*), serta panduan menghubungkan aplikasi klien milik rekan tim/teman Anda.

---

## 🏗️ Skema Arsitektur Sistem

```text
┌───────────────────────────┐         ┌──────────────────────────┐         ┌──────────────────────────┐
│  Aplikasi Klien (Teman)   │ ──────> │   SiPintu (API Gateway)  │ ──────> │    Server SIJUNA Pusat   │
│  (http://localhost:8000)  │  OAuth  │  (http://localhost:8000) │   API   │  (https://sijuna.com)    │
└───────────────────────────┘         └──────────────────────────┘         └──────────────────────────┘
```

> ⚠️ **Penting untuk Dipahami:**
> - **Aplikasi Teman** hanya berkomunikasi dengan **SiPintu** (`http://localhost:8000/api`).
> - **SiPintu** bertugas sebagai proxy/gateway yang mengamankan token, mengelola cache, dan berkomunikasi dengan **SIJUNA Pusat**.

---

## 📋 1. Prasyarat Sistem

Sebelum melakukan pemasangan, pastikan laptop Anda sudah terinstal perangkat lunak berikut:

- **PHP**: ^8.2 (dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- **Composer**: ^2.0
- **MySQL / MariaDB**: (via XAMPP, Laragon, atau Native MySQL Server)
- **Git**
- **Node.js & NPM**: (Opsional, jika ingin mengubah/kompilasi aset UI)

---

## 🚀 2. Langkah-Langkah Pemasangan SiPintu di Lokal

### Langkah 2.1: Clone Repositori
Buka terminal / Command Prompt, lalu klon repositori SiPintu:
```bash
git clone <URL_REPOSITORI_SIPINTU>
cd SiPintu
```

---

### Langkah 2.2: Install Dependensi PHP
Jalankan composer untuk mengunduh seluruh library Laravel:
```bash
composer install
```

---

### Langkah 2.3: Konfigurasi Environment (`.env`)
1. Duplikat file `.env.example` menjadi `.env`:
   * **Linux/macOS:** `cp .env.example .env`
   * **Windows (PowerShell):** `copy .env.example .env`

2. Buka file `.env` yang baru dibuat, lalu sesuaikan konfigurasi Database & API SIJUNA:

```env
APP_NAME=SiPintu
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# ----------------------------------------------------
# KONFIGURASI DATABASE
# ----------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=SiPintu
DB_USERNAME=root
DB_PASSWORD=

# ----------------------------------------------------
# KONFIGURASI SIJUNA API (Gateway Upstream)
# ----------------------------------------------------

biarin Helmy yang masang kalo yg ini
---

### Langkah 2.4: Generate Application Key
Jalankan perintah berikut untuk menggenerasi kunci enkripsi Laravel:
```bash
php artisan key:generate
```

---

### Langkah 2.5: Buat Database & Jalankan Migration
1. Buat database baru bernama `SiPintu` di MySQL (bisa melalui phpMyAdmin atau MySQL CLI).
2. Jalankan perintah `migration` beserta `seeder` untuk mengisi tabel dan data awal (seperti akun admin & data client OAuth):
```bash
php artisan migrate --seed
```

---

### Langkah 2.6: Jalankan Server Lokal SiPintu
Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Secara default, SiPintu akan aktif dan siap menerima request di:
**`http://localhost:8000`** atau **`http://127.0.0.1:8000`**

---

## 3. Panduan Integrasi Aplikasi Klien (Aplikasi Teman)

Agar aplikasi Anda bisa terhubung ke SiPintu API Gateway:

### Step 3.1: Konfigurasi di Aplikasi Anda (`.env`)
Di file `.env` pada aplikasi Anda, tambahkan URL SiPintu beserta **Client Credentials** yang terdaftar di SiPintu:

```env
SIPINTU_API_URL=http://localhost:8000/api
SIPINTU_CLIENT_ID=your_client_id_here
SIPINTU_CLIENT_SECRET=your_client_secret_here
```

### Step 3.2: Memanggil API SiPintu (Contoh Request Header)
Setiap kali aplikasi Anda melakukan request ke API SiPintu, sertakan header autentikasi berikut:

```http
GET /api/identity/students HTTP/1.1
Host: localhost:8000
X-Client-ID: your_client_id_here
X-Client-Secret: your_client_secret_here
Accept: application/json
```

---

## 🛠️ 4. Perintah Umum & Troubleshooting

* **Reset Database (Jika ada perubahan struktur):**
  ```bash
  php artisan migrate:fresh --seed
  ```

* **Clear Cache (Jika perubahan `.env` tidak terbaca):**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

* **Melihat Daftar Route API SiPintu:**
  ```bash
  php artisan route:list --path=api
  ```

---

*Dokumentasi ini dibuat untuk mempermudah pemasangan SiPintu sebagai API Gateway lokal.* 🚀