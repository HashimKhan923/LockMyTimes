<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Certification;

class CertificationController extends Controller
{
    public function index(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $certifications = Certification::where('employee_id', $emp->id)
            ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
            ->orderByDesc('issue_date')
            ->get();

        return view('employee.certifications.index', compact('certifications', 'tenant'));
    }
}
