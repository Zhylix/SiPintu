<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppGatewayJob;
use App\Models\Application;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayController extends Controller
{
    /**
     * Send a WhatsApp message from an authenticated downstream application.
     * Route: POST /api/v1/whatsapp/send
     */
    public function send(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        /** @var Application|null $application */
        $application = $request->attributes->get('oauth_application');

        if (! $application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Aplikasi downstream tidak teridentifikasi.',
            ], 401);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:25'],
            'message' => ['required', 'string'],
            'sync' => ['nullable', 'boolean'],
        ], [
            'phone.required' => 'Nomor WhatsApp tujuan (phone) wajib diisi.',
            'message.required' => 'Isi pesan WhatsApp (message) wajib diisi.',
        ]);

        $formattedPhone = $whatsAppService->formatPhoneNumber($validated['phone']);

        if (! $formattedPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format nomor telepon tidak valid. Gunakan format internasional atau nomor Indonesia (contoh: 08123456789 atau 628123456789).',
            ], 422);
        }

        $sessionId = $application->getEffectiveWaSessionId();
        $isSync = $request->boolean('sync', false);

        // Record log entry
        $log = WhatsAppLog::create([
            'application_id' => $application->id,
            'session_id' => $sessionId,
            'type' => 'gateway',
            'phone_number' => $formattedPhone,
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        // If synchronous sending is requested
        if ($isSync) {
            $allowFallback = (bool) $application->wa_auto_fallback;
            $result = $whatsAppService->sendMessage(
                phone: $formattedPhone,
                message: $validated['message'],
                sessionId: $sessionId,
                allowFallback: $allowFallback
            );

            if ($result['success']) {
                $actualSession = $result['session_id'] ?? $sessionId;
                $log->update([
                    'status' => 'sent',
                    'session_id' => $actualSession,
                    'sent_at' => now(),
                    'error_message' => ($result['fallback_used'] ?? false)
                        ? 'Pesan dikirim via Central Default Bot (fallback).'
                        : null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Pesan WhatsApp berhasil dikirim.',
                    'data' => [
                        'log_id' => $log->id,
                        'recipient' => $formattedPhone,
                        'session_id' => $actualSession,
                        'fallback_used' => $result['fallback_used'] ?? false,
                        'sent_at' => $log->fresh()->sent_at?->toIso8601String(),
                    ],
                ], 200);
            }

            $errorMessage = $result['error'] ?? 'Pengiriman pesan gagal.';
            $log->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $errorMessage,
                'data' => [
                    'log_id' => $log->id,
                    'session_id' => $sessionId,
                ],
            ], 502);
        }

        // Asynchronous (default best-practice): Dispatch to queue
        SendWhatsAppGatewayJob::dispatch($log->id);

        return response()->json([
            'status' => 'queued',
            'message' => 'Pesan WhatsApp berhasil dimasukkan ke dalam antrean pengiriman.',
            'data' => [
                'log_id' => $log->id,
                'recipient' => $formattedPhone,
                'session_id' => $sessionId,
                'queued_at' => now()->toIso8601String(),
            ],
        ], 202);
    }

    /**
     * Get the real-time connection status of this downstream application's WhatsApp session.
     * Route: GET /api/v1/whatsapp/status
     */
    public function status(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        /** @var Application|null $application */
        $application = $request->attributes->get('oauth_application');

        if (! $application) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Aplikasi downstream tidak teridentifikasi.',
            ], 401);
        }

        $sessionId = $application->getEffectiveWaSessionId();
        $sessionResult = $whatsAppService->getSessionStatus($sessionId);

        $isSessionOnline = false;
        $botPhone = $application->wa_phone_number;
        $botEnabled = true;

        if ($sessionResult['online'] && isset($sessionResult['data'])) {
            $data = $sessionResult['data'];
            $isSessionOnline = ($data['connection'] ?? '') === 'open' && ! empty($data['bot_phone']);
            $botPhone = $data['bot_phone'] ?? $botPhone;
            $botEnabled = $data['bot_enabled'] ?? true;

            // Sync database if status changed
            $newStatus = $isSessionOnline ? 'connected' : 'disconnected';
            if (! $botEnabled) {
                $newStatus = 'disabled';
            }
            if ($application->wa_status !== $newStatus || $application->wa_phone_number !== $botPhone) {
                $application->update([
                    'wa_status' => $newStatus,
                    'wa_phone_number' => $botPhone,
                    'wa_session_id' => $sessionId,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'application' => [
                'name' => $application->name,
                'slug' => $application->slug,
                'client_id' => $application->client_id,
            ],
            'whatsapp_gateway' => [
                'session_id' => $sessionId,
                'assigned_phone' => $botPhone,
                'is_connected' => $isSessionOnline,
                'is_enabled' => $botEnabled,
                'auto_fallback_to_central' => (bool) $application->wa_auto_fallback,
                'gateway_status' => $isSessionOnline ? 'ready' : 'not_ready',
            ],
        ]);
    }
}
