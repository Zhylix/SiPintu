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
const PORT = process.env.WA_BOT_PORT || process.env.PORT || 3789;
const API_KEY = process.env.WA_BOT_API_KEY || process.env.API_KEY || 'sipintu_wa_secret_key_2026';
const BASE_AUTH_DIR = path.join(__dirname, 'auth_info_baileys');
const SESSIONS_DIR = path.join(BASE_AUTH_DIR, 'sessions');

// Ensure base directories exist
if (!fs.existsSync(BASE_AUTH_DIR)) {
    fs.mkdirSync(BASE_AUTH_DIR, { recursive: true });
}
if (!fs.existsSync(SESSIONS_DIR)) {
    fs.mkdirSync(SESSIONS_DIR, { recursive: true });
}

// Migrate legacy single session auth files to sessions/default if present
function migrateLegacyAuth() {
    try {
        const legacyCredsPath = path.join(BASE_AUTH_DIR, 'creds.json');
        const defaultSessionDir = path.join(SESSIONS_DIR, 'default');
        if (fs.existsSync(legacyCredsPath) && !fs.existsSync(defaultSessionDir)) {
            console.log('[WhatsApp Bot] Memigrasikan kredensial sesi lama ke sessions/default...');
            fs.mkdirSync(defaultSessionDir, { recursive: true });
            const files = fs.readdirSync(BASE_AUTH_DIR);
            for (const file of files) {
                if (file === 'sessions') continue;
                const src = path.join(BASE_AUTH_DIR, file);
                const dest = path.join(defaultSessionDir, file);
                if (fs.statSync(src).isFile()) {
                    fs.copyFileSync(src, dest);
                    fs.unlinkSync(src);
                }
            }
            console.log('[WhatsApp Bot] Migrasi sesi lama selesai.');
        }
    } catch (err) {
        console.error('[WhatsApp Bot] Gagal migrasi sesi lama:', err.message);
    }
}
migrateLegacyAuth();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const logger = pino({ level: 'silent' });

// Global package tracking
let latestBaileysPackageVersion = null;
let currentInstalledBaileysVersion = '6.7.18';
let isBaileysUpdateAvailable = false;
let cachedProtocolVersion = [2, 3000, 1019846200];

// Anti-crash process error handlers
process.on('uncaughtException', (err) => {
    console.error('[WhatsApp Bot Error] Uncaught Exception:', err?.message || err);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('[WhatsApp Bot Error] Unhandled Rejection:', reason?.message || reason);
});

// Check update Baileys from npm registry
async function checkBaileysNpmUpdate() {
    try {
        const packageJsonPath = path.join(__dirname, 'package.json');
        if (fs.existsSync(packageJsonPath)) {
            const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
            const installed = pkg.dependencies?.['@whiskeysockets/baileys'];
            if (installed) {
                currentInstalledBaileysVersion = installed.replace(/[\^~]/g, '');
            }
        }

        const res = await fetch('https://registry.npmjs.org/@whiskeysockets/baileys/latest', {
            headers: { 'Accept': 'application/json' },
            signal: AbortSignal.timeout(6000)
        });

        if (res.ok) {
            const data = await res.json();
            latestBaileysPackageVersion = data.version || null;

            if (latestBaileysPackageVersion && currentInstalledBaileysVersion) {
                isBaileysUpdateAvailable = latestBaileysPackageVersion !== currentInstalledBaileysVersion;
            }

            console.log(`[Baileys NPM Check] Terinstall: v${currentInstalledBaileysVersion} | Latest NPM: v${latestBaileysPackageVersion || 'Unknown'} ${isBaileysUpdateAvailable ? '[UPDATE TERSEDIA]' : '[VERSI TERBARU]'}`);
        }
    } catch (err) {
        console.error('[Baileys NPM Check] Gagal mengecek update npm Baileys:', err.message);
    }
}

setInterval(() => {
    checkBaileysNpmUpdate();
}, 6 * 60 * 60 * 1000);

// Fetch protocol version once on startup
async function initProtocolVersion() {
    try {
        const fetched = await Promise.race([
            fetchLatestBaileysVersion(),
            new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout fetching Baileys version')), 7000))
        ]);
        if (fetched?.version) {
            cachedProtocolVersion = fetched.version;
        }
    } catch (err) {
        console.warn('[WhatsApp Bot] Warning: Fallback ke default protocol version:', err.message);
    }
}

