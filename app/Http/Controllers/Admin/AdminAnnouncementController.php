<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('author')->latest();

        if ($request->filled('target_role') && $request->target_role !== 'all_roles') {
            $query->where('target_role', $request->target_role);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $announcements = $query->paginate(10)->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,warning,danger,success',
            'target_role' => 'required|string|in:all,student,teacher,dudi',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_role' => $validated['target_role'],
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
            ],
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dipublikasikan!');
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
            'target_role' => 'required|string|in:all,student,teacher,dudi',
            'is_active' => 'nullable|boolean',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'target_role' => $validated['target_role'],
            'is_active' => $request->has('is_active'),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'activity' => 'Mengubah Pengumuman: '.$announcement->title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'announcement_id' => $announcement->id,
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
}
