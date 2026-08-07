# Flowchart User Lengkap & Rinci - SiPintu Identity Gateway

Dokumen ini memuat **Flowchart User** secara komprehensif, mencakup seluruh alur perjalanan pengguna (*user journey*), titik keputusan (*decision points*), navigasi halaman, hak akses per peran, integrasi SSO, hingga fitur profil dan logout di **SiPintu Identity Gateway**.

---

## 📋 Daftar Isi
1. [Flowchart Utama Pengguna (End-to-End User Journey)](#1-flowchart-utama-pengguna-end-to-end-user-journey)
2. [Flowchart Detail: Autentikasi & Lupa Password](#2-flowchart-detail-autentikasi--lupa-password)
3. [Flowchart Detail: Portal Admin](#3-flowchart-detail-portal-admin)
4. [Flowchart Detail: Portal User Non-Admin (Guru, Siswa, DUDI)](#4-flowchart-detail-portal-user-non-admin-guru-siswa-dudi)
5. [Flowchart Detail: Single Sign-On (SSO) ke Aplikasi Hilir](#5-flowchart-detail-single-sign-on-sso-ke-aplikasi-hilir)
6. [Flowchart Detail: Manajemen Profil & Keamanan](#6-flowchart-detail-manajemen-profil--keamanan)

---

## 1. Flowchart Utama Pengguna (End-to-End User Journey)

Diagram di bawah ini menggambarkan alur perjalanan pengguna dari saat pertama kali mengakses website hingga masuk ke portal masing-masing atau menggunakan Single Sign-On (SSO).

```mermaid
flowchart TD
    A(["Pengguna Buka Website SiPintu"]) --> B{"Sudah Memiliki Sesi Login?"}
    
    B -- "Belum" --> C["Halaman Login (/login)"]
    B -- "Ya" --> D{"Periksa Peran (Role)"}
    
    C --> E{"Memilih Aksi"}
    E -- "Input Kredensial" --> F["Submit Form Login"]
    E -- "Lupa Password" --> G["Halaman Lupa Password (/forgot-password)"]
    E -- "Akses via SSO App Klien" --> H["Request OAuth Authorize (/oauth/authorize)"]

    G --> G1["Input Email Terdaftar"]
    G1 --> G2["Sistem Kirim Link Reset via Email"]
    G2 --> C

    F --> I{"Validasi Kredensial & Status Account"}
    I -- "Kredensial Salah / Account Non-Aktif" --> J["Tampilkan Pesan Error / Akses Ditolak"]
    J --> C

    I -- "Kredensial Valid & Aktif" --> K["Buat Sesi Login & Log Audit"]
    K --> D

    D -- "Admin" --> L["Dashboard Admin (/admin/dashboard)"]
    D -- "Guru (Teacher)" --> M["Dashboard Guru (/guru/dashboard)"]
    D -- "Siswa (Student)" --> N["Dashboard Siswa (/siswa/dashboard)"]
    D -- "DUDI (Mitra)" --> O["Dashboard DUDI (/dudi/dashboard)"]

    L --> P["Fitur Kelola Sistem & Monitoring"]
    M --> Q["Portal Aplikasi Guru & Profil"]
    N --> R["Portal Aplikasi Siswa & Profil"]
    O --> S["Portal Aplikasi DUDI & Profil"]

    H --> T{"Sesi Login Aktif?"}
    T -- "Tidak" --> C
    T -- "Ya" --> U["Generasi OAuth Auth Code"]
    U --> V["Redirect ke Client App (with code & state)"]
    V --> W(["User Masuk ke Aplikasi Hilir"])
```

---

## 2. Flowchart Detail: Autentikasi & Lupa Password

```mermaid
flowchart TD
    Start(["Mulai"]) --> PageLogin["Tampilkan Halaman Login"]
    PageLogin --> SelectType["Pengguna Memilih Identifier"]
    
    SelectType --> InputForm["Input Credential:
    - Admin: Username / Email
    - Guru: NIP / Email / Username
    - Siswa: NIS / NISN / Email / Username
    - DUDI: Kode Mitra / Email / Username
    - Password"]
    
    InputForm --> ClickLogin["Klik Tombol 'Masuk'"]
    ClickLogin --> ProcessAuth["Process AuthController@login"]
    
    ProcessAuth --> CheckRateLimit{"Apakah Mengalami Failed Login > Limit?"}
    CheckRateLimit -- "Ya" --> BlockIP["Blokir Sementara IP & Catat SecurityLog"]
    BlockIP --> ErrLimit["Tampilkan Pesan: Akun/IP diblokir sementara"]
    ErrLimit --> PageLogin
    
    CheckRateLimit -- "Tidak" --> VerifyCred{"Verifikasi Password (Hash::check)"}
    VerifyCred -- "Salah" --> ErrCred["Tampilkan Pesan: Credential tidak cocok"]
    ErrCred --> PageLogin
    
    VerifyCred -- "Benar" --> CheckStatus{"Cek Field status User"}
    CheckStatus -- "inactive / blocked" --> ErrStatus["Tampilkan Pesan: Akun non-aktif / dibekukan"]
    ErrStatus --> PageLogin
    
    CheckStatus -- "active" --> Regensession["Regenerate Session ID & Simpan AuditLog"]
    Regensession --> RedirectHome["Redirect ke Route / (Home Redirector)"]
    RedirectHome --> End(["Selesai Autentikasi"])
```

---

## 3. Flowchart Detail: Portal Admin

Admin memiliki kontrol penuh atas manajemen identitas, aplikasi klien, sinkronisasi SIJUNA, WhatsApp Bot, dan monitoring.

```mermaid
flowchart TD
    AdminDash(["Masuk Dashboard Admin (/admin/dashboard)"]) --> MenuSelect{"Pilih Menu Navigasi Admin"}
    
    MenuSelect -- "1. Kelola User" --> UsersMgt["Halaman User Management (/admin/users)"]
    UsersMgt --> UserOps{"Aksi User"}
    UserOps -- "Tambah User" --> AddUser["Form User Baru (Admin, Guru, Siswa, DUDI)"]
    UserOps -- "Edit User" --> EditUser["Form Edit Data & Role User"]
    UserOps -- "Update WA" --> PhoneUser["Form Update No. WhatsApp User"]
    UserOps -- "Hapus User" --> DeleteUser["Konfirmasi & Hapus User"]

    MenuSelect -- "2. Kelola Aplikasi" --> AppsMgt["Halaman Aplikasi & OAuth Client (/admin/applications)"]
    AppsMgt --> AppOps{"Aksi Aplikasi"}
    AppOps -- "Tambah App Klien" --> RegisterApp["Input Nama App, Redirect URI, Icon, Kategori"]
    AppOps -- "Regenerate Secret" --> NewSecret["Generasi Ulang Client Secret OAuth"]
    AppOps -- "Test Health" --> PingApp["Ping /health Endpoint Klien"]

    MenuSelect -- "3. Sinkronisasi SIJUNA" --> SijunaMgt["Halaman SIJUNA Integration (/admin/sijuna)"]
    SijunaMgt --> SyncBtn["Klik 'Sinkronkan Data SIJUNA'"]
    SyncBtn --> TriggerSync["Dispatch SyncSijunaStudentsJob & SyncSijunaTeachersJob"]
    TriggerSync --> ViewSyncLogs["Tampilkan Log Hasil Sinkronisasi (sync_logs)"]

    MenuSelect -- "4. Monitoring & Diagnostik" --> MonMgt["Halaman Monitoring (/admin/monitoring)"]
    MonMgt --> MonOps{"Aksi Monitoring"}
    MonOps -- "Run Health Check" --> ExecHealth["Jalankan GatewayHealthValidationService"]
    MonOps -- "Validate Gateway" --> TestGateway["Uji Diagnostik Gateway & REST API"]

    MenuSelect -- "5. WA Bot & Pengumuman" --> AnnMgt["Halaman Pengumuman (/admin/announcements)"]
    AnnMgt --> WABotOps{"Aksi Broadcast"}
    WABotOps -- "Pairing QR Bot" --> ScanQR["Scan QR Code Baileys Bot"]
    WABotOps -- "Kirim Pengumuman" --> SendWA["Dispatch SendWhatsAppAnnouncementJob"]
    WABotOps -- "Toggle Power Bot" --> BotPower["Hidupkan / Matikan Bot Service"]

    MenuSelect -- "6. Audit Logs" --> AuditMgt["Halaman Log Audit (/admin/audit-logs)"]
    AuditMgt --> ViewLogs["Lihat Histori Riwayat Aktivitas & Perubahan Data"]
```

---

## 4. Flowchart Detail: Portal User Non-Admin (Guru, Siswa, DUDI)

Pengguna dengan peran Guru, Siswa, atau DUDI memiliki antarmuka yang bersih dan berfokus pada peluncuran aplikasi (*App Launcher*) serta pengelolaan profil.

```mermaid
flowchart TD
    UserEntry(["Pengguna Masuk ke Dashboard Peran"]) --> LoadApps["Sistem Muat Daftar Aplikasi Terotorisasi"]
    LoadApps --> DisplayDashboard["Tampilkan Halaman Dashboard Portal:
    - Banner Pengumuman Aktif
    - Statistik / Ringkasan Aplikasi
    - Grid Aplikasi Favorit & Semua Aplikasi"]

    DisplayDashboard --> ActionChoice{"Pilih Aksi Pengguna"}
    
    ActionChoice -- "1. Buka Aplikasi Hilir" --> ClickApp["Klik Kartu / Tombol 'Buka Aplikasi'"]
    ClickApp --> LaunchSSO["Inisiasi Redirect SSO ke Application Client_ID"]
    
    ActionChoice -- "2. Toggle Favorit App" --> ClickFav["Klik Ikon Bintang pada Kartu App"]
    ClickFav --> PostFav["POST /applications/{id}/favorite"]
    PostFav --> UpdateFavUI["Perbarui Tampilan Grid Favorit (Alpine.js)"]
    
    ActionChoice -- "3. Filter / Cari App" --> SearchApp["Ketik Kata Kunci / Filter Kategori"]
    SearchApp --> FilterUI["Tampilkan Hasil Filter Aplikasi secara Real-time"]

    ActionChoice -- "4. Kelola Profil" --> NavProfile["Buka Halaman Profil (/profile)"]
    ActionChoice -- "5. Logout" --> ExecLogout["Klik Tombol Logout"]
    ExecLogout --> PostLogout["POST /logout -> Hancurkan Sesi & Redirect /login"]
```

---

## 5. Flowchart Detail: Single Sign-On (SSO) ke Aplikasi Hilir

Mekanisme otentikasi terpusat saat pengguna mencoba masuk ke aplikasi pihak ketiga / aplikasi hilir yang terintegrasi dengan SiPintu.

```mermaid
flowchart TD
    AppClient(["Aplikasi Hilir (Misal: E-Absensi / CBT)"]) --> UserClickSSO["User Klik 'Login dengan SiPintu'"]
    UserClickSSO --> AuthReq["Redirect Browser ke SiPintu:
    GET /oauth/authorize?
    client_id=CLIENT_ID
    &redirect_uri=REDIRECT_URI
    &response_type=code
    &scope=openid profile email
    &state=STATE_STRING"]

    AuthReq --> CheckSession{"Apakah User Sudah Login di SiPintu?"}
    
    CheckSession -- "Belum" --> ShowLogin["Tampilkan Form Login SiPintu"]
    ShowLogin --> UserLoginSubmit["User Input Credential & Submit"]
    UserLoginSubmit --> AuthValid{"Apakah Credential Valid?"}
    AuthValid -- "Tidak" --> ShowLoginErr["Tampilkan Pesan Error Login"]
    ShowLoginErr --> ShowLogin
    AuthValid -- "Ya" --> CreateUserSession["Buat Sesi Login SiPintu"]
    CreateUserSession --> GenCode
    
    CheckSession -- "Sudah" --> GenCode["Generasi Authorization Code
    (Masa Berlaku 10 Menit, OAuthAuthCode Table)"]

    GenCode --> RedirClient["Redirect Browser ke:
    REDIRECT_URI?code=AUTH_CODE&state=STATE_STRING"]

    RedirClient --> ClientBackend["Backend Client App Menerima Code"]
    ClientBackend --> ExchangeToken["POST /oauth/token ke SiPintu:
    client_id, client_secret, code, grant_type"]

    ExchangeToken --> VerifyTokenReq{"Verifikasi client_secret & auth code"}
    VerifyTokenReq -- "Invalid" --> ReturnErr["Response HTTP 400 / 401 Unauthorized"]
    VerifyTokenReq -- "Valid" --> IssueTokens["Terbitkan Token JSON:
    - access_token (Bearer Token)
    - id_token (JWT signed RS256)
    - expires_in (3600 detik)"]

    IssueTokens --> ClientReqUser["Client App Request User Info:
    GET /api/v1/user dengan Header Bearer Token"]

    ClientReqUser --> SiPintuAPI["SiPintu ApiIdentityController@user"]
    SiPintuAPI --> ReturnUserData["Kembalikan JSON User Profile & Role"]
    ReturnUserData --> ClientLoginSuccess(["User Berhasil Masuk ke Aplikasi Hilir"])
```

---

## 6. Flowchart Detail: Manajemen Profil & Keamanan

```mermaid
flowchart TD
    StartProfile(["User Buka Halaman Profil (/profile)"]) --> DisplayProfile["Tampilkan Data Profil:
    - Nama Lengkap & Username
    - Email & Nomor WhatsApp
    - NIP / NIS / Kode DUDI & Peran
    - Form Ubah Profil & Password"]

    DisplayProfile --> ProfileAction{"Pilih Form Perubahan"}

    ProfileAction -- "Ubah Data Diri" --> SubmitProfile["Submit Form Update Profil (POST /profile)"]
    SubmitProfile --> ValidateProfile{"Validasi Format Email & Data"}
    ValidateProfile -- "Invalid" --> ProfileErr["Tampilkan Pesan Error Validasi"]
    ProfileErr --> DisplayProfile
    ValidateProfile -- "Valid" --> SaveProfile["Simpan Perubahan ke Database User"]
    SaveProfile --> AuditProfile["Catat AuditLog Perubahan Profil"]
    AuditProfile --> ProfileSuccess["Tampilkan Pesan Sukses Update Profil"]

    ProfileAction -- "Ubah Password" --> SubmitPass["Submit Form Update Password (PUT /profile/password)"]
    SubmitPass --> ValidatePass{"Validasi Password Lama & Format Baru"}
    ValidatePass -- "Password Lama Salah / Konfirmasi Tidak Cocok" --> PassErr["Tampilkan Pesan Error Password"]
    PassErr --> DisplayProfile
    ValidatePass -- "Valid" --> HashPass["Hash Password Baru (Hash::make)"]
    HashPass --> SavePass["Update Column password di Tabel users"]
    SavePass --> AuditPass["Catat AuditLog Perubahan Password"]
    AuditPass --> PassSuccess["Tampilkan Pesan Sukses & Minta Login Ulang jika Perlu"]
```

---

*Dokumen Flowchart User ini disusun secara mendalam untuk acuan pengembang, desainer sistem, maupun panduan pengguna SiPintu Identity Gateway.*
