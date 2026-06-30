<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Goal;
use App\Models\Tenant\Kudo;
use App\Models\Tenant\PerformanceReview;
use App\Models\Tenant\ReviewCycle;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function index(string $tenant, Request $request)
    {
        $tab = $request->get('tab', 'reviews');

        $stats = [
            'active_cycles'    => ReviewCycle::whereIn('status',['open','in_progress'])->count(),
            'pending_reviews'  => PerformanceReview::where('status','pending')->count(),
            'completed_reviews'=> PerformanceReview::where('status','completed')
                                   ->whereYear('created_at', now()->year)->count(),
            'active_goals'     => Goal::whereIn('status',['not_started','in_progress'])->count(),
            'kudos_this_month' => Kudo::whereMonth('created_at', now()->month)->count(),
        ];

        $employees = Employee::active()->with('department')->orderBy('first_name')->get();
        $cycles    = ReviewCycle::latest()->get();

        $reviews = PerformanceReview::with(['employee.department','reviewer','cycle'])
            ->latest()->paginate(15)->withQueryString();

        $goals = Goal::with(['employee.department'])
            ->latest()->paginate(15, ['*'], 'goals_page')->withQueryString();

        $kudos = Kudo::with(['fromEmployee','toEmployee'])
            ->where('is_public', true)
            ->latest()->take(30)->get();

        return view('admin.performance.index', compact(
            'tab','stats','employees','cycles',
            'reviews','goals','kudos','tenant'
        ));
    }

    public function goalsIndex(string $tenant, Request $request)
    {
        $request->merge(['tab' => 'goals']);
        return $this->index($tenant, $request);
    }

    /* ================================================================
     | REVIEW CYCLES
     |================================================================*/
    public function storeCycle(string $tenant, Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:annual,semi_annual,quarterly,monthly,project_based,probation',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'description' => 'nullable|string',
        ]);

        $cycle = ReviewCycle::create(array_merge($data, ['status' => 'open']));

        if ($request->boolean('auto_assign')) {
            Employee::active()->each(function ($employee) use ($cycle) {
                PerformanceReview::create([
                    'review_cycle_id' => $cycle->id,
                    'employee_id'     => $employee->id,
                    'reviewer_id'     => $employee->manager_id ?? $employee->id,
                    'review_type'     => 'manager',
                    'status'          => 'pending',
                    'due_date'        => $cycle->end_date,
                ]);
            });
        }
        return back()->with('success', "Review cycle \"{$cycle->name}\" created.");
    }

    /* ================================================================
     | REVIEWS
     |================================================================*/
    public function storeReview(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'review_cycle_id' => 'required|exists:review_cycles,id',
            'reviewer_id'     => 'required|exists:employees,id',
            'review_type'     => 'required|in:self,manager,peer,subordinate,360',
            // due_date not in review_cycles table
        ]);

        PerformanceReview::create(array_merge($data, ['status' => 'pending']));
        return back()->with('success', 'Performance review created.');
    }

    public function showReview(string $tenant, PerformanceReview $review)
    {
        $review->load(['employee.department','reviewer','cycle']);
        return view('admin.performance.review', compact('review','tenant'));
    }

    public function submitReview(string $tenant, Request $request, PerformanceReview $review)
    {
        $data = $request->validate([
            'overall_rating'       => 'required|numeric|min:1|max:5',
            'strengths'            => 'nullable|string',
            'areas_for_improvement'=> 'nullable|string',
            'manager_comments'     => 'nullable|string',
        ]);

        $review->update(array_merge($data, [
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]));

        return back()->with('success', 'Review submitted successfully.');
    }

    /* ================================================================
     | GOALS
     |================================================================*/
    public function storeGoal(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'type'        => 'required|in:okr,smart,kpi,personal',
            'category'    => 'required|in:individual,team,department,company',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'weight'      => 'nullable|numeric|min:0|max:100',
            'key_results' => 'nullable|string',
        ]);

        // Convert newline-separated key results to JSON array
        if (! empty($data['key_results'])) {
            $data['key_results'] = array_values(array_filter(
                array_map('trim', explode("\n", $data['key_results']))
            ));
        } else {
            $data['key_results'] = null;
        }

        Goal::create(array_merge($data, [
            'status'   => 'not_started',
            'progress' => 0,
        ]));

        return back()->with('success', 'Goal created.');
    }

    public function updateGoal(string $tenant, Request $request, Goal $goal)
    {
        $request->validate(['progress' => 'required|integer|min:0|max:100']);

        $progress = (int) $request->progress;
        $status   = match(true) {
            $progress >= 100 => 'completed',
            $progress > 0    => 'in_progress',
            default          => 'not_started',
        };

        $goal->update([
            'progress'     => $progress,
            'status'       => $status,
            'completed_at' => $progress >= 100 ? now() : null,
        ]);

        return back()->with('success', 'Goal progress updated.');
    }

    public function destroyGoal(string $tenant, Goal $goal)
    {
        $goal->delete();
        return back()->with('success', 'Goal deleted.');
    }

    /* ================================================================
     | KUDOS
     |================================================================*/
    public function storeKudo(string $tenant, Request $request)
    {
        $request->validate([
            'to_employee_id'   => 'required|exists:employees,id',
            'from_employee_id' => 'required|exists:employees,id',
            'message'          => 'required|string|max:500',
            'badge'            => 'nullable|string|max:50',
            'is_public'        => 'boolean',
        ]);

        Kudo::create([
            'from_employee_id' => $request->from_employee_id,
            'to_employee_id'   => $request->to_employee_id,
            'message'          => $request->message,
            'badge'            => $request->badge ?: null,
            'is_public'        => $request->boolean('is_public', true),
            'reactions_count'  => 0,
        ]);

        return back()->with('success', 'Kudos sent! 🎉');
    }

    public function destroyKudo(string $tenant, Kudo $kudo)
    {
        $kudo->delete();
        return back()->with('success', 'Kudos removed.');
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $reviews = PerformanceReview::with(['employee.department', 'reviewer', 'cycle'])->latest()->get();

        $columns = ['Employee', 'Department', 'Reviewer', 'Cycle', 'Rating', 'Status', 'Review Date'];

        $rows = $reviews->map(fn($r) => [
            $r->employee->full_name ?? '-',
            $r->employee->department?->name ?? '-',
            $r->reviewer->name ?? '-',
            $r->cycle?->name ?? '-',
            $r->overall_rating ?? '-',
            ucfirst(str_replace('_', ' ', $r->status)),
            $r->review_date?->format('Y-m-d') ?? $r->created_at->format('Y-m-d'),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Performance Reviews Report', $columns, $rows, 'performance-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'performance-'.now()->format('Y-m-d').'.xlsx');
    }
}