# 🚀 Panduan Pemasangan & Integrasi WhatsApp Bot untuk Aplikasi Downstream

Panduan praktis ini ditujukan bagi developer aplikasi *downstream* (seperti **SIJUNA**, **Tracer Study**, **BKK**, **Aplikasi Rapor**, dll.) yang sudah mendaftarkan aplikasinya di SiPintu dan telah mendapatkan **Client ID** serta **Client Secret**.

---

## 📌 Alur Singkat (3 Langkah Mudah)

```
[1. SiPintu] Registrasi Aplikasi & Dapatkan Client ID + Secret
     ⬇️
[2. SiPintu] Buka Menu "WhatsApp Gateway" -> Scan QR dengan nomor WA khusus aplikasi Anda
     ⬇️
[3. Downstream] Masukkan Client ID & Secret ke .env -> Panggil endpoint kirim pesan
```

---

## 🔑 Langkah 1: Catat Kredensial dari SiPintu

Saat Anda mendaftarkan aplikasi di menu **Pendaftaran Aplikasi** (`/admin/applications/create`), Anda akan mendapatkan:
* **Client ID**: contoh `app_izku74wtvuha`
* **Client Secret**: contoh `sec_LMT2mAGDKDXoKsLPMQOqPKu2JMVA15050`
* **Base URL SiPintu**: contoh `http://localhost:8000` atau `https://sipintu.sekolah.sch.id`

---

## 📱 Langkah 2: Tautkan Nomor WhatsApp Bot di SiPintu

1. Di SiPintu, klik menu **WhatsApp Gateway** pada sidebar kiri (di bawah kelompok *Integrasi & Keamanan*).
2. Temukan nama aplikasi downstream Anda di tabel.
3. Klik tombol **Scan QR** (ikon barcode hijau).
4. Di HP dengan nomor WhatsApp yang ingin dijadikan bot aplikasi Anda:
   * Buka WhatsApp &rarr; Ketuk titik tiga (Android) atau Pengaturan (iOS) &rarr; **Perangkat Tertaut (Linked Devices)**.
   * Ketuk **Tautkan Perangkat** & scan QR Code di layar SiPintu.
5. Status di SiPintu akan otomatis berubah menjadi: **🟢 Terhubung (+628xxxxxxx)**.

---

## 💻 Langkah 3: Pasang di Aplikasi Downstream Anda

Pilih teknologi yang digunakan aplikasi downstream Anda di bawah ini:

### OPSI A: Jika Downstream Berbasis Laravel (PHP)

#### 1. Tambahkan ke file `.env` downstream:
```env
SIPINTU_URL=http://localhost:8000
SIPINTU_CLIENT_ID=app_izku74wtvuha
SIPINTU_CLIENT_SECRET=sec_LMT2mAGDKDXoKsLPMQOqPKu2JMVA15050
```

#### 2. Daftarkan di `config/services.php` downstream:
```php
'sipintu' => [
    'url' => rtrim(env('SIPINTU_URL', 'http://localhost:8000'), '/'),
    'client_id' => env('SIPINTU_CLIENT_ID'),
    'client_secret' => env('SIPINTU_CLIENT_SECRET'),
],
```

