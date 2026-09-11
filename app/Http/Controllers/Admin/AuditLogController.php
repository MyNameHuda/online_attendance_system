<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->query('action'), fn ($q) => $q->where('action', $request->query('action')))
            ->when($request->query('user_id'), fn ($q) => $q->where('user_id', $request->query('user_id')))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        return view('admin.audit-logs.index', compact('logs', 'actions'));
    }
}
