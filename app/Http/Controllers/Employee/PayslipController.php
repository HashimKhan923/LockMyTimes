<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /* ================================================================
     | INDEX — Payslips list + YTD summary + filters
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        $year   = (int) ($request->get('year', now()->year));
        $status = $request->get('status');   // draft | finalized | paid | cancelled

        /* ───────── Available years from this employee's payslips ───────── */
        $years = Payslip::where('employee_id', $emp->id)
            ->selectRaw('YEAR(pay_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        /* ───────── All payslips for the selected year ───────── */
        $payslips = Payslip::with('payrollRun')
            ->where('employee_id', $emp->id)
            ->whereYear('pay_date', $year)
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('pay_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        /* ───────── Counters for chips (status filter) ───────── */
        $counters = Payslip::where('employee_id', $emp->id)
            ->whereYear('pay_date', $year)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'draft')     as d,
                SUM(status = 'finalized') as f,
                SUM(status = 'paid')      as p,
                SUM(status = 'cancelled') as c
            ")
            ->first();

        /* ───────── Latest payslip (paid or finalized) for the hero card ───────── */
        $latest = Payslip::with('payrollRun')
            ->where('employee_id', $emp->id)
            ->whereIn('status', ['paid', 'finalized'])
            ->orderByDesc('pay_date')
            ->orderByDesc('id')
            ->first();

        /* ───────── YTD aggregates for the selected year (paid + finalized) ───────── */
        $ytd = Payslip::where('employee_id', $emp->id)
            ->whereYear('pay_date', $year)
            ->whereIn('status', ['paid', 'finalized'])
            ->selectRaw('
                COALESCE(SUM(gross_pay),0)         as gross,
                COALESCE(SUM(net_pay),0)           as net,
                COALESCE(SUM(federal_tax + state_tax + local_tax + fica_ss + fica_medicare),0) as taxes,
                COALESCE(SUM(total_deductions),0)  as deductions,
                COALESCE(SUM(regular_hours),0)     as reg_hours,
                COALESCE(SUM(overtime_hours),0)    as ot_hours,
                COUNT(*)                           as count
            ')
            ->first();

        /* ───────── Monthly net chart (12 months of selected year) ───────── */
        $monthly = collect(range(1, 12))->map(function ($m) use ($emp, $year) {
            $net = Payslip::where('employee_id', $emp->id)
                ->whereYear('pay_date', $year)
                ->whereMonth('pay_date', $m)
                ->whereIn('status', ['paid', 'finalized'])
                ->sum('net_pay');
            return [
                'm'     => \Carbon\Carbon::create($year, $m, 1)->format('M'),
                'net'   => (float) $net,
            ];
        });

        return view('employee.payslips.index', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'year'       => $year,
            'status'     => $status,
            'payslips'   => $payslips,
            'counters'   => $counters,
            'latest'     => $latest,
            'ytd'        => $ytd,
            'years'      => $years,
            'monthly'    => $monthly,
        ]);
    }

    /* ================================================================
     | SHOW — Full payslip detail
     |================================================================*/
    public function show(string $tenant, int $payslip)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ps = Payslip::with([
                'employee.department',
                'employee.position',
                'payrollRun',
                'items',
            ])
            ->where('employee_id', $emp->id)
            ->findOrFail($payslip);

        // Draft payslips should not be visible to employees
        if ($ps->status === 'draft') {
            abort(404);
        }

        // Previous / next payslips (chronological navigation)
        $prev = Payslip::where('employee_id', $emp->id)
            ->whereIn('status', ['paid', 'finalized'])
            ->where(function ($q) use ($ps) {
                $q->where('pay_date', '<', $ps->pay_date)
                  ->orWhere(function ($q2) use ($ps) {
                      $q2->where('pay_date', $ps->pay_date)->where('id', '<', $ps->id);
                  });
            })
            ->orderByDesc('pay_date')->orderByDesc('id')
            ->first(['id', 'payslip_number']);

        $next = Payslip::where('employee_id', $emp->id)
            ->whereIn('status', ['paid', 'finalized'])
            ->where(function ($q) use ($ps) {
                $q->where('pay_date', '>', $ps->pay_date)
                  ->orWhere(function ($q2) use ($ps) {
                      $q2->where('pay_date', $ps->pay_date)->where('id', '>', $ps->id);
                  });
            })
            ->orderBy('pay_date')->orderBy('id')
            ->first(['id', 'payslip_number']);

        return view('employee.payslips.show', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'payslip'    => $ps,
            'prev'       => $prev,
            'next'       => $next,
        ]);
    }

    /* ================================================================
     | PDF — Download payslip as PDF (DomPDF)
     |================================================================*/
    public function pdf(string $tenant, int $payslip)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $ps = Payslip::with([
                'employee.department',
                'employee.position',
                'payrollRun',
                'items',
            ])
            ->where('employee_id', $emp->id)
            ->findOrFail($payslip);

        if ($ps->status === 'draft') {
            abort(404);
        }

        $currentTenant = view()->shared('currentTenant');
        $companyName   = $currentTenant->company_name ?? 'Lockmytimes';
        $currency      = view()->shared('tenantCurrency') ?? 'USD';

        // DomPDF has remote image fetching disabled (config/dompdf.php isRemoteEnabled=false),
        // so the logo has to be inlined as a base64 data URI rather than passed as a storage URL.
        $companyLogo = null;
        if ($currentTenant && $currentTenant->logo) {
            $logoPath = storage_path('app/public/'.$currentTenant->logo);
            if (is_file($logoPath)) {
                $mime = \Illuminate\Support\Facades\File::mimeType($logoPath);
                $companyLogo = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('employee.payslips.pdf', [
                'payslip'     => $ps,
                'emp'         => $emp,
                'companyName' => $companyName,
                'companyLogo' => $companyLogo,
                'currency'    => $currency,
            ])
            ->setPaper('a4');

        $filename = 'Payslip-'.$ps->payslip_number.'-'.$ps->pay_date->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}