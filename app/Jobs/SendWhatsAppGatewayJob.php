<?php

namespace App\Jobs;

use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppGatewayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $whatsAppLogId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(int $whatsAppLogId)
    {
        $this->whatsAppLogId = $whatsAppLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        $log = WhatsAppLog::with('application')->find($this->whatsAppLogId);

        if (! $log) {
            return;
        }

        $application = $log->application;
        $sessionId = $log->session_id;

        if (empty($sessionId) && $application) {
            $sessionId = $application->getEffectiveWaSessionId();
        }

        $allowFallback = $application ? (bool) $application->wa_auto_fallback : true;

        // Dispatch message through WhatsAppService
        $result = $whatsAppService->sendMessage(
            phone: $log->phone_number,
            message: $log->message,
            sessionId: $sessionId,
            allowFallback: $allowFallback
        );

        if ($result['success']) {
            $actualSessionUsed = $result['session_id'] ?? $sessionId ?? 'default';

            $log->update([
                'status' => 'sent',
                'session_id' => $actualSessionUsed,
                'sent_at' => now(),
                'error_message' => ($result['fallback_used'] ?? false)
                    ? 'Pesan dikirim via Central Default Bot (karena sesi khusus downstream offline).'
                    : null,
            ]);

            Log::info("[SendWhatsAppGatewayJob] Sukses mengirim pesan gateway ke {$log->phone_number} via sesi [{$actualSessionUsed}].");
        } else {
            $errorMessage = $result['error'] ?? 'Pengiriman pesan gateway gagal.';
            $log->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            Log::error("[SendWhatsAppGatewayJob] Gagal mengirim pesan gateway ke {$log->phone_number}: {$errorMessage}");

            throw new \RuntimeException($errorMessage);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $log = WhatsAppLog::find($this->whatsAppLogId);
        if ($log) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception?->getMessage() ?? 'Pengiriman pesan gateway gagal setelah beberapa kali percobaan.',
            ]);
        }
    }
}
