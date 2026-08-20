<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Department;
use App\Models\Tenant\Employee;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(string $tenant)
    {
        $departments = Department::with(['manager','parent','children'])
            ->withCount('employees')
            ->orderBy('name')->get();
        return view('admin.departments.index', compact('departments','tenant'));
    }

    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'code'       => 'nullable|string|max:20|unique:departments,code',
            'description'=> 'nullable|string',
            'parent_id'  => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'color'      => 'nullable|string|max:7',
        ]);
        $data['parent_id']  = ($data['parent_id']  ?? null) ?: null;
        $data['manager_id'] = ($data['manager_id'] ?? null) ?: null;
        Department::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Department created.');
    }

    public function update(string $tenant, Request $request, Department $department)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'code'       => 'nullable|string|max:20|unique:departments,code,'.$department->id,
            'description'=> 'nullable|string',
            'parent_id'  => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:employees,id',
            'color'      => 'nullable|string|max:7',
            'is_active'  => 'boolean',
        ]);
        $data['parent_id']  = ($data['parent_id']  ?? null) ?: null;
        $data['manager_id'] = ($data['manager_id'] ?? null) ?: null;
        $department->update($data);
        return back()->with('success', 'Department updated.');
    }

    public function destroy(string $tenant, Department $department)
    {
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete a department with active employees.');
        }
        $department->delete();
        return back()->with('success', 'Department deleted.');
    }
}