// ---------------------------------------------------------------------
// Multi-Session Manager
// ---------------------------------------------------------------------
const sessions = new Map();

function normalizeSessionId(rawId) {
    if (!rawId || typeof rawId !== 'string') return 'default';
    const cleaned = rawId.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '_');
    return cleaned || 'default';
}

function getSessionAuthDir(sessionId) {
    return path.join(SESSIONS_DIR, sessionId);
}

function getOrCreateSession(rawId) {
    const id = normalizeSessionId(rawId);
    if (!sessions.has(id)) {
        sessions.set(id, {
            id,
            sock: null,
            connectionState: 'close',
            lastQr: null,
            lastQrImage: null,
            isConnecting: false,
            isBotEnabled: true,
            isManualLogoutRequested: false,
            reconnectAttempts: 0,
            watchdogTimer: null,
            reconnectTimer: null,
            phone: null,
            user: null,
            updatedAt: new Date()
        });
    }
    return sessions.get(id);
}

function cleanSessionAuth(sessionId) {
    const id = normalizeSessionId(sessionId);
    const sessionDir = getSessionAuthDir(id);
    try {
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
            console.log(`[WhatsApp Bot (${id})] Folder sesi berhasil dibersihkan.`);
        }
    } catch (err) {
        console.error(`[WhatsApp Bot (${id})] Gagal menghapus folder sesi:`, err.message);
    }
}

