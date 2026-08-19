import dns from 'node:dns';
dns.setDefaultResultOrder('ipv4first');

import fs from 'node:fs';
import path from 'node:path';
import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import pino from 'pino';
import qrcodeTerminal from 'qrcode-terminal';
import QRCode from 'qrcode';
import makeWASocket, {
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    Browsers
} from '@whiskeysockets/baileys';

import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config({ path: path.join(__dirname, '.env') });
dotenv.config();

const app = express();
const PORT = process.env.WA_BOT_PORT || process.env.PORT || 3000;
const API_KEY = process.env.WA_BOT_API_KEY || process.env.API_KEY || 'sipintu_wa_secret_key_2026';
const AUTH_DIR = path.join(__dirname, 'auth_info_baileys');

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const logger = pino({ level: 'silent' });
let sock = null;
let connectionState = 'close';
let lastQr = null;
let lastQrImage = null;
let isConnecting = false;
let isBotEnabled = true;
let isManualLogoutRequested = false;

// Anti-crash process error handlers
process.on('uncaughtException', (err) => {
    console.error('[WhatsApp Bot Error] Uncaught Exception:', err?.message || err);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('[WhatsApp Bot Error] Unhandled Rejection:', reason?.message || reason);
});

// Middleware Auth API Key
const authenticateApiKey = (req, res, next) => {
    const requestKey = req.headers['x-api-key'] || req.query.api_key;
    if (!requestKey || requestKey !== API_KEY) {
        return res.status(401).json({
            status: 'error',
            message: 'Unauthorized: Invalid API Key'
        });
    }
    next();
};

async function connectToWhatsApp() {
    if (isConnecting) return;
    if (sock && (connectionState === 'connecting' || connectionState === 'open')) return;

    isConnecting = true;
    connectionState = 'connecting';

    try {
        if (sock) {
            try {
                sock.ev.removeAllListeners();
                sock.end();
            } catch (e) {
                // Ignore cleanup error
            }
            sock = null;
        }

        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version } = await fetchLatestBaileysVersion();

        console.log(`[WhatsApp Bot] Memulai Bot Baileys v${version.join('.')}...`);

        sock = makeWASocket({
            version,
            logger,
            auth: state,
            browser: Browsers.ubuntu('Chrome'),
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
            keepAliveIntervalMs: 25000,
            printQRInTerminal: false
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (connection) {
                connectionState = connection;
            }

            if (qr) {
                isConnecting = false;
                lastQr = qr;
                try {
                    lastQrImage = await QRCode.toDataURL(qr);
                } catch (e) {
                    console.error('[WhatsApp Bot] Error generating QR Data URL:', e.message);
                }
                console.log('\n==================================================');
                console.log('📱 SCAN QR CODE DI BAWAH UNTUK LOGIN WHATSAPP:');
                console.log('==================================================');
                qrcodeTerminal.generate(qr, { small: true });
                console.log('==================================================\n');
            }

            if (connection === 'close') {
                isConnecting = false;
                connectionState = 'close';
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const errorReason = lastDisconnect?.error?.message || lastDisconnect?.error;
                
                console.log(`[WhatsApp Bot] Koneksi terputus. Status Code: ${statusCode || 'N/A'}, Detail: ${errorReason || 'Unknown'}`);

                const isLoggedOut = statusCode === DisconnectReason.loggedOut 
                    || statusCode === 401 
                    || statusCode === 403
                    || statusCode === DisconnectReason.badSession
                    || isManualLogoutRequested;

                if (isLoggedOut) {
                    console.log('[WhatsApp Bot] Sesi terputus / tidak terhubung dengan nomor. Membersihkan auth_info_baileys untuk membuat QR Code baru...');
                    cleanAuthFolder();
                    lastQr = null;
                    lastQrImage = null;
                    isManualLogoutRequested = false;
                } else {
                    console.log('[WhatsApp Bot] Terputus sementara. Mempertahankan sesi & mencoba menghubungkan ulang...');
                }

                const retryDelay = isLoggedOut ? 1500 : 3000;
                console.log(`[WhatsApp Bot] Menghubungkan kembali dalam ${retryDelay / 1000} detik...`);
                setTimeout(() => {
                    connectToWhatsApp();
                }, retryDelay);
            } else if (connection === 'open') {
                isConnecting = false;
                connectionState = 'open';
                lastQr = null;
                lastQrImage = null;
                const botPhone = sock.user?.id?.split(':')[0] || 'Unknown';
                console.log('\n==================================================');
                console.log('🚀 WHATSAPP BOT BAILEYS BERHASIL TERHUBUNG!');
                console.log(`Nomor Terhubung: ${botPhone}`);
                console.log('==================================================\n');
            }
        });
    } catch (err) {
        isConnecting = false;
        connectionState = 'close';
        console.error('[WhatsApp Bot] Error inisialisasi socket Baileys:', err.message);
        setTimeout(() => {
            connectToWhatsApp();
        }, 3000);
    }
}

function cleanAuthFolder() {
    try {
        if (fs.existsSync(AUTH_DIR)) {
            fs.rmSync(AUTH_DIR, { recursive: true, force: true });
            console.log('[WhatsApp Bot] Folder auth_info_baileys berhasil dihapus.');
        }
    } catch (err) {
        console.error('[WhatsApp Bot] Gagal menghapus folder auth_info_baileys:', err.message);
    }
}

