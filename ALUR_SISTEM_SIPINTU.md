# Dokumentasi Alur Sistem Lengkap - SiPintu Identity Gateway

**SiPintu (Sistem Pintu Identity Gateway)** adalah platform identitas tersentralisasi (SSO / OAuth 2.0 / OpenID Connect Provider) yang mengelola autentikasi, otorisasi, sinkronisasi data dari SIJUNA, monitoring aplikasi hilir (*downstream apps*), serta pengiriman pengumuman via WhatsApp Bot.

---

## 📋 Daftar Isi
1. [Arsitektur & Konsep Utama Sistem](#1-arsitektur--konsep-utama-sistem)
2. [Alur Autentikasi Internal (Login Multi-Peran)](#2-alur-autentikasi-internal-login-multi-peran)
3. [Alur OAuth 2.0 & OpenID Connect (SSO Aplikasi Hilir)](#3-alur-oauth-20--openid-connect-sso-aplikasi-hilir)
4. [Alur Sinkronisasi Data SIJUNA (Siswa & Guru)](#4-alur-sinkronisasi-data-sijuna-siswa--guru)
5. [Alur Broadcast Pengumuman & WhatsApp Bot](#5-alur-broadcast-pengumuman--whatsapp-bot)
6. [Alur Monitoring Kesehatan Aplikasi & Audit Log](#6-alur-monitoring-kesehatan-aplikasi--audit-log)
7. [Matriks Peran & Hak Akses Sistem (RBAC)](#7-matriks-peran--hak-akses-sistem-rbac)
8. [Struktur Peta Rute & Middleware](#8-struktur-peta-rute--middleware)

---

## 1. Arsitektur & Konsep Utama Sistem

SiPintu berfungsi sebagai **Identity Provider (IdP)** utama untuk seluruh ekosistem aplikasi di lingkungan sekolah/institusi. 

```mermaid
graph TD
    User([Pengguna: Admin / Guru / Siswa / DUDI])
    SiPintu[SiPintu Identity Gateway]
    Sijuna[API External SIJUNA]
    WABot[Service WhatsApp Bot]
    AppA[Aplikasi CBT / Ujian]
    AppB[Aplikasi E-Absensi]
    AppC[Aplikasi E-Prakerin / DUDI]

    User -->|1. Login / SSO Request| SiPintu
    SiPintu <-->|2. Sync Data Siswa & Guru| Sijuna
    SiPintu -->|3. Dispatch Broadcast Notifikasi| WABot
    SiPintu <-->|4. OAuth 2.0 Auth / Token / UserInfo| AppA
    SiPintu <-->|4. OAuth 2.0 Auth / Token / UserInfo| AppB
    SiPintu <-->|4. OAuth 2.0 Auth / Token / UserInfo| AppC
```

---

## 2. Alur Autentikasi Internal (Login Multi-Peran)

SiPintu mendukung login multi-identifier yang disesuaikan dengan jenis akun/peran pengguna:

- **Admin**: Username / Email
- **Guru**: NIP / Email / Username
- **Siswa**: NIS / NISN / Email / Username
- **DUDI (Mitra)**: Kode DUDI / Email / Username

### Diagram Alur Login & Redireksi Peran

```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna
    participant Web as Browser / Frontend
    participant AuthCtrl as AuthController
    participant AuthMiddleware as Role Middleware
    participant Dash as Dashboard Peran

    User->>Web: Akses Halaman Login (/login)
    Web->>User: Tampilkan Form Login (Identifier & Password)
    User->>Web: Input Credential & Submit
    Web->>AuthCtrl: POST /login
    AuthCtrl->>AuthCtrl: Validasi Credential & Cek Status Aktif/Blocked
    alt Credential Salah / Ditolak
        AuthCtrl-->>Web: Redirect kembali dengan pesan Error & input
    else Credential Valid
        AuthCtrl->>AuthCtrl: Regenerate Session & Log Audit
        AuthCtrl-->>Web: Redirect ke Halaman Utama (/)
        Web->>AuthMiddleware: GET / -> Cek Role Pengguna
        alt Role: Admin
            AuthMiddleware-->>Dash: Redirect ke /admin/dashboard
        else Role: Teacher (Guru)
            AuthMiddleware-->>Dash: Redirect ke /guru/dashboard
        else Role: Student (Siswa)
            AuthMiddleware-->>Dash: Redirect ke /siswa/dashboard
        else Role: DUDI
            AuthMiddleware-->>Dash: Redirect ke /dudi/dashboard
        end
    end
```

---

## 3. Alur OAuth 2.0 & OpenID Connect (SSO Aplikasi Hilir)

SiPintu menyediakan mekanisme **Single Sign-On (SSO)** standar industri menggunakan **OAuth 2.0 Authorization Code Grant** dan **OpenID Connect (OIDC)**.

### Sequence Diagram OAuth 2.0 / OIDC Flow

```mermaid
sequenceDiagram
    autonumber
    actor User as Pengguna
    participant ClientApp as Aplikasi Hilir (Client App)
    participant SiPintuAuth as SiPintu Auth Engine
    participant SiPintuOAuth as OAuthController (/oauth)
    participant ResourceAPI as API Gateway (/api/v1)

    User->>ClientApp: Klik "Login via SiPintu"
    ClientApp->>SiPintuOAuth: GET /oauth/authorize?client_id=...&redirect_uri=...&scope=openid profile&response_type=code
    
    alt User belum Login ke SiPintu
        SiPintuOAuth-->>User: Redirect ke Form Login (/login)
        User->>SiPintuAuth: Submit Credential Login
        SiPintuAuth-->>SiPintuOAuth: Login Berhasil
    end

    SiPintuOAuth->>SiPintuOAuth: Generasi Auth Code (Masa berlaku 10 Menit)
    SiPintuOAuth-->>ClientApp: Redirect ke redirect_uri dengan `code` & `state`
    
    ClientApp->>SiPintuOAuth: POST /oauth/token (client_id, client_secret, code, grant_type=authorization_code)
    SiPintuOAuth->>SiPintuOAuth: Verifikasi client_secret & auth code
    SiPintuOAuth-->>ClientApp: Response Token JSON (access_token, id_token, token_type, expires_in)
    
    ClientApp->>ResourceAPI: GET /api/v1/user (Header: Authorization Bearer {access_token})
    ResourceAPI->>ResourceAPI: Validasi Token & Role Pengguna
    ResourceAPI-->>ClientApp: Data Profil Pengguna (Nama, Email, Role, NIS/NIP, dll)
    ClientApp-->>User: Pengguna Berhasil Masuk ke Aplikasi Hilir
```

### Endpoints OIDC & Public Keys:
- **Discovery Endpoint**: `GET /.well-known/openid-configuration`
- **Public Keys (JWKS)**: `GET /oauth/jwks.json` (Untuk verifikasi tanda tangan digital `id_token` JWT oleh aplikasi klien)
- **Single Logout**: `POST /oauth/logout`

---

## 4. Alur Sinkronisasi Data SIJUNA (Siswa & Guru)

Data siswa dan guru pada SiPintu terintegrasi secara otomatis dengan **SIJUNA API** untuk menjamin konsistensi data entitas.

### Mekanisme Sinkronisasi:
1. **Otomatis (Scheduled Task)**: Berjalan setiap 6 jam via Laravel Scheduler (`routes/console.php`).
2. **Manual (Admin Trigger)**: Admin dapat memicu sinkronisasi kapan saja melalui panel `/admin/sijuna`.

```mermaid
flowchart TD
    Start([Pemicu: Cron Scheduler 6 Jam / Tombol Admin]) --> JobQueue[Enqueue SyncSijunaStudentsJob & SyncSijunaTeachersJob]
    JobQueue --> Service[SijunaApiService Connection]
    Service -->|GET /api/v1/students & /teachers| ExtAPI[SIJUNA External REST API]
    ExtAPI -- Returns Paginated Data --> Service
    Service --> Loop{Iterasi Data}
    Loop --> UpdateDB[Upsert User Record ke Database Local]
    UpdateDB --> MapExtID[Simpan / Update external_id & Meta Profile]
    MapExtID --> NextItem{Ada Data Berikutnya?}
    NextItem -- Ya --> Loop
    NextItem -- Tidak --> SaveLog[Catat Aktivitas ke Tabel sync_logs]
    SaveLog --> Finish([Proses Sinkronisasi Selesai])
```

---

## 5. Alur Broadcast Pengumuman & WhatsApp Bot

SiPintu dilengkapi dengan modul pengumuman tersentralisasi dan bot pengirim pesan WhatsApp berbasis Node.js (`whatsapp-bot`).

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin SiPintu
    participant AdminUI as Admin Panel (/admin/announcements)
    participant Queue as SendWhatsAppAnnouncementJob
    participant WAService as WhatsAppService
    participant WABot as Node.js Baileys Bot Service
    actor TargetUser as Target Pengguna (Siswa/Guru/DUDI)

    Admin->>AdminUI: Buat Pengumuman Baru & Klik "Kirim via WhatsApp"
    AdminUI->>Queue: Dispatch Job Pengiriman Notifikasi
    Queue->>WAService: Panggil WhatsAppService::sendMessage()
    WAService->>WABot: HTTP POST /send-message (Payload Nomor & Pesan)
    
    alt Status Bot Connected
        WABot->>TargetUser: Pengiriman Pesan WA via Baileys Client
        WABot-->>WAService: Response Status Success
        WAService->>Queue: Update Log WhatsAppLog (Status: Delivered)
    else Status Bot Disconnected / Pairing Error
        WAService->>Queue: Catat Log WhatsAppLog (Status: Failed)
        Queue-->>AdminUI: Notifikasi Status Gagal di Dashboard Admin
    end
```

---

## 6. Alur Monitoring Kesehatan Aplikasi & Audit Log

SiPintu memantau status kesehatan (*health status*) dari seluruh aplikasi hilir terdaftar serta mencatat log audit keamanan.

```mermaid
flowchart LR
    subgraph Monitoring
        Cron[HealthCheckJob / Admin Manual Ping] --> HealthService[GatewayHealthValidationService]
        HealthService -->|HTTP GET /health| ClientApps[Aplikasi Hilir 1, 2, 3...]
        ClientApps -- Status HTTP 200 --> ActiveStatus[Status: Active]
        ClientApps -- Timeout / Error --> WarnStatus[Status: Problem / Disconnected]
        ActiveStatus & WarnStatus --> UpdateAppsDB[Update Column connection_status & last_ping_at]
    end

    subgraph Security & Audit
        UserAction[Aktivitas User / Admin] --> AuditLogger[AuditLogger Service]
        AuditLogger --> SaveAudit[Simpan ke audit_logs]
        FailedLogin[Login Gagal Berturut-turut] --> SecurityLogger[SecurityLog & BlockedIp]
    end
```

---

## 7. Matriks Peran & Hak Akses Sistem (RBAC)

| Peran (Role) | Akses Dashboard | Kelola User | Kelola App & SSO | Sync SIJUNA | WhatsApp Broadcast | Akses App Hilir |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **Admin** | `/admin/dashboard` | ✅ Full | ✅ Full | ✅ Trigger & View | ✅ Full Control | ✅ Semua App |
| **Guru (Teacher)** | `/guru/dashboard` | ❌ No | ❌ No | ❌ No | ❌ No | ✅ App Terotorisasi |
| **Siswa (Student)**| `/siswa/dashboard` | ❌ No | ❌ No | ❌ No | ❌ No | ✅ App Terotorisasi |
| **DUDI (Mitra)** | `/dudi/dashboard` | ❌ No | ❌ No | ❌ No | ❌ No | ✅ App Terotorisasi |

---

## 8. Struktur Peta Rute & Middleware

### Rute Utama & Autentikasi (`web.php`)
- `/login` & `/forgot-password`: Autentikasi Pengguna (Guest Only)
- `/profile` & `/applications/{app}/favorite`: Pengaturan Profil & Aplikasi Favorit (Auth Only)
- `/oauth/authorize`, `/oauth/token`, `/.well-known/openid-configuration`, `/oauth/jwks.json`: OAuth 2.0 / OIDC Engine

### Portal Spesifik Role (`web.php`)
- `/admin/*`: Middleware `['auth', 'role:admin']` (Manajemen Sistem, App, User, SIJUNA, Monitoring, Pengumuman)
- `/guru/*`: Middleware `['auth', 'role:teacher']` (Portal Guru & Aplikasi Terkait)
- `/siswa/*`: Middleware `['auth', 'role:student']` (Portal Siswa & Aplikasi Terkait)
- `/dudi/*`: Middleware `['auth', 'role:dudi']` (Portal DUDI & Aplikasi Terkait)

### REST API Gateway (`api.php`)
- `/api/v1/ping` & `/api/v1/validate-client`: Validation & Health Status Publik
- `/api/v1/user`, `/api/v1/user/profile`, `/api/v1/user/roles`: Data Pengguna via OAuth Bearer Token
- `/api/v1/sijuna/students`, `/api/v1/sijuna/teachers`: Data Sinkronisasi SIJUNA via Bearer Token

---

*Dokumen ini dibuat secara otomatis untuk memberikan gambaran alur kerja menyeluruh pada Sistem SiPintu Identity Gateway.*