async function connectSession(rawId) {
    const session = getOrCreateSession(rawId);
    const id = session.id;

    if (!session.isBotEnabled) {
        console.log(`[WhatsApp Bot (${id})] Sesi sedang dalam mode hibernate (OFF). Tidak melakukan koneksi.`);
        session.isConnecting = false;
        session.connectionState = 'close';
        return;
    }

    if (session.isConnecting) return;
    if (session.sock && (session.connectionState === 'connecting' || session.connectionState === 'open')) return;

    session.isConnecting = true;
    session.connectionState = 'connecting';

    try {
        if (session.sock) {
            try {
                session.sock.ev.removeAllListeners();
                session.sock.end();
            } catch (e) {}
            session.sock = null;
        }

        if (session.watchdogTimer) clearTimeout(session.watchdogTimer);
        session.watchdogTimer = setTimeout(() => {
            if (session.isConnecting && session.connectionState !== 'open') {
                console.warn(`[WhatsApp Bot (${id}) Watchdog] Inisialisasi koneksi menggantung > 45 detik. Mengulang koneksi ulang...`);
                session.isConnecting = false;
                session.connectionState = 'close';
                connectSession(id);
            }
        }, 45000);

        const sessionAuthDir = getSessionAuthDir(id);
        if (!fs.existsSync(sessionAuthDir)) {
            fs.mkdirSync(sessionAuthDir, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(sessionAuthDir);

        console.log(`[WhatsApp Bot (${id})] Memulai socket Baileys...`);

        const sock = makeWASocket({
            version: cachedProtocolVersion,
            logger,
            auth: state,
            browser: Browsers.ubuntu(`SiPintu-${id}`),
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
            keepAliveIntervalMs: 25000,
            printQRInTerminal: false
        });

        session.sock = sock;

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (connection) {
                session.connectionState = connection;
                session.updatedAt = new Date();
            }

            if (qr) {
                if (session.watchdogTimer) clearTimeout(session.watchdogTimer);
                session.isConnecting = false;
                session.lastQr = qr;
                try {
                    session.lastQrImage = await QRCode.toDataURL(qr);
                } catch (e) {
                    console.error(`[WhatsApp Bot (${id})] Error generating QR Data URL:`, e.message);
                }
                console.log('==================================================');
                console.log(`[QR CODE] SCAN QR CODE DI BAWAH UNTUK SESI [${id}]:`);
                console.log('==================================================');
                qrcodeTerminal.generate(qr, { small: true });
                console.log('==================================================\n');
            }

            if (connection === 'close') {
                session.isConnecting = false;
                session.connectionState = 'close';
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const errorReason = lastDisconnect?.error?.message || lastDisconnect?.error;

                console.log(`[WhatsApp Bot (${id})] Koneksi terputus. Status Code: ${statusCode || 'N/A'}, Detail: ${errorReason || 'Unknown'}`);

                const isLoggedOut = statusCode === DisconnectReason.loggedOut
                    || statusCode === 401
                    || statusCode === 403
                    || statusCode === DisconnectReason.badSession
                    || session.isManualLogoutRequested;

                if (isLoggedOut) {
                    console.log(`[WhatsApp Bot (${id})] Sesi terputus / logged out. Membersihkan auth folder...`);
                    cleanSessionAuth(id);
                    session.lastQr = null;
                    session.lastQrImage = null;
                    session.phone = null;
                    session.user = null;
                    session.isManualLogoutRequested = false;
                }

                session.reconnectAttempts++;
                const baseDelay = isLoggedOut ? 1500 : 3000;
                const retryDelay = Math.min(30000, Math.round(baseDelay * Math.pow(1.3, Math.min(session.reconnectAttempts, 6))));

                console.log(`[WhatsApp Bot (${id})] Percobaan reconnect #${session.reconnectAttempts}. Menghubungkan kembali dalam ${retryDelay / 1000} detik...`);
                if (session.reconnectTimer) clearTimeout(session.reconnectTimer);
                session.reconnectTimer = setTimeout(() => {
                    if (session.isBotEnabled) {
                        connectSession(id);
                    }
                }, retryDelay);
            } else if (connection === 'open') {
                if (session.watchdogTimer) clearTimeout(session.watchdogTimer);
                session.isConnecting = false;
                session.connectionState = 'open';
                session.reconnectAttempts = 0;
                session.lastQr = null;
                session.lastQrImage = null;

                const botPhone = sock.user?.id?.split(':')[0] || 'Unknown';
                session.phone = botPhone;
                session.user = sock.user;

                console.log('\n==================================================');
                console.log(`[SUCCESS] SESI [${id}] BERHASIL TERHUBUNG!`);
                console.log(`Nomor Terhubung: ${botPhone}`);
                console.log('==================================================\n');
            }
        });
    } catch (err) {
        session.isConnecting = false;
        session.connectionState = 'close';
        session.reconnectAttempts++;
        console.error(`[WhatsApp Bot (${id})] Error inisialisasi socket Baileys:`, err.message);
        const retryDelay = Math.min(30000, Math.round(3000 * Math.pow(1.3, Math.min(session.reconnectAttempts, 6))));
        if (session.reconnectTimer) clearTimeout(session.reconnectTimer);
        session.reconnectTimer = setTimeout(() => {
            if (session.isBotEnabled) {
                connectSession(id);
            }
        }, retryDelay);
    }
}

// Initialize all saved sessions found in auth_info_baileys/sessions/
function initSavedSessions() {
    try {
        if (!fs.existsSync(SESSIONS_DIR)) return;
        const entries = fs.readdirSync(SESSIONS_DIR, { withFileTypes: true });
        const sessionDirs = entries.filter(e => e.isDirectory()).map(e => e.name);

        console.log(`[WhatsApp Bot] Memuat ${sessionDirs.length} sesi tersimpan dari disk...`);
        for (const dirName of sessionDirs) {
            connectSession(dirName);
        }

        // Always ensure default session is initialized even if empty
        if (!sessionDirs.includes('default')) {
            connectSession('default');
        }
    } catch (err) {
        console.error('[WhatsApp Bot] Gagal memuat sesi tersimpan:', err.message);
        connectSession('default');
    }
}

// ---------------------------------------------------------------------
// Middleware Auth API Key
// ---------------------------------------------------------------------
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

// ---------------------------------------------------------------------
// REST API Endpoints
// ---------------------------------------------------------------------

// Helper to format session response
function formatSessionData(session) {
    const rawId = session.sock?.user?.id || '';
    const botPhone = rawId ? rawId.split(':')[0] : (session.phone || null);
    const isConnected = session.connectionState === 'open' && Boolean(botPhone);

    return {
        session_id: session.id,
        connection: isConnected ? 'open' : session.connectionState,
        bot_user: isConnected ? session.sock?.user : null,
        bot_phone: isConnected ? botPhone : null,
        bot_enabled: session.isBotEnabled,
        qr_code: isConnected ? null : session.lastQrImage,
        reconnect_attempts: session.reconnectAttempts,
        updated_at: session.updatedAt
    };
}

