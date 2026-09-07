<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'sso_failed') {
                $query->where(function ($q) {
                    $q->where('activity', 'like', 'sso_%fail%')
                        ->orWhere('activity', 'like', 'sso_%denied%')
                        ->orWhere('activity', 'like', 'token_exchange_invalid%')
                        ->orWhereJsonContains('metadata->is_sso_failure', true);
                });
            } elseif ($type === 'sso') {
                $query->where(function ($q) {
                    $q->where('activity', 'like', 'sso_%')
                        ->orWhere('activity', 'like', 'token_exchange_%')
                        ->orWhereJsonContains('metadata->via_sso', true);
                });
            } elseif ($type === 'login') {
                $query->where('activity', 'like', '%login%');
            }
        }

        if ($request->filled('activity')) {
            $query->where('activity', 'like', "%{$request->activity}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('activity', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $auditLogs = $query->latest()->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', compact('auditLogs'));
    }
}
