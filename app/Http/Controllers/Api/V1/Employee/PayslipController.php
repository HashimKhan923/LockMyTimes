<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Tenant\Payslip;
use App\Models\Tenant\Setting;
use App\Services\TenantManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON mirror of Employee/PayslipController — same queries/aggregates, PDF
 * generation reused verbatim (DomPDF), just streamed as a binary API
 * response instead of a browser download.
 */
class PayslipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $year = (int) $request->get('year', now()->year);
        $status = $request->get('status');

        $years = Payslip::where('employee_id', $emp->id)
            ->selectRaw('YEAR(pay_date) as y')
            ->groupBy('y')
            ->orderByDesc('y')
            ->pluck('y')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $payslips = Payslip::with('payrollRun')
            ->where('employee_id', $emp->id)
            ->whereYear('pay_date', $year)
            ->where('status', '!=', 'draft')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('pay_date')
            ->orderByDesc('id')
            ->paginate(12);

        $latest = Payslip::with('payrollRun')
            ->where('employee_id', $emp->id)
            ->whereIn('status', ['paid', 'finalized'])
            ->orderByDesc('pay_date')
            ->orderByDesc('id')
            ->first();

        $ytd = Payslip::where('employee_id', $emp->id)
            ->whereYear('pay_date', $year)
            ->whereIn('status', ['paid', 'finalized'])
            ->selectRaw('
                COALESCE(SUM(gross_pay),0) as gross,
                COALESCE(SUM(net_pay),0) as net,
                COALESCE(SUM(federal_tax + state_tax + local_tax + fica_ss + fica_medicare),0) as taxes,
                COALESCE(SUM(total_deductions),0) as deductions,
                COALESCE(SUM(regular_hours),0) as reg_hours,
                COALESCE(SUM(overtime_hours),0) as ot_hours,
                COUNT(*) as count
            ')
            ->first();

        $monthly = collect(range(1, 12))->map(function ($m) use ($emp, $year) {
            $net = Payslip::where('employee_id', $emp->id)
                ->whereYear('pay_date', $year)
                ->whereMonth('pay_date', $m)
                ->whereIn('status', ['paid', 'finalized'])
                ->sum('net_pay');

            return ['month' => Carbon::create($year, $m, 1)->format('M'), 'net' => (float) $net];
        });

        return response()->json([
            'year' => $year,
            'years' => $years,
            'payslips' => PayslipResource::collection($payslips->items()),
            'pagination' => [
                'current_page' => $payslips->currentPage(),
                'last_page' => $payslips->lastPage(),
                'total' => $payslips->total(),
            ],
            'latest' => $latest ? new PayslipResource($latest) : null,
            'ytd' => [
                'gross' => (float) $ytd->gross,
                'net' => (float) $ytd->net,
                'taxes' => (float) $ytd->taxes,
                'deductions' => (float) $ytd->deductions,
                'regular_hours' => (float) $ytd->reg_hours,
                'overtime_hours' => (float) $ytd->ot_hours,
                'count' => (int) $ytd->count,
            ],
            'monthly' => $monthly,
        ]);
    }

    public function show(Request $request, int $payslip): JsonResponse
    {
        $emp = $this->employeeOrFail($request);

        $ps = Payslip::with(['employee.department', 'employee.position', 'payrollRun', 'items'])
            ->where('employee_id', $emp->id)
            ->findOrFail($payslip);

        abort_if($ps->status === 'draft', 404);

        return response()->json(['payslip' => new PayslipResource($ps)]);
    }

    public function pdf(Request $request, int $payslip): Response
    {
        $emp = $this->employeeOrFail($request);

        $ps = Payslip::with(['employee.department', 'employee.position', 'payrollRun', 'items'])
            ->where('employee_id', $emp->id)
            ->findOrFail($payslip);

        abort_if($ps->status === 'draft', 404);

        $tenant = app(TenantManager::class)->current();
        $companyName = $tenant?->company_name ?? 'Lockmytimes';
        $currency = Setting::get('general.currency', 'USD');

        $pdf = Pdf::loadView('employee.payslips.pdf', [
            'payslip' => $ps,
            'emp' => $emp,
            'companyName' => $companyName,
            'currency' => $currency,
        ])->setPaper('a4');

        $filename = 'Payslip-'.$ps->payslip_number.'-'.$ps->pay_date->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    protected function employeeOrFail(Request $request)
    {
        $emp = $request->user()->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return $emp;
    }
}