// 1. Root / Health check & Default Session Status (Backward Compatible)
app.get('/status', (req, res) => {
    const defaultSession = getOrCreateSession('default');
    const rawId = defaultSession.sock?.user?.id || '';
    const botPhone = rawId ? rawId.split(':')[0] : (defaultSession.phone || null);
    const isConnected = defaultSession.connectionState === 'open' && Boolean(botPhone);

    // Trigger QR generation if default session is cold
    if (defaultSession.isBotEnabled && !isConnected && !defaultSession.lastQrImage && !defaultSession.isConnecting && defaultSession.connectionState === 'close' && defaultSession.reconnectAttempts === 0) {
        console.log('[WhatsApp Bot] Memicu koneksi sesi default...');
        connectSession('default');
    }

    const sessionSummary = {};
    for (const [id, s] of sessions.entries()) {
        sessionSummary[id] = {
            connection: s.connectionState === 'open' && s.phone ? 'open' : s.connectionState,
            bot_phone: s.phone,
            bot_enabled: s.isBotEnabled
        };
    }

    res.json({
        status: 'success',
        session_id: 'default',
        connection: isConnected ? 'open' : defaultSession.connectionState,
        bot_user: isConnected ? defaultSession.sock?.user : null,
        bot_phone: isConnected ? botPhone : null,
        bot_enabled: defaultSession.isBotEnabled,
        qr_code: isConnected ? null : defaultSession.lastQrImage,
        sessions_count: sessions.size,
        sessions: sessionSummary,
        baileys_info: {
            installed_package_version: currentInstalledBaileysVersion,
            latest_npm_version: latestBaileysPackageVersion,
            update_available: isBaileysUpdateAvailable,
            protocol_version: cachedProtocolVersion.join('.')
        },
        timestamp: new Date().toISOString()
    });
});

// 1.2 Check Baileys Package Update
app.get('/check-update', async (req, res) => {
    await checkBaileysNpmUpdate();
    return res.json({
        status: 'success',
        installed_package_version: currentInstalledBaileysVersion,
        latest_npm_version: latestBaileysPackageVersion,
        update_available: isBaileysUpdateAvailable,
        message: isBaileysUpdateAvailable
            ? `Versi baru @whiskeysockets/baileys (v${latestBaileysPackageVersion}) tersedia di npm!`
            : `Package @whiskeysockets/baileys sudah versi terbaru (v${currentInstalledBaileysVersion}).`
    });
});

// 2. List All Active Sessions
app.get('/sessions', authenticateApiKey, (req, res) => {
    const list = [];
    for (const [_, s] of sessions.entries()) {
        list.push(formatSessionData(s));
    }
    return res.json({
        status: 'success',
        total: list.length,
        sessions: list
    });
});

// 3. Get Specific Session Status (e.g. GET /session/sijuna/status)
app.get('/session/:sessionId/status', (req, res) => {
    const id = normalizeSessionId(req.params.sessionId);
    const session = getOrCreateSession(id);
    const isConnected = session.connectionState === 'open' && Boolean(session.phone);

    // Auto connect / create QR if cold
    if (session.isBotEnabled && !isConnected && !session.lastQrImage && !session.isConnecting && session.connectionState === 'close' && session.reconnectAttempts === 0) {
        connectSession(id);
    }

    return res.json({
        status: 'success',
        ...formatSessionData(session)
    });
});

// 4. Start / Wake Up a Specific Session (POST /session/:sessionId/start)
app.post('/session/:sessionId/start', authenticateApiKey, (req, res) => {
    const id = normalizeSessionId(req.params.sessionId);
    const session = getOrCreateSession(id);
    session.isBotEnabled = true;
    session.reconnectAttempts = 0;

    if (session.connectionState !== 'open' && !session.isConnecting) {
        connectSession(id);
    }

    return res.json({
        status: 'success',
        message: `Sesi [${id}] berhasil dimulai.`,
        session: formatSessionData(session)
    });
});

