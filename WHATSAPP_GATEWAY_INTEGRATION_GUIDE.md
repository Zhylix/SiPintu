# 📱 Panduan Integrasi Multi-Session WhatsApp Gateway (SiPintu)

Dokumen ini menjelaskan cara menggunakan fitur **Multi-Session WhatsApp Gateway** di **SiPintu** untuk aplikasi *downstream* (seperti SIJUNA, Tracer Study, BKK, Perpustakaan, dsb.).

---

## 🌟 1. Keunggulan Arsitektur Multi-Session SiPintu

1. **Nomor Mandiri per Aplikasi**: Setiap aplikasi *downstream* dapat memiliki nomor WhatsApp sendiri (misal: nomor khusus Tata Usaha untuk SIJUNA, nomor khusus BKK untuk Tracer Study).
2. **Isolasi Anti-Blokir**: Jika salah satu nomor downstream terkena batasan WhatsApp, nomor aplikasi lain tetap aman dan berjalan normal.
3. **Fallback Otomatis ke Nomor Pusat**: Jika nomor WhatsApp khusus aplikasi downstream sedang offline/terputus, pesan dapat otomatis dialihkan untuk dikirim via **Nomor WhatsApp Pusat SiPintu** agar pesan penting (seperti OTP/presensi) tidak hilang.
4. **Asynchronous Queue**: Pengiriman pesan secara default menggunakan sistem antrean berkinerja tinggi sehingga aplikasi downstream tidak mengalami *timeout* / *lag*.

---

## 🔐 2. Autentikasi API

Setiap request dari aplikasi downstream wajib menyertakan kredensial aplikasi yang terdaftar di SiPintu pada HTTP Header:

```http
X-Client-ID: <client_id_aplikasi_anda>
X-Client-Secret: <client_secret_aplikasi_anda>
Content-Type: application/json
Accept: application/json
```

> **Catatan:** Nilai `client_id` dan `client_secret` dapat dilihat oleh Administrator di menu **Admin &rarr; Pendaftaran Aplikasi** pada dashboard SiPintu.

---

## 📡 3. Spesifikasi Endpoint API

Base URL API: `https://<domain-sipintu>/api/v1/whatsapp`

### A. Kirim Pesan WhatsApp (`POST /send`)

Mengirim pesan WhatsApp ke nomor tujuan melalui nomor WhatsApp khusus aplikasi downstream.

* **URL:** `/api/v1/whatsapp/send`
* **Method:** `POST`
* **Headers:** `X-Client-ID`, `X-Client-Secret`, `Content-Type: application/json`
* **Body Request (JSON):**
  | Parameter | Tipe Data | Wajib | Keterangan |
  | :--- | :--- | :--- | :--- |
  | `phone` | `string` | **Ya** | Nomor WhatsApp penerima (format `0812...` atau `62812...`) |
  | `message` | `string` | **Ya** | Teks isi pesan WhatsApp |
  | `sync` | `boolean` | *Tidak* | `false` (default) = Di-antrikan di background (cepat, 202 Accepted). <br>`true` = Kirim langsung saat request (200 OK). |

#### Contoh Request:
```json
{
  "phone": "08123456789",
  "message": "Halo Ahmad, absensi Anda tercatat hadir pada pukul 07:15 WIB.",
  "sync": false
}
```

#### Contoh Respon Berhasil (Antrean / Async - 202 Accepted):
```json
{
  "status": "queued",
  "message": "Pesan WhatsApp berhasil dimasukkan ke dalam antrean pengiriman.",
  "data": {
    "log_id": 142,
    "recipient": "628123456789",
    "session_id": "app_sijuna",
    "queued_at": "2026-10-22T18:05:00+07:00"
  }
}
```

---

### B. Cek Status Koneksi Bot Aplikasi (`GET /status`)

Memeriksa apakah nomor WhatsApp khusus milik aplikasi downstream Anda sedang terhubung, offline, atau menggunakan fallback.

* **URL:** `/api/v1/whatsapp/status`
* **Method:** `GET`
* **Headers:** `X-Client-ID`, `X-Client-Secret`

#### Contoh Respon (200 OK):
```json
{
  "status": "success",
  "application": {
    "name": "SIJUNA Presensi",
    "slug": "sijuna",
    "client_id": "app_sijuna_123"
  },
  "whatsapp_gateway": {
    "session_id": "app_sijuna",
    "assigned_phone": "6285712345678",
    "is_connected": true,
    "is_enabled": true,
    "auto_fallback_to_central": true,
    "gateway_status": "ready"
  }
}
```

---

## 💻 4. Contoh Kode Pemanggilan dari Aplikasi Downstream

### Contoh 1: Laravel / PHP (Guzzle / Http Client)
```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-Client-ID' => config('services.sipintu.client_id'),
    'X-Client-Secret' => config('services.sipintu.client_secret'),
    'Accept' => 'application/json',
])->post('https://sipintu.sekolah.sch.id/api/v1/whatsapp/send', [
    'phone' => '08123456789',
    'message' => 'Halo! Ini adalah notifikasi otomatis dari SIJUNA.',
]);

if ($response->successful()) {
    $result = $response->json();
    // Log ID pengiriman: $result['data']['log_id']
}
```

