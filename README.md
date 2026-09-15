# 🚪 SiPintu Identity & API Gateway

SiPintu adalah platform **Identity & API Gateway** terpusat yang menghubungkan aplikasi-aplikasi internal/eksternal sekolah dengan API SIJUNA.

---

## ✨ Fitur Utama Arsitektur SiPintu

1. **OAuth 2.0 & OpenID Connect (OIDC) SSO**: Otentikasi terpusat untuk aplikasi hilir (*downstream apps*) dengan Single Sign-On (SSO), rotasi refresh token, dan pemetaan peran (*role-based access*).
2. **Push Webhook Real-time & Smart Conflict Resolution**: Sinkronisasi instan data profil dan kata sandi ke aplikasi hilir dengan sistem resolusi konflik otomatis berbasis timestamp `sipintu_last_synced_at` untuk memproteksi editan lokal pengguna.
3. **Integrasi Data SIJUNA API Gateway**: Sinkronisasi otomatis data siswa dan guru dari API SIJUNA dengan proteksi rate limit, background queues, dan proxy API.
4. **Broadcast Pengumuman & WhatsApp Bot**: Notifikasi pengumuman terintegrasi bot WhatsApp Baileys (Node.js) untuk siswa, guru, dan mitra DUDI.
5. **Keamanan Berlapis**: Validasi HMAC SHA-256 (`X-SiPintu-Signature`), Two-Factor Authentication (2FA), dan audit trail menyeluruh.

---

## 📖 Panduan Teknis & Dokumentasi Sistem

Silakan telusuri dokumentasi teknis lengkap berikut untuk implementasi dan pemeliharaan:

* 👉 **[ALUR_SISTEM_SIPINTU.md](./ALUR_SISTEM_SIPINTU.md)** — Arsitektur Sistem, Sequence Diagram SSO, Sinkronisasi Webhook, & Resolusi Konflik
* 👉 **[FLOWCHART_USER_SIPINTU.md](./FLOWCHART_USER_SIPINTU.md)** — Flowchart User Journey, Pengambilan Keputusan Webhook Sync, & Profiling
* 👉 **[API_INTEGRATION_GUIDE.md](./API_INTEGRATION_GUIDE.md)** — Panduan Lengkap Integrasi REST API, OAuth, & Webhook Real-time Downstream
* 👉 **[PANDUAN_SSO_DOWNSTREAM_HYBRID.md](./PANDUAN_SSO_DOWNSTREAM_HYBRID.md)** — Panduan Integrasi Downstream Hybrid (SSO + Login Mandiri)
* 👉 **[SIPINTU_SSO_GUIDE.md](./SIPINTU_SSO_GUIDE.md)** — Panduan Praktis OAuth 2.0 & OIDC SSO
* 👉 **[INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md)** — Panduan Instalasi & Setup Lingkungan Lokal
* 👉 **[WHATSAPP_INTEGRATION_GUIDE.md](./WHATSAPP_INTEGRATION_GUIDE.md)** — Panduan Menghubungkan Bot WhatsApp Baileys

---