// Helper for Toggle Power
function executeTogglePower(rawSessionId, enabledInput, res) {
    const id = normalizeSessionId(rawSessionId);
    const session = getOrCreateSession(id);

    if (typeof enabledInput === 'boolean') {
        session.isBotEnabled = enabledInput;
    } else {
        session.isBotEnabled = !session.isBotEnabled;
    }

    if (!session.isBotEnabled) {
        console.log(`[WhatsApp Bot (${id})] Mode Hibernate diaktifkan. Menidurkan socket...`);
        if (session.watchdogTimer) clearTimeout(session.watchdogTimer);
        if (session.reconnectTimer) clearTimeout(session.reconnectTimer);
        if (session.sock) {
            try {
                session.sock.ev.removeAllListeners();
                session.sock.end();
            } catch (e) {}
            session.sock = null;
        }
        session.connectionState = 'close';
        session.isConnecting = false;
        session.lastQr = null;
        session.lastQrImage = null;
    } else {
        console.log(`[WhatsApp Bot (${id})] Mengaktifkan bot kembali dari mode sleep...`);
        session.reconnectAttempts = 0;
        if (session.connectionState !== 'open' && !session.isConnecting) {
            connectSession(id);
        }
    }

    return res.json({
        status: 'success',
        session_id: id,
        bot_enabled: session.isBotEnabled,
        connection: session.connectionState,
        message: session.isBotEnabled
            ? `Bot WhatsApp sesi [${id}] berhasil diaktifkan (ON).`
            : `Bot WhatsApp sesi [${id}] berhasil ditidurkan (OFF / Hemat RAM).`
    });
}

// Helper for Session Logout
async function executeLogoutSession(rawSessionId, res) {
    const id = normalizeSessionId(rawSessionId);
    const session = getOrCreateSession(id);
    console.log(`[WhatsApp Bot (${id})] Request Logout diterima...`);
    session.isManualLogoutRequested = true;

    try {
        if (session.sock) {
            try {
                await session.sock.logout();
            } catch (e) {}
            try {
                session.sock.ev.removeAllListeners();
                session.sock.end();
            } catch (e) {}
        }

        session.sock = null;
        session.connectionState = 'close';
        session.lastQr = null;
        session.lastQrImage = null;
        session.phone = null;
        session.user = null;

        cleanSessionAuth(id);

        setTimeout(() => {
            connectSession(id);
        }, 1500);

        return res.json({
            status: 'success',
            session_id: id,
            message: `Sesi [${id}] berhasil di-logout. Silakan tunggu QR Code baru.`
        });
    } catch (error) {
        console.error(`[WhatsApp Bot (${id})] Error during logout:`, error);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal melakukan logout bot: ' + error.message
        });
    }
}

// 5. Toggle Power on Specific Session (POST /session/:sessionId/toggle-power)
app.post('/session/:sessionId/toggle-power', authenticateApiKey, (req, res) => {
    return executeTogglePower(req.params.sessionId, req.body.enabled, res);
});

// 5.1 Backward compatible toggle-power for default session
app.post('/toggle-power', authenticateApiKey, (req, res) => {
    const targetSessionId = req.body?.session_id || 'default';
    return executeTogglePower(targetSessionId, req.body?.enabled, res);
});

// 6. Logout Specific Session (POST /session/:sessionId/logout)
app.post('/session/:sessionId/logout', authenticateApiKey, async (req, res) => {
    return await executeLogoutSession(req.params.sessionId, res);
});

// 6.1 Backward compatible logout for default session
app.post('/logout', authenticateApiKey, async (req, res) => {
    const targetSessionId = req.body?.session_id || 'default';
    return await executeLogoutSession(targetSessionId, res);
});

// 7. Delete / Remove Session Entirely (DELETE /session/:sessionId)
app.delete('/session/:sessionId', authenticateApiKey, (req, res) => {
    const id = normalizeSessionId(req.params.sessionId);
    if (id === 'default') {
        return res.status(400).json({
            status: 'error',
            message: 'Sesi default tidak boleh dihapus secara permanen. Gunakan logout jika ingin mengganti nomor.'
        });
    }

    if (sessions.has(id)) {
        const session = sessions.get(id);
        if (session.sock) {
            try {
                session.sock.ev.removeAllListeners();
                session.sock.end();
            } catch (e) {}
        }
        sessions.delete(id);
    }

    cleanSessionAuth(id);

    return res.json({
        status: 'success',
        message: `Sesi [${id}] berhasil dihapus dari sistem.`
    });
});

