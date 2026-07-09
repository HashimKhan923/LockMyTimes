<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\Payment;
use App\Models\Main\Subscription;
use App\Models\Main\SubscriptionPlan;
use App\Models\Main\Tenant;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // ── MRR trend (12 months) ────────────────────────────────────────────
        $mrrTrend = $this->buildMonthlyRevenue(12);

        // ── Tenant growth (12 months cumulative) ────────────────────────────
        $tenantGrowth = $this->buildTenantGrowth(12);

        // ── Conversion: trial paid ─────────────────────────────────────────
        $totalTrials    = Tenant::whereIn('status', ['trial', 'active', 'past_due', 'suspended', 'cancelled'])->count();
        $convertedCount = Tenant::where('status', 'active')->count();
        $conversionRate = $totalTrials > 0 ? round(($convertedCount / $totalTrials) * 100, 1) : 0;

        // ── Churn this month ─────────────────────────────────────────────────
        $churnedThisMonth = Tenant::whereIn('status', ['cancelled', 'suspended'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $activeStart = Tenant::whereIn('status', ['active', 'trial'])->count() + $churnedThisMonth;
        $churnRate   = $activeStart > 0 ? round(($churnedThisMonth / $activeStart) * 100, 1) : 0;

        // ── Revenue by plan ──────────────────────────────────────────────────
        $revenueByPlan = SubscriptionPlan::withSum(
            ['subscriptions as monthly_arr' => fn($q) => $q->where('billing_cycle', 'monthly')->whereIn('status', ['active', 'trialing'])],
            'amount'
        )->withSum(
            ['subscriptions as yearly_arr' => fn($q) => $q->where('billing_cycle', 'yearly')->whereIn('status', ['active', 'trialing'])],
            'amount'
        )->withCount(['subscriptions as active_count' => fn($q) => $q->whereIn('status', ['active', 'trialing'])])
        ->ordered()->get()
        ->map(function ($plan) {
            $plan->mrr = ($plan->monthly_arr ?? 0) + (($plan->yearly_arr ?? 0) / 12);
            return $plan;
        });

        // ── Top tenants by lifetime revenue ─────────────────────────────────
        $topTenants = Tenant::with('activeSubscription.plan')
            ->withSum(['payments as lifetime_revenue' => fn($q) => $q->where('status', 'succeeded')], 'amount')
            ->orderByDesc('lifetime_revenue')
            ->limit(10)
            ->get();

        // ── Payment success rate (last 90 days) ──────────────────────────────
        $paymentsLast90 = Payment::where('created_at', '>=', now()->subDays(90));
        $totalPayments  = (clone $paymentsLast90)->count();
        $successfulPayments = (clone $paymentsLast90)->where('status', 'succeeded')->count();
        $paymentSuccessRate = $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 1) : 100;

        // ── ARR ──────────────────────────────────────────────────────────────
        $mrr = Subscription::whereIn('status', ['active', 'trialing'])
            ->where('billing_cycle', 'monthly')->sum('amount')
            + Subscription::whereIn('status', ['active', 'trialing'])
            ->where('billing_cycle', 'yearly')->sum(DB::raw('amount / 12'));
        $arr = round($mrr * 12, 2);

        // ── ARPU ─────────────────────────────────────────────────────────────
        $payingTenants = Tenant::where('status', 'active')->count();
        $arpu = $payingTenants > 0 ? round($mrr / $payingTenants, 2) : 0;

        // ── New vs churned this month ─────────────────────────────────────────
        $newThisMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        $summary = compact(
            'conversionRate', 'churnRate', 'churnedThisMonth',
            'paymentSuccessRate', 'arr', 'arpu', 'mrr',
            'payingTenants', 'newThisMonth'
        );

        return view('superadmin.analytics.index', compact(
            'mrrTrend', 'tenantGrowth', 'revenueByPlan', 'topTenants', 'summary'
        ));
    }

    private function buildMonthlyRevenue(int $months): array
    {
        $labels = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
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

    private function buildTenantGrowth(int $months): array
    {
        $labels = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month    = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[]   = Tenant::whereDate('created_at', '<=', $month->endOfMonth())->count();
        }
        return ['labels' => $labels, 'data' => $data];
    }
}
