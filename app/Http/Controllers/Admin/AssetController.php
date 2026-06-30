<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Asset;
use App\Models\Tenant\AssetAssignment;
use App\Models\Tenant\AssetCategory;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Location;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $status   = $request->get('status', 'all');
        $category = $request->get('category');
        $search   = $request->get('search');

        $query = Asset::with(['category', 'location', 'currentAssignment.employee'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($category) {
            $query->where('category_id', $category);
        }
        if ($search) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('asset_code', 'like', "%$search%")
                ->orWhere('serial_number', 'like', "%$search%")
                ->orWhere('brand', 'like', "%$search%")
            );
        }

        $assets     = $query->paginate(16)->withQueryString();
        $categories = AssetCategory::where('is_active', true)->orderBy('name')->get();
        $employees  = Employee::active()->orderBy('first_name')->get();
        $locations  = Location::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total'      => Asset::count(),
            'available'  => Asset::where('status', 'available')->count(),
            'assigned'   => Asset::where('status', 'assigned')->count(),
            'maintenance'=> Asset::where('status', 'maintenance')->count(),
            'total_value'=> Asset::sum('purchase_cost'),
        ];

        return view('admin.assets.index', compact(
            'assets', 'categories', 'employees', 'locations',
            'stats', 'status', 'tenant'
        ));
    }

    /* ================================================================
     | SHOW
     |================================================================*/
    public function show(string $tenant, Asset $asset)
    {
        $asset->load([
            'category', 'location',
            'assignments.employee',
            'assignments.assignedBy',
            'currentAssignment.employee',
        ]);

        return view('admin.assets.show', compact('asset', 'tenant'));
    }

    /* ================================================================
     | STORE
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'category_id'   => 'required|exists:asset_categories,id',
            'location_id'   => 'nullable|exists:locations,id',
            'serial_number' => 'nullable|string|max:100',
            'brand'         => 'nullable|string|max:100',
            'model'         => 'nullable|string|max:100',
            'purchase_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'warranty_until'=> 'nullable|date',
            'vendor'        => 'nullable|string|max:200',
            'invoice_number'=> 'nullable|string|max:100',
            'condition'     => 'required|in:new,good,fair,damaged,retired',
            'description'   => 'nullable|string',
            'notes'         => 'nullable|string',
            'image'         => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('assets/images', 'public');
        }

        $data['status']     = 'available';
        $next = (Asset::max('id') ?? 0) + 1;
        $data['asset_code'] = 'AST-'.str_pad($next, 4, '0', STR_PAD_LEFT);

        Asset::create($data);
        return back()->with('success', 'Asset added successfully.');
    }

    /* ================================================================
     | UPDATE
     |================================================================*/
    public function update(string $tenant, Request $request, Asset $asset)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'category_id'   => 'required|exists:asset_categories,id',
            'location_id'   => 'nullable|exists:locations,id',
            'serial_number' => 'nullable|string|max:100',
            'brand'         => 'nullable|string|max:100',
            'model'         => 'nullable|string|max:100',
            'purchase_cost' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'warranty_until'=> 'nullable|date',
            'vendor'        => 'nullable|string|max:200',
            'condition'     => 'required|in:new,good,fair,damaged,retired',
            'status'        => 'required|in:available,assigned,maintenance,retired,lost',
            'notes'         => 'nullable|string',
        ]);

        $asset->update($data);
        return back()->with('success', 'Asset updated.');
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Asset $asset)
    {
        if ($asset->status === 'assigned') {
            return back()->with('error', 'Cannot delete an assigned asset. Return it first.');
        }
        $asset->delete();
        return redirect()
            ->route('admin.assets.index', $tenant)
            ->with('success', 'Asset deleted.');
    }

    /* ================================================================
     | ASSIGN
     |================================================================*/
    public function assign(string $tenant, Request $request, Asset $asset)
    {
        $data = $request->validate([
            'employee_id'             => 'required|exists:employees,id',
            'assigned_at'             => 'required|date',
            'condition_at_assignment' => 'required|in:new,good,fair,poor,damaged',
            'assignment_notes'        => 'nullable|string|max:500',
        ]);

        if ($asset->status === 'assigned') {
            return back()->with('error', 'This asset is already assigned. Return it first.');
        }

        AssetAssignment::create([
            'asset_id'                => $asset->id,
            'employee_id'             => $data['employee_id'],
            'assigned_by'             => auth()->id(),
            'assigned_at'             => Carbon::parse($data['assigned_at']),
            'condition_at_assignment' => $data['condition_at_assignment'],
            'assignment_notes'        => $data['assignment_notes'] ?? null,
        ]);

        $asset->update(['status' => 'assigned']);

        $employee = Employee::find($data['employee_id']);
        return back()->with('success', "Asset assigned to {$employee->full_name}.");
    }

    /* ================================================================
     | RETURN
     |================================================================*/
    public function return(string $tenant, Request $request, Asset $asset)
    {
        $data = $request->validate([
            'condition_at_return' => 'required|in:new,good,fair,poor,damaged',
            'return_notes'        => 'nullable|string|max:500',
        ]);

        $assignment = $asset->currentAssignment;
        if (! $assignment) {
            return back()->with('error', 'No active assignment found for this asset.');
        }

        $assignment->update([
            'returned_at'          => now(),
            'condition_at_return'  => $data['condition_at_return'],
            'return_notes'         => $data['return_notes'] ?? null,
        ]);

        $asset->update([
            'status'    => 'available',
            'condition' => $data['condition_at_return'],
        ]);

        return back()->with('success', 'Asset returned and marked as available.');
    }

    /* ================================================================
     | CATEGORIES
     |================================================================*/
    public function categories(string $tenant)
    {
        $categories = AssetCategory::withCount('assets')->orderBy('name')->get();
        return view('admin.assets.categories', compact('categories', 'tenant'));
    }

    public function storeCategory(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:20|unique:asset_categories,code',
            'color'       => 'nullable|string|max:7',
            'icon'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        AssetCategory::create(array_merge($data, ['is_active' => true]));
        return back()->with('success', 'Category created.');
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $assets = Asset::with(['category', 'currentAssignment.employee'])->latest()->get();

        $columns = ['Asset Tag', 'Name', 'Category', 'Brand', 'Model', 'Serial No.', 'Status', 'Assigned To', 'Purchase Date', 'Value'];

        $rows = $assets->map(fn($a) => [
            $a->asset_tag ?? '-',
            $a->name,
            $a->category?->name ?? '-',
            $a->brand ?? '-',
            $a->model ?? '-',
            $a->serial_number ?? '-',
            ucfirst($a->status),
            $a->currentAssignment?->employee?->full_name ?? 'Unassigned',
            $a->purchase_date?->format('Y-m-d') ?? '-',
            $a->purchase_price ? number_format($a->purchase_price, 2) : '-',
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Assets Report', $columns, $rows, 'assets-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'assets-'.now()->format('Y-m-d').'.xlsx');
    }
}