// 8. Send Message Function Core
async function executeSendMessage(sessionId, phone, message) {
    const id = normalizeSessionId(sessionId);
    const session = sessions.get(id);

    if (!session) {
        return {
            status: 404,
            payload: {
                status: 'error',
                message: `Sesi WhatsApp [${id}] tidak ditemukan atau belum pernah diinisialisasi.`
            }
        };
    }

    if (!session.isBotEnabled) {
        return {
            status: 503,
            payload: {
                status: 'error',
                message: `Bot WhatsApp pada sesi [${id}] sedang NON-AKTIF (OFF).`
            }
        };
    }

    if (session.connectionState !== 'open' || !session.sock) {
        return {
            status: 503,
            payload: {
                status: 'error',
                message: `Bot WhatsApp pada sesi [${id}] belum terhubung/terotentikasi. Silakan scan QR code terlebih dahulu.`
            }
        };
    }

    // Clean phone number format
    let cleanPhone = String(phone).replace(/[^\d]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.slice(1);
    } else if (cleanPhone.startsWith('8')) {
        cleanPhone = '62' + cleanPhone;
    }

    const jid = `${cleanPhone}@s.whatsapp.net`;
    const response = await session.sock.sendMessage(jid, { text: message });

    return {
        status: 200,
        payload: {
            status: 'success',
            message: 'Pesan WhatsApp berhasil dikirim.',
            data: {
                session_id: id,
                sender_phone: session.phone,
                recipient: cleanPhone,
                message_id: response.key.id,
                timestamp: response.messageTimestamp
            }
        }
    };
}

// 8.1 Send Message Endpoint for Specific Session (POST /session/:sessionId/send-message)
app.post('/session/:sessionId/send-message', authenticateApiKey, async (req, res) => {
    const { phone, message } = req.body;
    const sessionId = req.params.sessionId;

    if (!phone || !message) {
        return res.status(400).json({
            status: 'error',
            message: 'Parameter "phone" dan "message" wajib diisi.'
        });
    }

    try {
        const result = await executeSendMessage(sessionId, phone, message);
        return res.status(result.status).json(result.payload);
    } catch (error) {
        console.error(`[WhatsApp Bot (${sessionId})] Failed to send message:`, error);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal mengirim pesan: ' + error.message
        });
    }
});

// 8.2 Send Message Endpoint (Backward Compatible POST /send-message)
app.post('/send-message', authenticateApiKey, async (req, res) => {
    const { phone, message, session_id } = req.body;
    const targetSession = session_id || 'default';

    if (!phone || !message) {
        return res.status(400).json({
            status: 'error',
            message: 'Parameter "phone" dan "message" wajib diisi.'
        });
    }

    try {
        const result = await executeSendMessage(targetSession, phone, message);
        return res.status(result.status).json(result.payload);
    } catch (error) {
        console.error(`[WhatsApp Bot (${targetSession})] Failed to send message:`, error);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal mengirim pesan: ' + error.message
        });
    }
});

// ---------------------------------------------------------------------
// Server Initialization
// ---------------------------------------------------------------------
const server = app.listen(PORT, '127.0.0.1', async () => {
    console.log(`==================================================`);
    console.log(`[SERVER] Multi-Session WhatsApp Bot Gateway`);
    console.log(`Listening on http://127.0.0.1:${PORT}`);
    console.log(`Sessions Storage: ${SESSIONS_DIR}`);
    console.log(`==================================================`);
    await checkBaileysNpmUpdate();
    await initProtocolVersion();
    initSavedSessions();
});

server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`\n[ERROR] Port ${PORT} sedang digunakan oleh proses lain!`);
        console.error(`Silakan jalankan: fuser -k ${PORT}/tcp\n`);
        process.exit(1);
    } else {
        console.error('Server error:', err);
    }
});
