<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SystemErrorOccurredNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ErrorLoggerService
{
    protected static bool $isHandling = false;

    /**
     * Record an uncaught exception or server error (500 and similar).
     */
    public static function record(Throwable $e, ?Request $request = null): ?ErrorLog
    {
        if (self::$isHandling) {
            return null;
        }

        // Determine if this exception is a server error (5xx or unhandled throwable)
        if (! self::shouldRecord($e)) {
            return null;
        }

        self::$isHandling = true;

        try {
            $request = $request ?? request();

            $statusCode = ($e instanceof HttpExceptionInterface) ? $e->getStatusCode() : 500;
            $errorType = class_basename($e);
            $message = $e->getMessage() ?: 'Terjadi kesalahan sistem internal yang tidak terduga.';
            $file = $e->getFile();
            $line = $e->getLine();

            $url = $request ? $request->fullUrl() : 'cli';
            $path = $request ? $request->path() : 'cli';
            $method = $request ? $request->method() : 'CLI';
            $routeName = $request && $request->route() ? $request->route()->getName() : null;
            $ipAddress = $request ? $request->ip() : null;
            $userAgent = $request ? substr($request->userAgent() ?? '', 0, 500) : null;

            $user = null;
            try {
                $user = auth()->user() ?? ($request ? $request->user() : null);
            } catch (Throwable $ue) {
                // Ignore auth access failures during error capture
            }

            $userId = $user?->id;
            $userRole = $user?->role ?? ($request && $request->is('api/*') ? 'api' : 'guest');

            // Unique fingerprint to group repeated errors
            $fingerprint = hash('sha256', "{$errorType}|{$file}|{$line}|{$path}|{$method}");

            // Sanitize payload data (exclude passwords, tokens, secrets)
            $requestData = $request ? self::sanitizeRequestData($request->all()) : null;
            $headers = $request ? self::filterHeaders($request->headers->all()) : null;

            // Formatted stack trace (capped at 40 frames)
            $trace = self::formatTrace($e);

            // Check if an unresolved error with this fingerprint exists within the last 24h
            $existingLog = ErrorLog::where('fingerprint', $fingerprint)
                ->where('status', 'unresolved')
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existingLog) {
                $existingLog->increment('occurrence_count');
                $existingLog->update([
                    'last_seen_at' => now(),
                    'message' => $message,
                    'user_id' => $userId ?? $existingLog->user_id,
                    'user_role' => $userRole ?? $existingLog->user_role,
                ]);

                $errorLog = $existingLog;
            } else {
                $incidentCode = 'ERR-'.date('Ymd').'-'.strtoupper(Str::random(6));

                $errorLog = ErrorLog::create([
                    'incident_code' => $incidentCode,
                    'status_code' => $statusCode,
                    'error_type' => $errorType,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'trace' => $trace,
                    'url' => $url,
                    'route_name' => $routeName,
                    'method' => $method,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'user_id' => $userId,
                    'user_role' => $userRole,
                    'request_data' => $requestData,
                    'headers' => $headers,
                    'status' => 'unresolved',
                    'occurrence_count' => 1,
                    'fingerprint' => $fingerprint,
                    'last_seen_at' => now(),
                ]);
            }

            // Bind incident code to current request attributes for display on user error view
            if ($request) {
                $request->attributes->set('incident_code', $errorLog->incident_code);
                $request->attributes->set('current_error_log', $errorLog);
            }

            // Dispatch notification to admins with flood control
            self::notifyAdmins($errorLog, $fingerprint);

            return $errorLog;
        } catch (Throwable $loggingError) {
            Log::error('[ErrorLoggerService] Gagal mencatat error server: '.$loggingError->getMessage(), [
                'exception' => $loggingError,
            ]);

            return null;
        } finally {
            self::$isHandling = false;
        }
    }

    /**
     * Check if exception should be recorded.
     */
    protected static function shouldRecord(Throwable $e): bool
    {
        // Ignore standard HTTP client error exceptions (status < 500)
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode() >= 500;
        }

        // Ignore common client-side exceptions
        $ignoredClasses = [
            ValidationException::class,
            AuthenticationException::class,
            AuthorizationException::class,
            NotFoundHttpException::class,
            AccessDeniedHttpException::class,
            TokenMismatchException::class,
        ];

        foreach ($ignoredClasses as $ignoredClass) {
            if ($e instanceof $ignoredClass) {
                return false;
            }
        }

        // All other unhandled exceptions (QueryException, ErrorException, TypeError, RuntimeException, etc.) produce HTTP 500
        return true;
    }

    /**
     * Sanitize request data to strip sensitive passwords and credentials.
     */
    protected static function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'secret',
            'client_secret',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'authorization',
            'credit_card',
            'cvv',
            'pin',
            'otp',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $value = '******** [REDACTED]';
            }
        });

        return $data;
    }

    /**
     * Filter relevant headers.
     */
    protected static function filterHeaders(array $headers): array
    {
        $allowedHeaders = [
            'host',
            'user-agent',
            'referer',
            'accept',
            'content-type',
            'origin',
            'sec-ch-ua',
            'x-forwarded-for',
        ];

        $filtered = [];
        foreach ($headers as $key => $values) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $allowedHeaders, true)) {
                $filtered[$key] = is_array($values) ? implode(', ', $values) : $values;
            }
        }

        return $filtered;
    }

    /**
     * Format exception stack trace into a readable, concise string.
     */
    protected static function formatTrace(Throwable $e): string
    {
        $trace = '';
        $frames = array_slice($e->getTrace(), 0, 35);

        foreach ($frames as $i => $frame) {
            $file = $frame['file'] ?? '[internal function]';
            $line = isset($frame['line']) ? ":{$frame['line']}" : '';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $function = $frame['function'] ?? '';

            $trace .= "#{$i} {$file}{$line} {$class}{$type}{$function}()\n";
        }

        return $trace;
    }

    /**
     * Send notifications to all active Admins with flood control.
     */
    protected static function notifyAdmins(ErrorLog $errorLog, string $fingerprint): void
    {
        $cacheKey = "error_notif_sent_{$fingerprint}";

        // Flood control: Send notification at most once per 15 minutes for the exact same error
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(15));

        try {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isEmpty()) {
                return;
            }

            // 1. In-App Database Notifications for all admins
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new SystemErrorOccurredNotification($errorLog));
                } catch (Throwable $ne) {
                    Log::warning("[ErrorLoggerService] Gagal mengirim notifikasi DB ke admin #{$admin->id}: ".$ne->getMessage());
                }
            }

            // 2. Optional WhatsApp Alert for Admins if enabled
            $waAlertsEnabled = Setting::get('wa_error_alerts_enabled', '1') !== '0';
            if ($waAlertsEnabled) {
                $whatsAppService = app(WhatsAppService::class);
                $userName = $errorLog->user ? $errorLog->user->name : 'Pengguna Tamu / Sistem';
                $userRole = $errorLog->user ? $errorLog->user->getUserTypeName() : 'Guest';

                $waMessage = "🚨 *ALERT ERROR SERVER SIPINTU*\n\n"
                    ."Terdeteksi kendala teknis internal pada portal SiPintu:\n\n"
                    ."• *Kode Insiden:* `{$errorLog->incident_code}`\n"
                    ."• *Status:* HTTP {$errorLog->status_code} ({$errorLog->error_type})\n"
                    .'• *Pesan:* '.Str::limit($errorLog->message, 120)."\n"
                    ."• *Endpoint:* {$errorLog->method} {$errorLog->url}\n"
                    ."• *Pengguna:* {$userName} ({$userRole})\n"
                    .'• *Waktu:* '.now()->format('d/m/Y H:i')." WIB\n\n"
                    ."Silakan periksa detail dan lakukan perbaikan melalui Admin Panel SiPintu:\n"
                    .url("/admin/error-logs/{$errorLog->id}");

                foreach ($admins as $admin) {
                    if ($admin->wa_notify && ! empty($admin->phone) && $whatsAppService->isValidPhoneNumber($admin->phone)) {
                        try {
                            $whatsAppService->sendMessage($admin->phone, $waMessage);
                        } catch (Throwable $we) {
                            Log::warning('[ErrorLoggerService] Gagal mengirim WhatsApp error alert: '.$we->getMessage());
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error('[ErrorLoggerService] notifyAdmins exception: '.$e->getMessage());
        }
    }
}
