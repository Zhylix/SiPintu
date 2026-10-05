<?php

namespace App\Notifications;

use App\Models\ErrorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemErrorOccurredNotification extends Notification
{
    use Queueable;

    public function __construct(public ErrorLog $errorLog) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $userName = $this->errorLog->user ? $this->errorLog->user->name : 'Pengguna Tamu / Sistem';
        $userRoleName = $this->errorLog->user ? $this->errorLog->user->getUserTypeName() : 'Guest';

        return [
            'type' => 'system_error',
            'title' => "Error Server {$this->errorLog->status_code} Terdeteksi",
            'message' => "Error {$this->errorLog->error_type} pada {$this->errorLog->method} {$this->errorLog->url}",
            'incident_code' => $this->errorLog->incident_code,
            'error_log_id' => $this->errorLog->id,
            'status_code' => $this->errorLog->status_code,
            'error_type' => $this->errorLog->error_type,
            'user_name' => $userName,
            'user_role' => $userRoleName,
            'url' => $this->errorLog->url,
            'occurrence_count' => $this->errorLog->occurrence_count,
            'action_url' => route('admin.error-logs.show', $this->errorLog->id),
            'time' => now()->toIso8601String(),
        ];
    }
}