// REST API Endpoints

// 1. Health check & status
app.get('/status', (req, res) => {
    const rawId = sock?.user?.id || '';
    const botPhone = rawId ? rawId.split(':')[0] : null;
    const isConnected = connectionState === 'open' && Boolean(botPhone);

    // Jika bot tidak terhubung dengan nomor HP & tidak sedang connecting & belum ada QR Code, picu regenerasi QR Code
    if (!isConnected && !lastQrImage && !isConnecting && connectionState === 'close') {
        console.log('[WhatsApp Bot] Bot tidak terhubung dengan nomor dan QR code belum aktif. Memulai pembuatan QR Code...');
        connectToWhatsApp();
    }

    res.json({
        status: 'success',
        connection: isConnected ? 'open' : connectionState,
        bot_user: isConnected ? sock?.user : null,
        bot_phone: isConnected ? botPhone : null,
        bot_enabled: isBotEnabled,
        qr_code: isConnected ? null : lastQrImage,
        timestamp: new Date().toISOString()
    });
});

// 1.5 Toggle Bot Power (ON/OFF) Without Logout
app.post('/toggle-power', authenticateApiKey, (req, res) => {
    if (typeof req.body.enabled === 'boolean') {
        isBotEnabled = req.body.enabled;
    } else {
        isBotEnabled = !isBotEnabled;
    }

    console.log(`[WhatsApp Bot] Status bot diubah ke: ${isBotEnabled ? 'AKTIF (ON)' : 'NON-AKTIF (OFF)'}`);

    return res.json({
        status: 'success',
        bot_enabled: isBotEnabled,
        message: `Bot WhatsApp berhasil ${isBotEnabled ? 'diaktifkan (ON)' : 'dinonaktifkan (OFF)'}.`
    });
});

// 2. Logout / Reset Bot Session (Ganti Nomor WA Bot)
app.post('/logout', authenticateApiKey, async (req, res) => {
    console.log('[WhatsApp Bot] Request Logout / Ganti Nomor Bot diterima...');
    isManualLogoutRequested = true;

    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (e) {
                // Ignore logout error if socket is already closed
            }
            try {
                sock.ev.removeAllListeners();
                sock.end();
            } catch (e) {}
        }

        sock = null;
        connectionState = 'close';
        lastQr = null;
        lastQrImage = null;

        cleanAuthFolder();

        // Restart socket asynchronously to produce fresh QR Code
        setTimeout(() => {
            connectToWhatsApp();
        }, 1500);

        return res.json({
            status: 'success',
            message: 'Sesi bot WhatsApp berhasil di-logout. Silakan tunggu QR Code baru untuk scan nomor baru.'
        });
    } catch (error) {
        console.error('[WhatsApp Bot] Error during logout:', error);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal melakukan logout bot: ' + error.message
        });
    }
});

// 3. Send Message Endpoint (Protected by API Key)
app.post('/send-message', authenticateApiKey, async (req, res) => {
    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({
            status: 'error',
            message: 'Parameter "phone" dan "message" wajib diisi.'
        });
    }

    if (!isBotEnabled) {
        return res.status(503).json({
            status: 'error',
            message: 'Bot WhatsApp sedang dalam posisi NON-AKTIF (OFF). Silakan aktifkan bot terlebih dahulu.'
        });
    }

    if (connectionState !== 'open' || !sock) {
        return res.status(503).json({
            status: 'error',
            message: 'Bot WhatsApp belum terhubung/terotentikasi. Silakan scan QR code terlebih dahulu.'
        });
    }

    try {
        // Clean phone number format
        let cleanPhone = String(phone).replace(/[^\d]/g, '');
        if (cleanPhone.startsWith('0')) {
            cleanPhone = '62' + cleanPhone.slice(1);
        } else if (cleanPhone.startsWith('8')) {
            cleanPhone = '62' + cleanPhone;
        }

        const jid = `${cleanPhone}@s.whatsapp.net`;

        // Send text message
        const response = await sock.sendMessage(jid, { text: message });

        return res.json({
            status: 'success',
            message: 'Pesan WhatsApp berhasil dikirim.',
            data: {
                recipient: cleanPhone,
                message_id: response.key.id,
                timestamp: response.messageTimestamp
            }
        });
    } catch (error) {
        console.error('[WhatsApp Bot] Failed to send message:', error);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal mengirim pesan: ' + error.message
        });
    }
});

const server = app.listen(PORT, () => {
    console.log(`==================================================`);
    console.log(`⚡ WhatsApp Bot Server running on http://127.0.0.1:${PORT}`);
    console.log(`==================================================`);
    connectToWhatsApp();
});

server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`\n❌ ERROR: Port ${PORT} sedang digunakan oleh proses lain!`);
        console.error(`Silakan hentikan proses pada port ${PORT} terlebih dahulu.`);
        console.error(`Atau jalankan perintah: fuser -k ${PORT}/tcp\n`);
        process.exit(1);
    } else {
        console.error('Server error:', err);
    }
});
