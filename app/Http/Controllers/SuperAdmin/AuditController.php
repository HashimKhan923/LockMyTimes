<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\AuditLog;
use App\Models\Main\SuperAdmin;
use App\Models\Main\Tenant;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with(['superAdmin', 'tenant'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('event', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhere('auditable_type', 'like', "%{$search}%"));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('admin_id')) {
            $query->where('super_admin_id', $request->admin_id);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(30)->withQueryString();

        $events = AuditLog::distinct()->orderBy('event')->pluck('event');
        $admins  = SuperAdmin::orderBy('name')->get(['id', 'name']);
        $tenants = Tenant::orderBy('company_name')->get(['id', 'company_name']);

        return view('superadmin.audit.index', compact('logs', 'events', 'admins', 'tenants'));
    }
}
