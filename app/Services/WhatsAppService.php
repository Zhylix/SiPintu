<?php

namespace App\Services;

use App\Jobs\SendWhatsAppAnnouncementJob;
use App\Models\Announcement;
use App\Models\User;
use App\Models\WhatsAppLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $botUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->botUrl = rtrim(config('services.whatsapp.bot_url', 'http://127.0.0.1:3000'), '/');
        $this->apiKey = config('services.whatsapp.api_key', '');
    }

    /**
     * Format phone number to international Indonesian format (e.g. 628123456789).
     *
     * @param string|null $phone
     * @return string|null
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
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62' . $cleaned;
        }

        // Validate basic format: Indonesian country code (62) followed by 8xx... and 9 to 13 total digits
        if (!preg_match('/^62[2-9]\d{7,12}$/', $cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Check if a phone number is valid.
     *
     * @param string|null $phone
     * @return bool
     */
    public function isValidPhoneNumber(?string $phone): bool
    {
        return $this->formatPhoneNumber($phone) !== null;
    }

    /**
     * Format announcement message template according to system specifications.
     *
     * @param string $userName
     * @param string $content
     * @return string
     */
    public function formatAnnouncementMessage(string $userName, string $content): string
    {
        return "📢 PENGUMUMAN\n\n" .
               "Halo, {$userName}\n\n" .
               "{$content}\n\n" .
               "Terima kasih.";
    }

    /**
     * Send HTTP request to Baileys API Bot.
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    public function sendMessage(string $phone, string $message): array
    {
        $formattedPhone = $this->formatPhoneNumber($phone);

        if (!$formattedPhone) {
            return [
                'success' => false,
                'error' => 'Nomor telepon kosong atau format tidak valid.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->botUrl}/send-message", [
                'phone' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && strtolower($data['status']) === 'success') {
                    return [
                        'success' => true,
                        'data' => $data,
                    ];
                }

                $errorMsg = $data['message'] ?? 'Bot Baileys mengembalikan respon gagal.';
                return [
                    'success' => false,
                    'error' => $errorMsg,
                ];
            }

            $errorMessage = "HTTP Request gagal dengan status code {$response->status()}: " . $response->body();
            Log::error("[WhatsAppService] Send message failed: {$errorMessage}");

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            $errorMsg = 'Koneksi ke API Bot Baileys gagal: ' . $e->getMessage();
            Log::error("[WhatsAppService] Exception: {$errorMsg}");

            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }
    }

    /**
     * Dispatch Queue Jobs for all eligible target users of an announcement.
     *
     * @param Announcement $announcement
     * @return array
     */
    public function dispatchAnnouncementToUsers(Announcement $announcement): array
    {
        $query = User::query()->where('status', 'active');

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

            if (!$formattedPhone) {
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
     *
     * @return array
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
                'error' => 'Bot service HTTP error ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'online' => false,
                'error' => 'Koneksi ke Bot Baileys offline / gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Logout WhatsApp Bot session to allow scanning a new WhatsApp number.
     *
     * @return array
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
                'error' => 'Tidak dapat terhubung ke bot untuk logout: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Toggle or set WhatsApp Bot ON/OFF power status without logging out.
     *
     * @param bool|null $enabled
     * @return array
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
                'error' => 'Tidak dapat terhubung ke bot untuk mengubah status daya: ' . $e->getMessage(),
            ];
        }
    }
}
