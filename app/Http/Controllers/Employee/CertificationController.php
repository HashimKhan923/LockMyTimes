<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Certification;
use App\Services\ExportService;

class CertificationController extends Controller
{
    public function index(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $certifications = $this->myCertifications($emp);

        return view('employee.certifications.index', compact('certifications', 'tenant'));
    }

    public function export(string $tenant, ExportService $exporter)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $certifications = $this->myCertifications($emp);

        $columns = ['Certification', 'Credential ID', 'Issuer', 'Issued', 'Expires', 'Status', 'Verified'];
        $rows = $certifications->map(function ($cert) {
            $isExpired = $cert->expiry_date
                && now()->startOfDay()->diffInDays($cert->expiry_date->copy()->startOfDay(), false) < 0;

            return [
                $cert->name,
                $cert->credential_id ?: '-',
                $cert->issuer ?: '-',
                $cert->issue_date?->format('Y-m-d') ?? '-',
                $cert->expiry_date?->format('Y-m-d') ?? 'No expiry',
                $isExpired ? 'Expired' : 'Active',
                $cert->is_verified ? 'Yes' : 'No',
            ];
        });

        $employeeDetails = [
            'name'            => $emp->full_name,
            'code'            => $emp->employee_code,
            'department'      => $emp->department?->name,
            'position'        => $emp->position?->title,
            'email'           => $emp->email,
            'phone'           => $emp->phone,
            'employment_type' => $emp->employment_type ? ucfirst(str_replace('_', ' ', $emp->employment_type)) : null,
            'hire_date'       => $emp->hire_date?->format('M j, Y'),
        ];

        $filename = 'certifications-'.\Illuminate\Support\Str::slug($emp->employee_code ?: $emp->id).'.pdf';

        return $exporter->pdf("{$emp->full_name} — Certifications", $columns, $rows, $filename, 'landscape', $employeeDetails);
    }

    private function myCertifications($emp)
    {
        return Certification::where('employee_id', $emp->id)
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderByDesc('issue_date')
            ->get();
    }
}
