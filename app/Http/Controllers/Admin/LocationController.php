<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(string $tenant)
    {
        $locations = Location::withCount('employees')->orderBy('name')->get();
        return view('admin.locations.index', compact('locations','tenant'));
    }

    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:150',
            'code'                   => 'nullable|string|max:20|unique:locations,code',
            'address_line1'          => 'nullable|string',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:50',
            'postal_code'            => 'nullable|string|max:20',
            'timezone'               => 'nullable|string',
            'latitude'               => 'nullable|numeric|between:-90,90',
            'longitude'              => 'nullable|numeric|between:-180,180',
            'geofence_radius_meters' => 'nullable|integer|min:10|max:5000',
            'is_headquarters'        => 'boolean',
        ]);
        Location::create(array_merge($data, ['is_active' => true, 'country' => 'US']));
        return back()->with('success', 'Location created.');
    }

    public function update(string $tenant, Request $request, Location $location)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:150',
            'address_line1'          => 'nullable|string',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:50',
            'timezone'               => 'nullable|string',
            'latitude'               => 'nullable|numeric|between:-90,90',
            'longitude'              => 'nullable|numeric|between:-180,180',
            'geofence_radius_meters' => 'nullable|integer|min:10|max:5000',
            'is_headquarters'        => 'boolean',
            'is_active'              => 'boolean',
        ]);
        $location->update($data);
        return back()->with('success', 'Location updated.');
    }

    public function destroy(string $tenant, Location $location)
    {
        if ($location->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete a location with assigned employees.');
        }
        $location->delete();
        return back()->with('success', 'Location deleted.');
    }
}