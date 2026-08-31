<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AssetAssignment;

class AssetController extends Controller
{
    public function index(string $tenant)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $current = AssetAssignment::with(['asset.category'])
            ->where('employee_id', $emp->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->get();

        $history = AssetAssignment::with(['asset.category'])
            ->where('employee_id', $emp->id)
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->paginate(15);

        return view('employee.assets.index', compact('current', 'history', 'tenant'));
    }
}
