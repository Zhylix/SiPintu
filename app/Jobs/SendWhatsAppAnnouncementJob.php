<?php

namespace App\Jobs;

use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $whatsAppLogId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
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
        $log = WhatsAppLog::find($this->whatsAppLogId);

        if (!$log) {
            return;
        }

        // Send WhatsApp message using the WhatsAppService
        $result = $whatsAppService->sendMessage($log->phone_number, $log->message);

        if ($result['success']) {
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } else {
            $errorMessage = $result['error'] ?? 'Pengiriman gagal.';
            $log->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            if ($this->attempts() < $this->tries) {
                throw new \RuntimeException($errorMessage);
            }
        }
    }
}
