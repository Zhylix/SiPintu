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