### Contoh 2: Node.js (Axios)
```javascript
import axios from 'axios';

async function sendWhatsApp(phone, message) {
    const res = await axios.post('https://sipintu.sekolah.sch.id/api/v1/whatsapp/send', {
        phone: phone,
        message: message,
    }, {
        headers: {
            'X-Client-ID': process.env.SIPINTU_CLIENT_ID,
            'X-Client-Secret': process.env.SIPINTU_CLIENT_SECRET,
            'Content-Type': 'application/json'
        }
    });

    return res.data;
}
```

### Contoh 3: cURL Command Line
```bash
curl -X POST https://sipintu.sekolah.sch.id/api/v1/whatsapp/send \
  -H "Content-Type: application/json" \
  -H "X-Client-ID: app_sijuna_123" \
  -H "X-Client-Secret: sec_abcxyz789" \
  -d '{
    "phone": "08123456789",
    "message": "Kode verifikasi Anda adalah 582190."
  }'
```

---

## ⚙️ 5. Cara Admin Menghubungkan Nomor WhatsApp untuk Downstream

1. Masuk ke Dashboard Admin SiPintu &rarr; Menu **WhatsApp Gateway** (`/admin/whatsapp-gateway`).
2. Cari nama aplikasi downstream yang ingin dipasangkan nomor (misal: **SIJUNA**).
3. Klik ikon **Scan QR** (ikon barcode).
4. Di HP dengan nomor WhatsApp aplikasi tersebut:
   * Buka aplikasi WhatsApp &rarr; **Perangkat Tertaut (Linked Devices)**.
   * Pilih **Tautkan Perangkat** dan scan QR Code yang muncul di layar.
5. Sesi akan otomatis terhubung (*online*) dan nomor HP aplikasi tersebut langsung tercatat aktif.

---

## 🔌 6. Cara Admin Memutuskan (Disconnect) Nomor WhatsApp

Jika admin ingin mengganti nomor WhatsApp atau melepas nomor dari sesi:
1. **Nomor WhatsApp Pusat**:
   * Klik tombol **Disconnect** berwarna merah di kartu *Nomor WhatsApp Pusat SiPintu*.
2. **Nomor Downstream Application**:
   * Pada tabel sesi aplikasi, klik tombol ikon **Disconnect** (berwarna merah) di kolom *Aksi*.
3. **Melalui Modal QR Code**:
   * Jika modal QR dibuka pada sesi yang sudah terhubung, klik tombol **Putus / Disconnect Nomor**.
4. Setelah konfirmasi, sesi WhatsApp akan otomatis di-*logout* dan dilepas (*unlinked*), folder autentikasi dibersihkan, dan status di database diperbarui menjadi `disconnected`.

---

## 🛠️ 7. Troubleshooting: Mengapa Notifikasi Otomatis Downstream Tidak Terkirim?

| Gejala Masalah | Kemungkinan Penyebab | Cara Mengatasi |
| :--- | :--- | :--- |
| **Respon `202 Accepted` ("queued") tapi pesan tidak pernah masuk ke HP user** | Pesan tertahan di antrean database karena `queue:work` belum jalan di server SiPintu. | 1. Di downstream, tambahkan `"sync": true` pada request agar pesan langsung dikirim seketika tanpa antrean.<br>2. Atau jalankan worker di SiPintu: `php artisan queue:work`. |
| **Error `502 Bad Gateway` atau `Connection refused 127.0.0.1:3789`** | Service Node.js Baileys Bot belum dinyalakan di server SiPintu. | Jalankan service bot di server SiPintu: `cd whatsapp-bot && npm start` (atau gunakan PM2: `pm2 start ecosystem.config.cjs`). |
| **Error `Bot offline` atau `Nomor bot belum terhubung`** | Nomor WhatsApp bot belum di-scan QR atau koneksi HP terputus. | Buka menu **WhatsApp Gateway** (`/admin/whatsapp-gateway`) di SiPintu & scan QR code dengan nomor WA yang ingin dijadikan bot. |
| **Error `422 Unprocessable Entity` ("Format nomor telepon tidak valid")** | Nomor telepon di database downstream kosong atau mengandung karakter ilegal (spasi, strip, huruf). | Bersihkan nomor sebelum dikirim: gunakan format `08123456789` atau `628123456789` (hanya digit angka). |
| **Error `401 Unauthorized`** | Nilai `X-Client-ID` atau `X-Client-Secret` salah/belum disetel di `.env` downstream. | Cocokkan kredensial di file `.env` downstream dengan data di menu **Admin &rarr; Pendaftaran Aplikasi** SiPintu. |
| **Tidak ada respon / log sama sekali di SiPintu** | Trigger notifikasi di downstream belum dipanggil pada logic/event presensi. | Pasang pemanggilan API di Controller Presensi atau Model Observer (lihat [PANDUAN_PEMASANGAN_BOT_DOWNSTREAM.md](file:///home/zephyr/Documents/SiPintu/SiPintu/PANDUAN_PEMASANGAN_BOT_DOWNSTREAM.md)). |


