<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminWhatsAppGatewayController extends Controller
{
    /**
     * Display the WhatsApp Gateway management dashboard.
     */
    public function index(Request $request, WhatsAppService $whatsAppService): View
    {
        $applications = Application::withCount(['whatsAppLogs as total_logs_count'])
            ->withCount(['whatsAppLogs as sent_logs_count' => function ($q) {
                $q->where('status', 'sent');
            }])
            ->orderBy('name')
            ->get();

        // Get live central bot status
        $centralBotStatus = $whatsAppService->getSessionStatus('default');

        // Fetch all active sessions from bot service
        $sessionsResult = $whatsAppService->getAllSessions();
        $liveSessions = [];
        if ($sessionsResult['success'] && is_array($sessionsResult['data'])) {
            foreach ($sessionsResult['data'] as $s) {
                if (isset($s['session_id'])) {
                    $liveSessions[$s['session_id']] = $s;
                }
            }
        }

        // Attach live session info to applications
        foreach ($applications as $app) {
            $effectiveSessionId = $app->getEffectiveWaSessionId();
            if (isset($liveSessions[$effectiveSessionId])) {
                $live = $liveSessions[$effectiveSessionId];
                $app->live_connection = $live['connection'] ?? 'disconnected';
                $app->live_phone = $live['bot_phone'] ?? $app->wa_phone_number;
                $app->live_enabled = $live['bot_enabled'] ?? true;
                $app->live_has_qr = ! empty($live['qr_code']);
            } else {
                $app->live_connection = 'disconnected';
                $app->live_phone = $app->wa_phone_number;
                $app->live_enabled = true;
                $app->live_has_qr = false;
            }
        }

        // Stats summary
        $connectedCount = 0;
        if (($centralBotStatus['data']['connection'] ?? '') === 'open') {
            $connectedCount++;
        }
        foreach ($applications as $app) {
            if ($app->live_connection === 'open') {
                $connectedCount++;
            }
        }

        $stats = [
            'total_apps' => $applications->count() + 1, // +1 for central bot
            'connected_sessions' => $connectedCount,
            'total_gateway_messages' => WhatsAppLog::where('type', 'gateway')->count(),
            'today_gateway_messages' => WhatsAppLog::where('type', 'gateway')->whereDate('created_at', today())->count(),
        ];

        // Recent gateway logs
        $recentLogs = WhatsAppLog::with('application')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.whatsapp-gateway.index', compact(
            'applications',
            'centralBotStatus',
            'liveSessions',
            'stats',
            'recentLogs'
        ));
    }

    /**
     * Live JSON status endpoint for a specific session (used by QR scanner modal).
     */
    public function sessionStatus(string $sessionId, WhatsAppService $whatsAppService): JsonResponse
    {
        $status = $whatsAppService->getSessionStatus($sessionId);

        // Auto-sync application status in database if dedicated session connects or updates
        if (($status['online'] ?? false) && isset($status['data']) && $sessionId !== 'default') {
            $data = $status['data'];
            $conn = $data['connection'] ?? '';
            $phone = $data['bot_phone'] ?? null;
            $enabled = $data['bot_enabled'] ?? true;

            $newStatus = ($conn === 'open' && ! empty($phone)) ? 'connected' : ($conn === 'connecting' ? 'connecting' : 'disconnected');
            if (! $enabled) {
                $newStatus = 'disabled';
            }

            Application::where('wa_session_id', $sessionId)
                ->orWhere('slug', preg_replace('/^app_/', '', $sessionId))
                ->update([
                    'wa_status' => $newStatus,
                    'wa_phone_number' => $phone,
                ]);
        }

        return response()->json($status);
    }

    /**
     * Explicitly start/wake session and generate QR Code.
     */
    public function startSession(string $sessionId, WhatsAppService $whatsAppService): JsonResponse
    {
        $result = $whatsAppService->startSession($sessionId);

        return response()->json($result);
    }

    /**
     * Logout and reset a specific session.
     */
    public function logoutSession(string $sessionId, Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $result = $whatsAppService->logoutSession($sessionId);

        // Update database if matching application exists (ignore if central bot default session)
        if ($sessionId !== 'default') {
            Application::where('wa_session_id', $sessionId)
                ->orWhere('slug', preg_replace('/^app_/', '', $sessionId))
                ->update([
                    'wa_status' => 'disconnected',
                    'wa_phone_number' => null,
                ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => "Logout Sesi WhatsApp Gateway: [{$sessionId}]",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'session_id' => $sessionId,
            ],
        ]);

        return response()->json($result);
    }

    /**
     * Toggle session power (hibernate/wake).
     */
    public function togglePower(string $sessionId, Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $enabled = $request->has('enabled') ? $request->boolean('enabled') : null;
        $result = $whatsAppService->toggleSessionPower($sessionId, $enabled);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => "Ubah Daya Sesi WhatsApp [{$sessionId}]",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'session_id' => $sessionId,
                'result' => $result,
            ],
        ]);

        return response()->json($result);
    }

    /**
     * Update application WhatsApp configuration (session id, auto fallback).
     */
    public function updateAppConfig(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'wa_session_id' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'wa_auto_fallback' => ['nullable', 'boolean'],
        ], [
            'wa_session_id.regex' => 'Session ID hanya boleh berisi huruf, angka, tanda strip (-), atau garis bawah (_).',
        ]);

        $customSession = ! empty($validated['wa_session_id']) ? trim($validated['wa_session_id']) : 'app_'.$application->slug;

        // Prevent hijacking the reserved central bot session ID
        if (strtolower($customSession) === 'default') {
            return redirect()->back()->withErrors([
                'wa_session_id' => 'Session ID "default" dicadangkan khusus untuk Nomor WhatsApp Pusat SiPintu.',
            ]);
        }

        // Prevent duplicate session IDs between different downstream applications
        $existingApp = Application::where('wa_session_id', $customSession)
            ->where('id', '!=', $application->id)
            ->first();

        if ($existingApp) {
            return redirect()->back()->withErrors([
                'wa_session_id' => "Session ID '{$customSession}' sudah digunakan oleh aplikasi {$existingApp->name}.",
            ]);
        }

        $application->update([
            'wa_session_id' => $customSession,
            'wa_auto_fallback' => $request->boolean('wa_auto_fallback'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => "Memperbarui Konfigurasi WA Gateway Aplikasi: {$application->name}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'application_id' => $application->id,
                'wa_session_id' => $customSession,
                'wa_auto_fallback' => $request->boolean('wa_auto_fallback'),
            ],
        ]);

        return redirect()->back()->with('success', "Konfigurasi WhatsApp Gateway untuk {$application->name} berhasil diperbarui.");
    }

    /**
     * Send a test message from a specific session.
     */
    public function sendTestMessage(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $formattedPhone = $whatsAppService->formatPhoneNumber($validated['phone']);

        if (! $formattedPhone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format nomor WhatsApp tidak valid. Masukkan nomor yang valid (contoh: 08123456789 atau 628123456789).',
            ], 422);
        }

        $result = $whatsAppService->sendMessage(
            phone: $formattedPhone,
            message: $validated['message'],
            sessionId: $validated['session_id'],
            allowFallback: false
        );

        $application = Application::where('wa_session_id', $validated['session_id'])
            ->orWhere('slug', preg_replace('/^app_/', '', $validated['session_id']))
            ->first();

        // Record test message in WhatsAppLog
        WhatsAppLog::create([
            'application_id' => $application?->id,
            'session_id' => $validated['session_id'],
            'type' => 'test',
            'phone_number' => $formattedPhone,
            'message' => $validated['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Pengiriman pesan uji coba gagal.'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => "Uji Coba Pengiriman WhatsApp Sesi [{$validated['session_id']}]",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'session_id' => $validated['session_id'],
                'phone' => $formattedPhone,
                'success' => $result['success'],
            ],
        ]);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => "Pesan uji coba berhasil dikirim via sesi [{$validated['session_id']}].",
                'data' => $result['data'] ?? [],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['error'] ?? 'Pengiriman pesan uji coba gagal.',
        ], 500);
    }

    /**
     * View detailed gateway delivery audit logs.
     */
    public function logs(Request $request): View
    {
        $query = WhatsAppLog::with(['application', 'announcement'])->latest();

        if ($request->filled('application_id') && $request->application_id !== 'all') {
            $query->where('application_id', $request->application_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('session_id', 'like', "%{$search}%")
                    ->orWhereHas('application', function ($appQ) use ($search) {
                        $appQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();
        $applications = Application::orderBy('name')->get();

        return view('admin.whatsapp-gateway.logs', compact('logs', 'applications'));
    }
}
