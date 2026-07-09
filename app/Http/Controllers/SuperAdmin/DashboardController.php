<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\Payment;
use App\Models\Main\Subscription;
use App\Models\Main\SubscriptionPlan;
use App\Models\Main\SupportTicket;
use App\Models\Main\Tenant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Core counts ─────────────────────────────────────────────────────
        $totalTenants    = Tenant::count();
        $activeTenants   = Tenant::where('status', 'active')->count();
        $trialTenants    = Tenant::where('status', 'trial')->count();
        $pastDue         = Tenant::where('status', 'past_due')->count();
        $suspended       = Tenant::where('status', 'suspended')->count();
        $newThisMonth    = Tenant::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $newLastMonth    = Tenant::whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
        $expiringSoon    = Tenant::where('status', 'trial')->where('trial_ends_at', '<=', now()->addDays(3))->count();
        $openTickets     = SupportTicket::where('status', 'open')->count();

        // ── MRR ─────────────────────────────────────────────────────────────
        $mrr           = $this->calculateMRR();
        $mrrLastMonth  = $this->calculateMRRForMonth(now()->subMonth());
        $mrrDelta      = $mrrLastMonth > 0 ? round((($mrr - $mrrLastMonth) / $mrrLastMonth) * 100, 1) : null;

        // ── Revenue this month vs last ────────────────────────────────────
        $revenueThisMonth = Payment::where('status', 'succeeded')
            ->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount');
        $revenueLastMonth = Payment::where('status', 'succeeded')
            ->whereMonth('paid_at', now()->subMonth()->month)->whereYear('paid_at', now()->subMonth()->year)->sum('amount');

        $stats = [
            'total_tenants'    => $totalTenants,
            'active_tenants'   => $activeTenants,
            'trial_tenants'    => $trialTenants,
            'past_due_tenants' => $pastDue,
            'suspended_tenants'=> $suspended,
            'new_this_month'   => $newThisMonth,
            'new_last_month'   => $newLastMonth,
            'expiring_soon'    => $expiringSoon,
            'open_tickets'     => $openTickets,
            'mrr'              => $mrr,
            'mrr_delta'        => $mrrDelta,
            'revenue_this_month' => $revenueThisMonth,
            'revenue_last_month' => $revenueLastMonth,
        ];

        // ── Action alerts ────────────────────────────────────────────────────
        $alerts = $this->buildAlerts();

        // ── Recent tenants ────────────────────────────────────────────────
        $recentTenants = Tenant::with('activeSubscription.plan')->latest()->take(8)->get();

        // ── Top tenants by revenue ────────────────────────────────────────
        $topTenants = Tenant::with('activeSubscription.plan')
            ->withSum(['payments as lifetime_revenue' => fn($q) => $q->where('status', 'succeeded')], 'amount')
            ->orderByDesc('lifetime_revenue')
            ->limit(5)
            ->get();

        // ── Plan distribution ─────────────────────────────────────────────
        $planStats = SubscriptionPlan::withCount([
            'subscriptions as count' => fn($q) => $q->whereIn('status', ['active', 'trialing']),
        ])->active()->ordered()->get()
          ->map(fn($p) => ['name' => $p->name, 'count' => $p->count])
          ->toArray();

        // ── Revenue chart — last 6 months ─────────────────────────────────
        $revenueChart = $this->buildRevenueChart();

        return view('superadmin.dashboard', compact(
            'stats', 'alerts', 'recentTenants', 'topTenants', 'planStats', 'revenueChart'
        ));
    }

    private function buildAlerts(): array
    {
        $alerts = [];

        // Trials expiring in 3 days
        $expiring = Tenant::where('status', 'trial')
            ->where('trial_ends_at', '<=', now()->addDays(3))
            ->where('trial_ends_at', '>', now())
            ->orderBy('trial_ends_at')
            ->get(['id', 'company_name', 'trial_ends_at', 'slug']);

        foreach ($expiring as $t) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'clock',
                'message' => "<strong>{$t->company_name}</strong> trial expires " . $t->trial_ends_at->diffForHumans(),
                'action'  => route('superadmin.organizations.show', $t),
                'label'   => 'Extend',
            ];
        }

        // Past-due tenants
        $pastDue = Tenant::where('status', 'past_due')->latest()->limit(3)->get(['id', 'company_name', 'slug']);
        foreach ($pastDue as $t) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'alert-circle',
                'message' => "<strong>{$t->company_name}</strong> is past due",
                'action'  => route('superadmin.organizations.show', $t),
                'label'   => 'View',
            ];
        }

        // Failed payments in last 7 days
        $failedCount = Payment::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))->count();
        if ($failedCount > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'credit-card',
                'message' => "<strong>{$failedCount}</strong> failed payment(s) in the last 7 days",
                'action'  => route('superadmin.payments.index', ['status' => 'failed']),
                'label'   => 'Review',
            ];
        }

        // Open tickets
        $ticketCount = SupportTicket::where('status', 'open')->count();
        if ($ticketCount > 0) {
            $alerts[] = [
                'type'    => 'info',
                'icon'    => 'life-buoy',
                'message' => "<strong>{$ticketCount}</strong> open support ticket(s) awaiting response",
                'action'  => route('superadmin.tickets.index', ['status' => 'open']),
                'label'   => 'View',
            ];
        }

        return $alerts;
    }

    private function calculateMRR(): float
    {
        return Subscription::whereIn('status', ['active', 'trialing'])
            ->where('billing_cycle', 'monthly')->sum('amount')
            + Subscription::whereIn('status', ['active', 'trialing'])
            ->where('billing_cycle', 'yearly')->sum(DB::raw('amount / 12'));
    }

    private function calculateMRRForMonth(\Carbon\Carbon $month): float
    {
        // Approximate: revenue collected that month as proxy for MRR
        return (float) Payment::where('status', 'succeeded')
            ->whereMonth('paid_at', $month->month)
            ->whereYear('paid_at', $month->year)
            ->sum('amount');
    }

    private function buildRevenueChart(): array
    {
        $labels = [];
        $data   = [];
        for ($i = 5; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[]   = round(
                Payment::where('status', 'succeeded')
                    ->whereMonth('paid_at', $month->month)
                    ->whereYear('paid_at', $month->year)
                    ->sum('amount'),
                2
            );
        }
        return ['labels' => $labels, 'data' => $data];
    }
}
