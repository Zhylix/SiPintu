<?php

namespace App\Services;

use App\Jobs\SendWhatsAppAnnouncementJob;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppService
{
    protected string $botUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->botUrl = rtrim(config('services.whatsapp.bot_url', 'http://127.0.0.1:3789'), '/');
        $this->apiKey = config('services.whatsapp.api_key', '');
    }

    /**
     * Format phone number to international Indonesian format (e.g. 628123456789).
     */
    public function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-numeric characters except leading '+' if present initially
        $cleaned = preg_replace('/[^\d]/', '', $phone);

        if (empty($cleaned)) {
            return null;
        }

        // Standardize Indonesian prefix
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62'.substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        // Validate basic format: Indonesian country code (62) followed by 8xx... and 9 to 13 total digits
        if (! preg_match('/^62[2-9]\d{7,12}$/', $cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Check if a phone number is valid.
     */
    public function isValidPhoneNumber(?string $phone): bool
    {
        return $this->formatPhoneNumber($phone) !== null;
    }

    /**
     * Format announcement message template according to system specifications.
     */
    public function formatAnnouncementMessage(string $userName, string $content): string
    {
        return "📢 PENGUMUMAN\n\n".
               "Halo, {$userName}\n\n".
               "{$content}\n\n".
               'Terima kasih.';
    }

    /**
     * Send HTTP request to Baileys API Bot (Supports Multi-Session and Fallback).
     */
    public function sendMessage(string $phone, string $message, ?string $sessionId = null, bool $allowFallback = true): array
    {
        $formattedPhone = $this->formatPhoneNumber($phone);

        if (! $formattedPhone) {
            return [
                'success' => false,
                'error' => 'Nomor telepon kosong atau format tidak valid.',
            ];
        }

        $targetSession = ! empty($sessionId) ? trim($sessionId) : 'default';

        // 1. Try sending via specified session if not 'default'
        if ($targetSession !== 'default') {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("{$this->botUrl}/session/{$targetSession}/send-message", [
                    'phone' => $formattedPhone,
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['status']) && strtolower($data['status']) === 'success') {
                        return [
                            'success' => true,
                            'session_id' => $targetSession,
                            'fallback_used' => false,
                            'data' => $data,
                        ];
                    }
                }

                $errorDetail = $response->json()['message'] ?? $response->body();
                Log::warning("[WhatsAppService] Gagal kirim via session [{$targetSession}]: {$errorDetail}");

                if (! $allowFallback) {
                    return [
                        'success' => false,
                        'session_id' => $targetSession,
                        'error' => "Pengiriman via sesi [{$targetSession}] gagal: {$errorDetail}",
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("[WhatsAppService] Exception kirim via session [{$targetSession}]: ".$e->getMessage());
                if (! $allowFallback) {
                    return [
                        'success' => false,
                        'session_id' => $targetSession,
                        'error' => "Koneksi ke sesi [{$targetSession}] gagal: ".$e->getMessage(),
                    ];
                }
            }
        }

        // 2. Send via default session (Central Bot or Fallback)
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->botUrl}/send-message", [
                'phone' => $formattedPhone,
                'message' => $message,
                'session_id' => 'default',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && strtolower($data['status']) === 'success') {
                    return [
                        'success' => true,
                        'session_id' => 'default',
                        'fallback_used' => ($targetSession !== 'default'),
                        'data' => $data,
                    ];
                }

                $errorMsg = $data['message'] ?? 'Bot Baileys mengembalikan respon gagal.';

                return [
                    'success' => false,
                    'session_id' => 'default',
                    'error' => $errorMsg,
                ];
            }

            $errorMessage = "HTTP Request gagal dengan status code {$response->status()}: ".$response->body();
            Log::error("[WhatsAppService] Send message failed: {$errorMessage}");

            return [
                'success' => false,
                'session_id' => 'default',
                'error' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            $errorMsg = 'Koneksi ke API Bot Baileys gagal: '.$e->getMessage();
            Log::error("[WhatsAppService] Exception: {$errorMsg}");

            return [
                'success' => false,
                'session_id' => 'default',
                'error' => $errorMsg,
            ];
        }
    }

    /**
     * Dispatch Queue Jobs for all eligible target users of an announcement.
     */
    public function dispatchAnnouncementToUsers(Announcement $announcement): array
    {
        $query = User::query()
            ->where('status', 'active');

        if (Schema::hasColumn('users', 'wa_notify')) {
            $query->where('wa_notify', true);
        }

        // Filter target users based on announcement target_role
        if ($announcement->target_role && $announcement->target_role !== 'all') {
            $targetRole = $announcement->target_role;
            $query->where(function ($q) use ($targetRole) {
                if ($targetRole === 'user') {
                    $q->where('role', '!=', 'admin');
                } else {
                    $q->where('role', $targetRole);
                    if ($targetRole === 'student') {
                        $q->orWhere('role', 'siswa');
                    } elseif ($targetRole === 'teacher') {
                        $q->orWhere('role', 'guru');
                    } elseif ($targetRole === 'dudi') {
                        $q->orWhere('role', 'mitra');
                    }
                }
            });
        }

        $users = $query->get();

        $dispatchedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $formattedPhone = $this->formatPhoneNumber($user->phone);

            if (! $formattedPhone) {
                // Create failed log for invalid/empty phone number
                WhatsAppLog::create([
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                    'phone_number' => $user->phone ?? '-',
                    'message' => $this->formatAnnouncementMessage($user->name, $announcement->content),
                    'status' => 'failed',
                    'error_message' => 'Nomor telepon pengguna kosong atau tidak valid.',
                    'sent_at' => null,
                ]);
                $skippedCount++;

                continue;
            }

            $message = $this->formatAnnouncementMessage($user->name, $announcement->content);

            // Create pending log entry
            $log = WhatsAppLog::create([
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'phone_number' => $formattedPhone,
                'message' => $message,
                'status' => 'pending',
            ]);

            // Dispatch job to queue
            SendWhatsAppAnnouncementJob::dispatch($log->id);
            $dispatchedCount++;
        }

        return [
            'dispatched' => $dispatchedCount,
            'skipped' => $skippedCount,
            'total_users' => $users->count(),
        ];
    }

    /**
     * Get real-time status of the WhatsApp Bot service.
     */
    public function getBotStatus(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->botUrl}/status");

            if ($response->successful()) {
                return [
                    'online' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'online' => false,
                'error' => 'Bot service HTTP error '.$response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'online' => false,
                'error' => 'Koneksi ke Bot Baileys offline / gagal: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Logout WhatsApp Bot session to allow scanning a new WhatsApp number.
     */
    public function logoutBot(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->post("{$this->botUrl}/logout");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? 'Sesi bot WhatsApp berhasil dikeluarkan.',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal melakukan logout bot.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Tidak dapat terhubung ke bot untuk logout: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Toggle or set WhatsApp Bot ON/OFF power status without logging out.
     */
    public function toggleBotPower(?bool $enabled = null): array
    {
        try {
            $payload = [];
            if ($enabled !== null) {
                $payload['enabled'] = $enabled;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->botUrl}/toggle-power", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'bot_enabled' => $data['bot_enabled'] ?? true,
                    'message' => $data['message'] ?? 'Status aktif bot WhatsApp berhasil diperbarui.',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal mengubah status aktif bot WhatsApp.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Tidak dapat terhubung ke bot untuk mengubah status daya: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get real-time status and QR code of a specific session.
     */
    public function getSessionStatus(string $sessionId): array
    {
        try {
            $session = trim($sessionId);
            $response = Http::timeout(5)->get("{$this->botUrl}/session/{$session}/status");

            if ($response->successful()) {
                return [
                    'online' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'online' => false,
                'error' => 'HTTP Error '.$response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'online' => false,
                'error' => 'Koneksi ke Bot Baileys offline: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Start/wake up a specific session to generate QR Code.
     */
    public function startSession(string $sessionId): array
    {
        try {
            $session = trim($sessionId);
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->post("{$this->botUrl}/session/{$session}/start");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal memulai sesi bot.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Gagal terhubung ke bot: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Logout and reset a specific session.
     */
    public function logoutSession(string $sessionId): array
    {
        try {
            $session = trim($sessionId);
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->post("{$this->botUrl}/session/{$session}/logout");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? "Sesi [{$session}] berhasil dikeluarkan.",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal melakukan logout sesi bot.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Gagal terhubung ke bot: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Toggle power (hibernate/wake) for a specific session.
     */
    public function toggleSessionPower(string $sessionId, ?bool $enabled = null): array
    {
        try {
            $session = trim($sessionId);
            $payload = [];
            if ($enabled !== null) {
                $payload['enabled'] = $enabled;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->botUrl}/session/{$session}/toggle-power", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal mengubah daya sesi.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Gagal terhubung ke bot: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Delete a session entirely from bot memory & disk.
     */
    public function deleteSession(string $sessionId): array
    {
        try {
            $session = trim($sessionId);
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->delete("{$this->botUrl}/session/{$session}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json()['message'] ?? "Sesi [{$session}] berhasil dihapus.",
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Gagal menghapus sesi bot.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Gagal terhubung ke bot: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get all active sessions from bot.
     */
    public function getAllSessions(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$this->botUrl}/sessions");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['sessions'] ?? [],
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP Error '.$response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync and update the application record with live status from bot.
     */
    public function syncApplicationWaStatus(Application $application): array
    {
        $sessionId = $application->getEffectiveWaSessionId();
        $statusResult = $this->getSessionStatus($sessionId);

        if ($statusResult['online'] && isset($statusResult['data'])) {
            $data = $statusResult['data'];
            $isConnected = ($data['connection'] ?? '') === 'open' && ! empty($data['bot_phone']);
            $newStatus = $isConnected ? 'connected' : (($data['connection'] ?? '') === 'connecting' ? 'connecting' : 'disconnected');
            if (isset($data['bot_enabled']) && ! $data['bot_enabled']) {
                $newStatus = 'disabled';
            }

            $application->update([
                'wa_session_id' => $sessionId,
                'wa_phone_number' => $data['bot_phone'] ?? $application->wa_phone_number,
                'wa_status' => $newStatus,
            ]);

            return [
                'status' => $newStatus,
                'phone' => $data['bot_phone'] ?? null,
                'qr_code' => $data['qr_code'] ?? null,
                'bot_enabled' => $data['bot_enabled'] ?? true,
            ];
        }

        return [
            'status' => 'offline',
            'phone' => $application->wa_phone_number,
            'qr_code' => null,
            'bot_enabled' => false,
        ];
    }
}
