<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(string $tenant)
    {
        $positions = Position::with('department')
            ->withCount('employees')
            ->orderBy('title')->get();
        $departments = \App\Models\Tenant\Department::where('is_active',true)->orderBy('name')->get();
        return view('admin.positions.index', compact('positions','departments','tenant'));
    }

    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:150',
            'code'          => 'nullable|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'description'   => 'nullable|string',
            'min_salary'    => 'nullable|numeric|min:0',
            'max_salary'    => 'nullable|numeric|min:0',
            'level'         => 'nullable|in:entry,junior,mid,senior,lead,manager,director,executive',
        ]);
        Position::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Position created.');
    }

    public function update(string $tenant, Request $request, Position $position)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:150',
            'department_id' => 'nullable|exists:departments,id',
            'min_salary'    => 'nullable|numeric|min:0',
            'max_salary'    => 'nullable|numeric|min:0',
            'level'         => 'nullable|in:entry,junior,mid,senior,lead,manager,director,executive',
            'is_active'     => 'boolean',
        ]);
        $position->update($data);
        return back()->with('success', 'Position updated.');
    }

    public function destroy(string $tenant, Position $position)
    {
        if ($position->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete a position with assigned employees.');
        }
        $position->delete();
        return back()->with('success', 'Position deleted.');
    }
}