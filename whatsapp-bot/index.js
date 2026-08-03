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

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;
const API_KEY = process.env.API_KEY || 'sipintu_wa_secret_key_2026';
const AUTH_DIR = 'auth_info_baileys';

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const logger = pino({ level: 'silent' });
let sock = null;
let connectionState = 'close';
let lastQr = null;
let lastQrImage = null;
let isConnecting = false;

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
    isConnecting = true;

    try {
        if (sock) {
            try {
                sock.ev.removeAllListeners();
                sock.end(new Error('Reconnecting socket'));
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

        isConnecting = false;

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
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
                connectionState = 'close';
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const errorReason = lastDisconnect?.error?.message || lastDisconnect?.error;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                
                console.log(`[WhatsApp Bot] Koneksi terputus. Status Code: ${statusCode || 'N/A'}, Detail: ${errorReason || 'Unknown'}`);

                if (statusCode === DisconnectReason.loggedOut) {
                    console.log('[WhatsApp Bot] Sesi dikeluarkan oleh pengguna/WhatsApp. Membersihkan folder auth_info_baileys...');
                    cleanAuthFolder();
                }

                if (shouldReconnect) {
                    console.log('[WhatsApp Bot] Mencoba menghubungkan kembali dalam 5 detik...');
                    setTimeout(() => {
                        connectToWhatsApp();
                    }, 5000);
                } else {
                    console.log('[WhatsApp Bot] Sesi di-logout. Memulai ulang koneksi untuk QR code baru...');
                    setTimeout(() => {
                        connectToWhatsApp();
                    }, 2000);
                }
            } else if (connection === 'open') {
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
        console.error('[WhatsApp Bot] Error inisialisasi socket Baileys:', err.message);
        setTimeout(() => {
            connectToWhatsApp();
        }, 5000);
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

    res.json({
        status: 'success',
        connection: connectionState,
        bot_user: sock?.user || null,
        bot_phone: botPhone,
        qr_code: lastQrImage,
        timestamp: new Date().toISOString()
    });
});

// 2. Logout / Reset Bot Session (Ganti Nomor WA Bot)
app.post('/logout', authenticateApiKey, async (req, res) => {
    console.log('[WhatsApp Bot] Request Logout / Ganti Nomor Bot diterima...');

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