#### 3. Buat Helper Service di downstream (`app/Services/WhatsAppBot.php`):
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBot
{
    /**
     * Kirim pesan WhatsApp via SiPintu Gateway
     * 
     * @param string $phone Nomor tujuan (0812... / 62812...)
     * @param string $message Isi pesan WhatsApp
     * @param bool $sync false = background queue (cepat, 202 Accepted), true = langsung (200 OK)
     */
    public static function send(string $phone, string $message, bool $sync = false): array
    {
        $url = config('services.sipintu.url') . '/api/v1/whatsapp/send';
        $clientId = config('services.sipintu.client_id');
        $clientSecret = config('services.sipintu.client_secret');

        try {
            $response = Http::withHeaders([
                'X-Client-ID' => $clientId,
                'X-Client-Secret' => $clientSecret,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, [
                'phone' => $phone,
                'message' => $message,
                'sync' => $sync,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('[WhatsAppBot] Gagal kirim: ' . $response->body());
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Pengiriman gagal.',
            ];
        } catch (\Throwable $e) {
            Log::error('[WhatsAppBot] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Koneksi ke SiPintu gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cek status apakah nomor WhatsApp bot aplikasi kita sedang online
     */
    public static function isConnected(): bool
    {
        $url = config('services.sipintu.url') . '/api/v1/whatsapp/status';
        $clientId = config('services.sipintu.client_id');
        $clientSecret = config('services.sipintu.client_secret');

        try {
            $response = Http::withHeaders([
                'X-Client-ID' => $clientId,
                'X-Client-Secret' => $clientSecret,
                'Accept' => 'application/json',
            ])->timeout(5)->get($url);

            return $response->json()['whatsapp_gateway']['is_connected'] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
```

#### 4. Cara Pemanggilan di Controller Downstream:
```php
use App\Services\WhatsAppBot;

// Contoh saat siswa absen hadir:
WhatsAppBot::send(
    phone: '08123456789',
    message: "📢 [SIJUNA]\nHalo Bapak/Ibu, ananda Ahmad telah hadir di sekolah pukul 07:15 WIB."
);
```

---

### OPSI B: Jika Downstream Berbasis Node.js / Express

```javascript
import axios from 'axios';

const SIPINTU_URL = process.env.SIPINTU_URL || 'http://localhost:8000';
const CLIENT_ID = process.env.SIPINTU_CLIENT_ID;
const CLIENT_SECRET = process.env.SIPINTU_CLIENT_SECRET;

export async function sendWhatsApp(phone, message, sync = false) {
    try {
        const response = await axios.post(`${SIPINTU_URL}/api/v1/whatsapp/send`, {
            phone: phone,
            message: message,
            sync: sync
        }, {
            headers: {
                'X-Client-ID': CLIENT_ID,
                'X-Client-Secret': CLIENT_SECRET,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout: 10000
        });

        return { success: true, data: response.data };
    } catch (error) {
        console.error('[WhatsAppBot Error]:', error.response?.data || error.message);
        return { success: false, error: error.response?.data?.message || error.message };
    }
}

// Pemanggilan:
// await sendWhatsApp('08123456789', 'Halo ini pesan dari bot downstream!');
```

---

### OPSI C: Jika Downstream Berbasis PHP Native / cURL

```php
<?php

function sendWhatsAppMessage($phone, $message) {
    $sipintuUrl = 'http://localhost:8000/api/v1/whatsapp/send';
    $clientId = 'app_izku74wtvuha';
    $clientSecret = 'sec_LMT2mAGDKDXoKsLPMQOqPKu2JMVA15050';

    $payload = json_encode([
        'phone' => $phone,
        'message' => $message,
        'sync' => false,
    ]);

    $ch = curl_init($sipintuUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Client-ID: ' . $clientId,
        'X-Client-Secret: ' . $clientSecret,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}
```

---

### OPSI D: Jika Downstream Berbasis Python

```python
import requests

def send_whatsapp(phone: str, message: str):
    url = "http://localhost:8000/api/v1/whatsapp/send"
    headers = {
        "X-Client-ID": "app_izku74wtvuha",
        "X-Client-Secret": "sec_LMT2mAGDKDXoKsLPMQOqPKu2JMVA15050",
        "Content-Type": "application/json",
        "Accept": "application/json"
    }
    payload = {
        "phone": phone,
        "message": message,
        "sync": False
    }
    
    response = requests.post(url, json=payload, headers=headers, timeout=10)
    return response.json()
```

---

## 🛡️ Fitur Canggih: Auto-Fallback ke Nomor Pusat

Jika HP bot downstream Anda sewaktu-waktu:
* Mati lampu / baterai habis
* Tidak ada sinyal / kehabisan kuota
* Terputus koneksinya

SiPintu secara cerdas akan **otomatis mengalihkan pesan tersebut untuk dikirim via Nomor WhatsApp Pusat SiPintu**, sehingga pesan penting (seperti OTP atau presensi) **dijamin tetap sampai ke penerima tanpa gagal**.

---

## 🚨 Kenapa Notifikasi Otomatis Downstream Tidak Terkirim? (Troubleshooting Cepat)

Jika aplikasi downstream Anda sudah memiliki fitur notifikasi WhatsApp otomatis tetapi pesannya **tidak pernah sampai ke nomor user/orang tua**, periksa 5 penyebab utama berikut:

### ⚠️ Penyebab 1: Menggunakan Mode Antrean (`sync: false`) Tapi Queue Worker SiPintu Belum Berjalan
* **Gejala:** Downstream menerima respon `202 Accepted` ("queued"), log di downstream tampak berhasil, namun pesan tidak pernah terkirim ke WhatsApp.
* **Penyebab:** SiPintu secara default memasukkan pesan ke tabel `jobs` (antrean database). Jika di server SiPintu perintah worker tidak dijalankan, pesan tertahan selamanya di antrean!
* **Solusi Cepat:**
  1. Ubah parameter `sync` menjadi `true` di downstream:
     ```php
     WhatsAppBot::send(phone: $phone, message: $msg, sync: true);
     ```
     Dengan `sync: true`, pesan langsung dikirim detik itu juga (200 OK) tanpa menunggu queue worker.
  2. Atau, jika ingin tetap antrean asinkron, jalankan worker di server SiPintu:
     ```bash
     php artisan queue:work --tries=3
     ```

### ⚠️ Penyebab 2: Daemon Service Baileys Bot Node.js Belum Dijalankan
* **Gejala:** Downstream menerima respon HTTP `502 Bad Gateway` atau cURL error `Connection refused to http://127.0.0.1:3789`.
* **Penyebab:** Mesin pengirim WhatsApp di SiPintu (`whatsapp-bot/index.js`) belum menyala.
* **Solusi:** Di server SiPintu, jalankan service bot:
  ```bash
  cd /path/ke/SiPintu/whatsapp-bot
  npm start
  # atau gunakan PM2 agar otomatis hidup kembali jika crash/reboot:
  pm2 start ecosystem.config.cjs
  ```

### ⚠️ Penyebab 3: Nomor WhatsApp Belum Ditautkan (Belum Scan QR)
* **Gejala:** Respon error `Bot offline` atau `Nomor bot belum terhubung`.
* **Solusi:** 
  1. Buka dashboard SiPintu &rarr; Menu **WhatsApp Gateway** (`/admin/whatsapp-gateway`).
  2. Cari aplikasi downstream Anda & klik ikon **Scan QR**.
  3. Buka WhatsApp di HP Anda &rarr; **Perangkat Tertaut** &rarr; **Tautkan Perangkat** &rarr; Scan QR Code.
  4. Pastikan status berubah menjadi **🟢 Terhubung**.
  *(Tips: Pastikan opsi "Auto Fallback to Central Bot" dicentang agar jika nomor Anda offline, bot pusat SiPintu otomatis mem-backup pengiriman).*

### ⚠️ Penyebab 4: Format Nomor Telepon Salah / Kosong di Database Downstream
* **Gejala:** Respon HTTP `422 Unprocessable Entity` ("Format nomor telepon tidak valid").
* **Penyebab:** Nomor di database siswa/ortu downstream mengandung tanda strip (`0812-3456-789`), spasi (`0812 3456`), atau kosong/null.
* **Solusi:** SiPintu menerima format nomor Indonesia seperti `08123456789` atau `628123456789`. Pastikan downstream memformat nomor sebelum mengirim:
  ```php
  $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
  ```

### ⚠️ Penyebab 5: Trigger Event di Downstream Belum Memanggil Helper WhatsApp
* **Gejala:** Tidak ada log apa pun di SiPintu (tabel `whatsapp_logs` kosong).
* **Penyebab:** Fitur notifikasi otomatis di downstream hanya berupa tampilan UI/setting, namun fungsi pemanggilan API SiPintu belum disematkan pada fungsi controller/observer absensi.
* **Solusi:** Tautkan pemanggilan `WhatsAppBot::send(...)` langsung ke Event atau Observer absensi (lihat panduan langkah demi langkah di bawah).

---

## ⚡ Panduan Detail: Cara Memasang Notifikasi Otomatis pada Aplikasi Downstream

Berikut adalah panduan langkah demi langkah untuk memasang notifikasi otomatis pada skenario nyata:

### Skenario: Notifikasi Absensi/Presensi Siswa Otomatis ke WhatsApp Orang Tua (Contoh: SIJUNA)

#### Opsi 1: Pasang Langsung di Controller Absensi (Paling Mudah)
Buka controller downstream tempat data presensi disimpan (misal: `AttendanceController.php` atau `PresensiController.php`):

```php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\WhatsAppBot;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    public function store(Request $request)
    {
        // 1. Simpan data presensi siswa
        $attendance = Attendance::create([
            'student_id' => $request->student_id,
            'status'     => $request->status, // misal: 'hadir', 'terlambat'
            'time_in'    => now()->format('H:i:s'),
            'date'       => today(),
        ]);

        $student = Student::with('parent')->find($request->student_id);

        // 2. Kirim notifikasi otomatis jika nomor orang tua / wali tersedia
        $parentPhone = $student->parent_phone ?? $student->parent?->whatsapp_number;

        if ($parentPhone) {
            $namaSiswa = $student->name;
            $statusStr = ucfirst($attendance->status);
            $jam = now()->translatedFormat('H:i') . ' WIB';
            $tanggal = now()->translatedFormat('d F Y');

            $pesan = "📢 *NOTIFIKASI PRESENSI SISWA*\n\n"
                   . "Yth. Orang Tua/Wali dari *{$namaSiswa}*,\n\n"
                   . "Diberitahukan bahwa ananda telah tercatat presensi:\n"
                   . "• Tanggal : {$tanggal}\n"
                   . "• Jam     : {$jam}\n"
                   . "• Status  : *{$statusStr}*\n\n"
                   . "Pesan ini dikirim otomatis oleh sistem SIJUNA.";

            // Kirim secara langsung (sync = true direkomendasikan untuk notifikasi real-time)
            WhatsAppBot::send(
                phone: $parentPhone,
                message: $pesan,
                sync: true
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Presensi berhasil dicatat dan notifikasi WhatsApp telah dikirim.',
        ]);
    }
}
```

#### Opsi 2: Menggunakan Eloquent Model Observer (Arsitektur Terbaik & Rapi)
Jika aplikasi downstream ingin setiap kali data presensi baru dibuat otomatis mengirim WhatsApp tanpa mengotori controller:

1. Buat observer di downstream:
   ```bash
   php artisan make:observer AttendanceObserver --model=Attendance
   ```
2. Isi file `app/Observers/AttendanceObserver.php`:
   ```php
   namespace App\Observers;

   use App\Models\Attendance;
   use App\Services\WhatsAppBot;
   use Illuminate\Support\Facades\Log;

   class AttendanceObserver
   {
       public function created(Attendance $attendance): void
       {
           $student = $attendance->student;
           $parentPhone = $student?->parent_phone;

           if (! $parentPhone) {
               return;
           }

           $pesan = "📢 *NOTIFIKASI PRESENSI*\n\n"
                  . "Halo Bapak/Ibu, ananda *{$student->name}* tercatat *{$attendance->status}* pada pukul " . now()->format('H:i') . " WIB.";

           // Kirim dengan sync: true
           $res = WhatsAppBot::send($parentPhone, $pesan, sync: true);

           if (! $res['success']) {
               Log::warning("[Presensi] Gagal kirim WA notifikasi ke {$parentPhone}: " . ($res['error'] ?? 'Unknown error'));
           }
       }
   }
   ```
3. Daftarkan di `app/Providers/EventServiceProvider.php` downstream:
   ```php
   use App\Models\Attendance;
   use App\Observers\AttendanceObserver;

   public function boot(): void
   {
       Attendance::observe(AttendanceObserver::class);
   }
   ```

---

## 🧪 Uji Coba Cepat (Diagnostik Mandiri via Terminal / cURL)

Sebelum menyalahkan aplikasi downstream, jalankan 2 tes cURL berikut dari terminal downstream atau Postman untuk memastikan SiPintu siap:

### 1. Tes Cek Status Sesi Bot Downstream
```bash
curl -X GET "http://localhost:8000/api/v1/whatsapp/status" \
  -H "X-Client-ID: <client_id_aplikasi_anda>" \
  -H "X-Client-Secret: <client_secret_aplikasi_anda>" \
  -H "Accept: application/json"
```
**Hasil yang diharapkan:**
`"is_connected": true` dan `"gateway_status": "ready"`.

### 2. Tes Kirim Pesan Langsung (Sync: true)
```bash
curl -X POST "http://localhost:8000/api/v1/whatsapp/send" \
  -H "X-Client-ID: <client_id_aplikasi_anda>" \
  -H "X-Client-Secret: <client_secret_aplikasi_anda>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "phone": "08123456789",
    "message": "Halo! Ini adalah pesan uji coba integrasi WhatsApp Bot.",
    "sync": true
  }'
```
**Hasil yang diharapkan:**
Respon HTTP `200 OK` dengan `"status": "success"` dan pesan langsung masuk ke HP tujuan dalam 1-3 detik.
