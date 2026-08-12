<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request, WhatsAppService $whatsAppService)
    {
        $query = Announcement::with('author')->withCount('whatsAppLogs')->latest();

        if ($request->filled('target_role') && $request->target_role !== 'all_roles') {
            $query->where('target_role', $request->target_role);
        }

        if ($request->filled('channel') && $request->channel !== 'all_channels') {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('content', 'like', '%'.$search.'%')
                    ->orWhereHas('author', function ($aq) use ($search) {
                        $aq->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $announcements = $query->paginate(10)->withQueryString();
        $botStatus = $whatsAppService->getBotStatus();

        return view('admin.announcements.index', compact('announcements', 'botStatus'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request, WhatsAppService $whatsAppService)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,warning,danger,success',
            'target_role' => 'required|string|in:all,user,student,alumni,teacher,dudi',
            'channel' => 'required|string|in:web,whatsapp,both',
            'is_active' => 'nullable|boolean',
            'send_whatsapp' => 'nullable|boolean',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_role' => $validated['target_role'],
            'channel' => $validated['channel'],
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
            'published_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => 'Membuat Pengumuman: '.$announcement->title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'announcement_id' => $announcement->id,
                'target_role' => $announcement->target_role,
                'channel' => $announcement->channel,
            ],
        ]);

        $waMsg = '';
        if ($announcement->channel !== 'web' || $request->boolean('send_whatsapp')) {
            $result = $whatsAppService->dispatchAnnouncementToUsers($announcement);
            $waMsg = " Pengiriman WhatsApp di-antrikan untuk {$result['dispatched']} penerima.";
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dipublikasikan!'.$waMsg);
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,warning,danger,success',
            'target_role' => 'required|string|in:all,user,student,alumni,teacher,dudi',
            'channel' => 'required|string|in:web,whatsapp,both',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_role' => $validated['target_role'],
            'channel' => $validated['channel'],
            'is_active' => $request->has('is_active'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => 'Mengubah Pengumuman: '.$announcement->title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'announcement_id' => $announcement->id,
                'channel' => $announcement->channel,
            ],
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui!');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => 'Menghapus Pengumuman: '.$title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dihapus!');
    }

    public function toggleStatus(Request $request, Announcement $announcement)
    {
        $announcement->update([
            'is_active' => ! $announcement->is_active,
        ]);

        return redirect()->back()->with('success', 'Status pengumuman berhasil diperbarui!');
    }

    /**
     * Broadcast announcement to users via WhatsApp Queue.
     */
    public function sendWhatsApp(Request $request, Announcement $announcement, WhatsAppService $whatsAppService)
    {
        $result = $whatsAppService->dispatchAnnouncementToUsers($announcement);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => 'Mengirim Pengumuman ke WhatsApp: '.$announcement->title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'announcement_id' => $announcement->id,
                'dispatched' => $result['dispatched'],
                'skipped' => $result['skipped'],
            ],
        ]);

        return redirect()->back()->with(
            'success',
            "Pengiriman WhatsApp berhasil dijadwalkan! {$result['dispatched']} pesan di-antrikan, {$result['skipped']} nomor dilewati karena tidak valid/kosong."
        );
    }

    /**
     * View WhatsApp log delivery status for an announcement.
     */
    public function whatsAppLogs(Announcement $announcement)
    {
        $logs = WhatsAppLog::with('user')
            ->where('announcement_id', $announcement->id)
            ->latest()
            ->paginate(15);

        return view('admin.announcements.logs', compact('announcement', 'logs'));
    }

    /**
     * Logout current WhatsApp Bot session to allow scanning a new WhatsApp number.
     */
    public function logoutBot(Request $request, WhatsAppService $whatsAppService)
    {
        $result = $whatsAppService->logoutBot();

        if ($result['success']) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'activity' => 'Logout / Ganti Nomor Bot WhatsApp',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'message' => $result['message'],
                ],
            ]);

            return back()->with('success', 'Sesi Bot WhatsApp berhasil dikeluarkan! Silakan scan QR Code baru untuk menghubungkan nomor WhatsApp baru.');
        }

        return back()->with('error', $result['error'] ?? 'Gagal mengeluarkan sesi Bot WhatsApp.');
    }

    /**
     * Toggle WhatsApp Bot ON/OFF power status without logging out session.
     */
    public function toggleBotPower(Request $request, WhatsAppService $whatsAppService)
    {
        $enabled = $request->has('enabled') ? $request->boolean('enabled') : null;
        $result = $whatsAppService->toggleBotPower($enabled);

        if ($result['success']) {
            $isBotEnabled = $result['bot_enabled'] ?? true;
            $statusText = $isBotEnabled ? 'DIAKTIFKAN (ON)' : 'DINONAKTIFKAN (OFF)';

            AuditLog::create([
                'user_id' => Auth::id(),
                'activity' => 'Ubah Status Bot WhatsApp: '.$statusText,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'bot_enabled' => $isBotEnabled,
                ],
            ]);

            return back()->with(
                'success',
                "Status Bot WhatsApp berhasil {$statusText}! Sesi terhubung ke WhatsApp tetap tersimpan (tidak logout)."
            );
        }

        return back()->with('error', $result['error'] ?? 'Gagal mengubah status aktif/non-aktif bot WhatsApp.');
    }

    /**
     * Get JSON bot status for live AJAX QR polling.
     */
    public function botStatus(WhatsAppService $whatsAppService)
    {
        return response()->json($whatsAppService->getBotStatus());
    }
}
