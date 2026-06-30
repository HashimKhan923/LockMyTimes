<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Asset;
use App\Models\Tenant\Document;
use App\Models\Tenant\Employee;
use App\Models\Tenant\JobPosting;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(string $tenant, Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = [];

        try {
            Employee::where(fn($q2) => $q2
                ->where('first_name',     'like', "%$q%")
                ->orWhere('last_name',    'like', "%$q%")
                ->orWhere('email',        'like', "%$q%")
                ->orWhere('employee_code','like', "%$q%")
            )->limit(5)->get()->each(function ($e) use (&$results, $tenant) {
                $results[] = [
                    'type'     => 'Employee',
                    'icon'     => 'user',
                    'color'    => 'bg-blue-50 text-blue-600',
                    'title'    => $e->full_name,
                    'subtitle' => $e->email ?? $e->employee_code,
                    'url'      => route('admin.employees.show', [$tenant, $e->id]),
                ];
            });
        } catch (\Throwable $e) {}

        try {
            Asset::where(fn($q2) => $q2
                ->where('name',           'like', "%$q%")
                ->orWhere('asset_code',   'like', "%$q%")
                ->orWhere('serial_number','like', "%$q%")
                ->orWhere('brand',        'like', "%$q%")
            )->limit(4)->get()->each(function ($a) use (&$results, $tenant) {
                $results[] = [
                    'type'     => 'Asset',
                    'icon'     => 'package',
                    'color'    => 'bg-amber-50 text-amber-600',
                    'title'    => $a->name,
                    'subtitle' => $a->asset_code . ($a->brand ? ' · ' . $a->brand : ''),
                    'url'      => route('admin.assets.show', [$tenant, $a->id]),
                ];
            });
        } catch (\Throwable $e) {}

        try {
            Document::where('title', 'like', "%$q%")
                ->limit(4)->get()->each(function ($d) use (&$results, $tenant) {
                    $results[] = [
                        'type'     => 'Document',
                        'icon'     => 'file-text',
                        'color'    => 'bg-purple-50 text-purple-600',
                        'title'    => $d->title,
                        'subtitle' => $d->category ?? 'Document',
                        'url'      => route('admin.documents.index', $tenant),
                    ];
                });
        } catch (\Throwable $e) {}

        try {
            JobPosting::with('department')->where('title', 'like', "%$q%")
                ->limit(3)->get()->each(function ($j) use (&$results, $tenant) {
                    $results[] = [
                        'type'     => 'Job',
                        'icon'     => 'briefcase',
                        'color'    => 'bg-emerald-50 text-emerald-600',
                        'title'    => $j->title,
                        'subtitle' => ucfirst($j->status) . ($j->department ? ' · ' . $j->department->name : ''),
                        'url'      => route('admin.recruitment.show', [$tenant, $j->id]),
                    ];
                });
        } catch (\Throwable $e) {}

        return response()->json($results);
    }
}
