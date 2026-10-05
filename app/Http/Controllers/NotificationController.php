<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read and optionally redirect to its target URL.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai telah dibaca.',
            ]);
        }

        if ($notification && ! empty($notification->data['action_url'])) {
            return redirect()->to($notification->data['action_url']);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Mark all unread notifications of current user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi telah ditandai sebagai dibaca.',
            ]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Delete a specific notification.
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->delete();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }
}
