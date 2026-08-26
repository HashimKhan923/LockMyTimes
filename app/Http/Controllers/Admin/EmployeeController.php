<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Location;
use App\Models\Tenant\Position;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use App\Services\ExportService;
use App\Services\MailService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $query = Employee::with(['department', 'position', 'location', 'manager'])
            ->orderBy('first_name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($dept = $request->get('department')) {
            $query->where('department_id', $dept);
        }

        if ($status = $request->get('status')) {
            $query->where('employment_status', $status);
        }

        if ($type = $request->get('type')) {
            $query->where('employment_type', $type);
        }

        $employees   = $query->paginate(20)->withQueryString();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total'      => Employee::count(),
            'active'     => Employee::where('employment_status','active')->count(),
            'on_leave'   => Employee::where('employment_status','on_leave')->count(),
            'full_time'  => Employee::where('employment_type','full_time')->count(),
        ];

        return view('admin.employees.index',
            compact('employees', 'departments', 'stats', 'tenant'));
    }

    /* ================================================================
     | CREATE
     |================================================================*/
    public function create(string $tenant)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $positions   = Position::where('is_active', true)->orderBy('title')->get();
        $locations   = Location::where('is_active', true)->orderBy('name')->get();
        $managers    = Employee::active()->orderBy('first_name')->get();
        $employee    = new \App\Models\Tenant\Employee();
        $generalTimezone = Setting::get('general.timezone', config('app.timezone'));
        $suggestedCode = Employee::generateNextCode();

        return view('admin.employees.create',
            compact('departments', 'positions', 'locations', 'managers', 'employee', 'tenant', 'generalTimezone', 'suggestedCode'));
    }

    /* ================================================================
     | STORE
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'required|email|unique:employees,email',
            'employee_code'     => 'nullable|string|max:30|unique:employees,employee_code',
            'phone'             => 'nullable|string|max:30',
            'date_of_birth'     => 'nullable|date|before:today',
            'gender'            => 'nullable|in:male,female,non_binary,prefer_not_to_say',
            'hire_date'         => 'required|date',
            'department_id'     => 'nullable|exists:departments,id',
            'position_id'       => 'nullable|exists:positions,id',
            'location_id'       => 'nullable|exists:locations,id',
            'employment_mode'   => 'nullable|in:onsite,remote,hybrid',
            'location_ids'      => 'nullable|array',
            'location_ids.*'    => 'exists:locations,id',
            'manager_id'        => 'nullable|exists:employees,id',
            'employment_status' => 'required|in:active,on_leave,suspended,terminated',
            'employment_type'   => 'required|in:full_time,part_time,contract,intern,temporary',
            'base_salary'       => 'nullable|numeric|min:0',
            'salary_frequency'  => 'nullable|in:hourly,weekly,bi_weekly,semi_monthly,monthly,annually',
            'avatar'            => 'nullable|image|max:2048',
            // Empty/omitted = inherit the company's General Settings timezone dynamically (see
            // Employee::attendanceTimezone()) rather than freezing a snapshot at hire time.
            'timezone'          => 'nullable|timezone',
        ]);

        $locationIds = $validated['location_ids'] ?? [];
        unset($validated['location_ids']);
        $validated['base_salary'] = $validated['base_salary'] ?? 0;
        $validated['employee_code'] = ($validated['employee_code'] ?? null) ?: null;
        $timezone = $validated['timezone'] ?? null;
        unset($validated['timezone']);

        // Enforce plan employee limit
        $currentTenant = \App\Models\Main\Tenant::where('slug', $tenant)->first();
        if ($currentTenant && ! $currentTenant->canAddEmployee()) {
            $max = $currentTenant->getPlanLimit('max_employees');
            return back()->withInput()->with('error',
                "You have reached your plan limit of {$max} employees. Please upgrade your plan to add more.");
        }

        DB::beginTransaction();
        try {
            // Generate employee code only if the admin didn't manually enter one
            $validated['employee_code'] = $validated['employee_code'] ?: Employee::generateNextCode();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $validated['avatar'] = $request->file('avatar')
                    ->store('employees/avatars', 'public');
            }

            $employee = Employee::create($validated);
            $this->syncLocations($employee, $locationIds);

            // Create a user account for this employee
            $tempPassword = Str::random(10);
            $user = User::create([
                'employee_id'          => $employee->id,
                'name'                 => $employee->full_name,
                'email'                => $employee->email,
                'password'             => Hash::make($tempPassword),
                'must_change_password' => true,
                'is_active'            => true,
                'email_verified_at'    => now(),
                'timezone'             => $timezone,
            ]);
            $user->assignRole('Employee');

            // Link user back to employee
            $employee->update(['user_id' => $user->id]);

            // Update tenant employee count in main DB
            \App\Models\Main\Tenant::where('slug', $tenant)
                ->increment('employees_count');

            DB::commit();

            app(MailService::class)->sendEmployeeWelcome(
                $employee->load(['department', 'position']),
                $tempPassword
            );
            NotificationService::notifyAdmins(
                "New employee {$employee->full_name} has been added",
                'employee.created', 'user-plus', '#6C7DF7',
                route('admin.employees.show', [$tenant, $employee->id]),
                [], auth()->id()
            );

            return redirect()
                ->route('admin.employees.show', [$tenant, $employee->id])
                ->with('success', "Employee {$employee->full_name} added successfully. Temp password: {$tempPassword}");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create employee: '.$e->getMessage());
        }
    }

    /* ================================================================
     | SHOW
     |================================================================*/
    public function show(string $tenant, Employee $employee)
    {
        $employee->load([
            'department', 'position', 'location', 'manager',
            'subordinates', 'emergencyContacts', 'documents',
            'leaveBalances.leaveType',
            'assignedAssets.asset.category',
            'trainingEnrollments.training',
            'loans', 'salaryAdvances',
        ]);

        return view('admin.employees.show', compact('employee', 'tenant'));
    }

    /* ================================================================
     | EDIT
     |================================================================*/
    public function edit(string $tenant, Employee $employee)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $positions   = Position::where('is_active', true)->orderBy('title')->get();
        $locations   = Location::where('is_active', true)->orderBy('name')->get();
        $managers    = Employee::active()
            ->where('id', '!=', $employee->id)
            ->orderBy('first_name')->get();
        $generalTimezone = Setting::get('general.timezone', config('app.timezone'));

        return view('admin.employees.edit',
            compact('employee', 'departments', 'positions', 'locations', 'managers', 'tenant', 'generalTimezone'));
    }

    /* ================================================================
     | UPDATE
     |================================================================*/
    public function update(string $tenant, Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'required|email|unique:employees,email,'.$employee->id,
            'employee_code'     => 'required|string|max:30|unique:employees,employee_code,'.$employee->id,
            'phone'             => 'nullable|string|max:30',
            'date_of_birth'     => 'nullable|date|before:today',
            'gender'            => 'nullable|in:male,female,non_binary,prefer_not_to_say',
            'hire_date'         => 'required|date',
            'department_id'     => 'nullable|exists:departments,id',
            'position_id'       => 'nullable|exists:positions,id',
            'location_id'       => 'nullable|exists:locations,id',
            'employment_mode'   => 'nullable|in:onsite,remote,hybrid',
            'location_ids'      => 'nullable|array',
            'location_ids.*'    => 'exists:locations,id',
            'manager_id'        => 'nullable|exists:employees,id',
            'employment_status' => 'required|in:active,on_leave,suspended,terminated,retired',
            'employment_type'   => 'required|in:full_time,part_time,contract,intern,temporary',
            'base_salary'       => 'nullable|numeric|min:0',
            'salary_frequency'  => 'nullable|in:hourly,weekly,bi_weekly,semi_monthly,monthly,annually',
            'avatar'            => 'nullable|image|max:2048',
            // Empty/omitted = inherit the company's General Settings timezone dynamically (see
            // Employee::attendanceTimezone()) rather than freezing a snapshot at hire time.
            'timezone'          => 'nullable|timezone',
        ]);

        $locationIds = $validated['location_ids'] ?? [];
        unset($validated['location_ids']);
        $validated['base_salary'] = $validated['base_salary'] ?? 0;
        $timezone = $validated['timezone'] ?? null;
        unset($validated['timezone']);

        if ($request->hasFile('avatar')) {
            if ($employee->avatar) Storage::disk('public')->delete($employee->avatar);
            $validated['avatar'] = $request->file('avatar')->store('employees/avatars','public');
        }

        $employee->update($validated);
        $this->syncLocations($employee, $locationIds);

        // Sync user record — timezone is admin-only (employees cannot set their own; see
        // Employee\SettingsController, which no longer accepts a timezone field at all).
        if ($employee->user) {
            $employee->user->update([
                'name'     => $employee->full_name,
                'email'    => $employee->email,
                'timezone' => $timezone,
            ]);
        }

        return redirect()
            ->route('admin.employees.show', [$tenant, $employee->id])
            ->with('success', 'Employee updated successfully.');
    }

    /* ================================================================
     | TERMINATE
     |================================================================*/
    public function terminate(string $tenant, Request $request, Employee $employee)
    {
        $request->validate([
            'termination_date'   => 'required|date',
            'termination_reason' => 'required|string|max:255',
        ]);

        $employee->update([
            'employment_status'  => 'terminated',
            'termination_date'   => $request->termination_date,
            'termination_reason' => $request->termination_reason,
        ]);

        if ($employee->user) {
            $employee->user->update(['is_active' => false]);
        }

        \App\Models\Main\Tenant::where('slug', $tenant)->decrement('employees_count');

        return back()->with('success', "{$employee->full_name} has been terminated.");
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Employee $employee)
    {
        $name = $employee->full_name;

        if ($employee->user) {
            $employee->user->update(['is_active' => false]);
        }

        // Soft delete only — Employee uses SoftDeletes, so this just sets
        // deleted_at and does NOT trigger the cascadeOnDelete() foreign keys
        // on attendances/payrolls/leave_requests, preserving history intact.
        $employee->delete();

        \App\Models\Main\Tenant::where('slug', $tenant)->decrement('employees_count');

        return redirect()
            ->route('admin.employees.index', $tenant)
            ->with('success', "{$name} has been deleted.");
    }

    /* ================================================================
     | ORG CHART DATA
     |================================================================*/
    public function orgChart(string $tenant)
    {
        $employees = Employee::active()
            ->with(['department', 'position'])
            ->select('id','first_name','last_name','manager_id','department_id','position_id','avatar')
            ->get();

        return view('admin.employees.org-chart', compact('employees', 'tenant'));
    }

    /* ================================================================
     | IMPORT (CSV)
     |================================================================*/
    public function import(string $tenant, Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $path    = $request->file('file')->getRealPath();
        $rows    = array_map('str_getcsv', file($path));
        $headers = array_shift($rows);

        $created = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $data = array_combine($headers, $row);
            try {
                Employee::create([
                    'employee_code'     => Employee::generateNextCode(),
                    'first_name'        => trim($data['first_name'] ?? ''),
                    'last_name'         => trim($data['last_name'] ?? ''),
                    'email'             => trim($data['email'] ?? ''),
                    'hire_date'         => $data['hire_date'] ?? now()->toDateString(),
                    'employment_status' => 'active',
                    'employment_type'   => $data['employment_type'] ?? 'full_time',
                    'base_salary'       => $data['base_salary'] ?? 0,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row ".($i + 2).": ".$e->getMessage();
            }
        }

        $msg = "Imported {$created} employees.";
        if ($errors) $msg .= ' '.count($errors).' rows failed.';

        return back()->with('success', $msg);
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $employees = Employee::with(['department', 'position', 'location'])
            ->orderBy('first_name')
            ->get();

        $columns = ['Code', 'First Name', 'Last Name', 'Email', 'Department', 'Position', 'Location', 'Type', 'Status', 'Hire Date', 'Salary'];

        $rows = $employees->map(fn($e) => [
            $e->employee_code,
            $e->first_name,
            $e->last_name,
            $e->email,
            $e->department?->name ?? '-',
            $e->position?->title ?? '-',
            $e->location?->name ?? '-',
            ucfirst(str_replace('_', ' ', $e->employment_type)),
            ucfirst($e->employment_status),
            $e->hire_date?->format('Y-m-d') ?? '-',
            $e->base_salary ? number_format($e->base_salary, 2) : '-',
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Employee Report', $columns, $rows, 'employees-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'employees-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Sync the employee_locations pivot to exactly what was checked in the "Additional Locations"
     * form — including allowing the admin to uncheck the current primary location's own box.
     * This doesn't strand the primary location: attendance/holiday visibility (assignedLocationIds()
     * in the attendance controllers) always unions in $employee->location_id directly regardless of
     * what's in this pivot, so nothing else in the app depends on the primary having a pivot row.
     */
    private function syncLocations(Employee $employee, array $locationIds): void
    {
        $sync = [];
        foreach (array_unique(array_filter($locationIds)) as $id) {
            $sync[$id] = ['is_primary' => (int) $id === (int) $employee->location_id];
        }

        $employee->locations()->sync($sync);
    }
}