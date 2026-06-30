<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\Payment;
use App\Models\Main\Tenant;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['tenant', 'subscription.plan'])->latest('paid_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('tenant', fn($q) => $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('contact_email', 'like', "%{$search}%"))
                ->orWhere('stripe_payment_intent_id', 'like', "%{$search}%")
                ->orWhere('card_last4', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->to);
        }

        $payments = $query->paginate(25)->withQueryString();

        $stats = [
            'total_revenue'    => Payment::where('status', 'succeeded')->sum('amount'),
            'total_refunded'   => Payment::where('status', 'succeeded')->sum('amount_refunded'),
            'failed_count'     => Payment::where('status', 'failed')->count(),
            'this_month'       => Payment::where('status', 'succeeded')
                                         ->whereMonth('paid_at', now()->month)
                                         ->whereYear('paid_at', now()->year)
                                         ->sum('amount'),
        ];

        $tenants = Tenant::orderBy('company_name')->get(['id', 'company_name']);

        return view('superadmin.payments.index', compact('payments', 'stats', 'tenants'));
    }
}
