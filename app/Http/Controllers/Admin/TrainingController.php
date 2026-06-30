<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Certification;
use App\Models\Tenant\Employee;
use App\Models\Tenant\Training;
use App\Models\Tenant\TrainingEnrollment;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $tab      = $request->get('tab', 'courses');
        $category = $request->get('category');
        $search   = $request->get('search');

        $query = Training::withCount('enrollments')->where('is_active', true)->latest();

        if ($category) $query->where('category', $category);
        if ($search)   $query->where('title', 'like', "%$search%");

        $trainings    = $query->paginate(12)->withQueryString();
        $enrollments  = TrainingEnrollment::with(['employee.department', 'training'])
            ->latest()->paginate(20, ['*'], 'enroll_page')->withQueryString();
        $certifications = Certification::with('employee')
            ->latest()->paginate(20, ['*'], 'cert_page')->withQueryString();
        $employees = Employee::active()->orderBy('first_name')->get();

        $stats = [
            'total_courses'    => Training::where('is_active', true)->count(),
            'enrollments'      => TrainingEnrollment::count(),
            'completed'        => TrainingEnrollment::where('status', 'completed')->count(),
            'certifications'   => Certification::count(),
            'expiring_soon'    => Certification::whereNotNull('expiry_date')
                                    ->where('expiry_date', '>', now())
                                    ->where('expiry_date', '<=', now()->addDays(30))
                                    ->count(),
        ];

        $categories = [
            'onboarding'  => 'Onboarding',
            'compliance'  => 'Compliance',
            'technical'   => 'Technical',
            'soft_skills' => 'Soft Skills',
            'leadership'  => 'Leadership',
            'safety'      => 'Safety',
            'other'       => 'Other',
        ];

        return view('admin.training.index', compact(
            'tab', 'trainings', 'enrollments', 'certifications',
            'employees', 'stats', 'categories', 'tenant'
        ));
    }

    /* ================================================================
     | SHOW — course detail
     |================================================================*/
    public function show(string $tenant, Training $training)
    {
        $training->load('enrollments.employee.department');
        $avgRating  = $training->enrollments->whereNotNull('rating')->avg('rating');
        $avgScore   = $training->enrollments->whereNotNull('score')->avg('score');
        $employees  = Employee::active()->orderBy('first_name')->get();

        return view('admin.training.show', compact(
            'training', 'avgRating', 'avgScore', 'employees', 'tenant'
        ));
    }

    /* ================================================================
     | STORE — create training
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'title'                   => 'required|string|max:200',
            'type'                    => 'required|in:in_person,online,hybrid,self_paced',
            'category'                => 'required|in:onboarding,compliance,technical,soft_skills,leadership,safety,other',
            'description'             => 'nullable|string',
            'provider'                => 'nullable|string|max:200',
            'instructor'              => 'nullable|string|max:200',
            'start_date'              => 'nullable|date',
            'end_date'                => 'nullable|date',
            'start_time'              => 'nullable|date_format:H:i',
            'end_time'                => 'nullable|date_format:H:i',
            'location'                => 'nullable|string|max:200',
            'online_url'              => 'nullable|url',
            'cost'                    => 'nullable|numeric|min:0',
            'duration_hours'          => 'nullable|integer|min:0',
            'max_participants'        => 'nullable|integer|min:1',
            'is_mandatory'            => 'boolean',
            'issues_certificate'      => 'boolean',
            'certificate_valid_months'=> 'nullable|integer|min:1',
            'thumbnail'               => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('training/thumbnails', 'public');
        }

        $data['is_active'] = true;
        $data['cost']      = $data['cost'] ?? 0;
        $data['duration_hours'] = $data['duration_hours'] ?? 0;

        $training = Training::create($data);

        // Auto-enroll all employees if mandatory
        if ($data['is_mandatory'] ?? false) {
            Employee::active()->each(function ($emp) use ($training) {
                TrainingEnrollment::firstOrCreate(
                    ['training_id' => $training->id, 'employee_id' => $emp->id],
                    ['enrolled_at' => now(), 'status' => 'enrolled', 'progress' => 0]
                );
            });
        }

        return back()->with('success', "Training \"{$training->title}\" created.");
    }

    /* ================================================================
     | UPDATE
     |================================================================*/
    public function update(string $tenant, Request $request, Training $training)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:200',
            'type'           => 'required|in:in_person,online,hybrid,self_paced',
            'category'       => 'required|in:onboarding,compliance,technical,soft_skills,leadership,safety,other',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'instructor'     => 'nullable|string|max:200',
            'cost'           => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|integer|min:0',
            'is_mandatory'   => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $training->update($data);
        return back()->with('success', 'Training updated.');
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Training $training)
    {
        $training->delete();
        return redirect()
            ->route('admin.training.index', $tenant)
            ->with('success', 'Training deleted.');
    }

    /* ================================================================
     | ENROLL EMPLOYEES
     |================================================================*/
    public function enroll(string $tenant, Request $request, Training $training)
    {
        $data = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $enrolled = 0;
        foreach ($data['employee_ids'] as $empId) {
            $created = TrainingEnrollment::firstOrCreate(
                ['training_id' => $training->id, 'employee_id' => $empId],
                ['enrolled_at' => now(), 'status' => 'enrolled', 'progress' => 0]
            );
            if ($created->wasRecentlyCreated) $enrolled++;
        }

        return back()->with('success', "{$enrolled} employee(s) enrolled.");
    }

    /* ================================================================
     | UPDATE ENROLLMENT — progress / score / status
     |================================================================*/
    public function updateEnrollment(string $tenant, Request $request, TrainingEnrollment $enrollment)
    {
        $data = $request->validate([
            'status'   => 'required|in:enrolled,in_progress,completed,failed,dropped',
            'progress' => 'required|integer|min:0|max:100',
            'score'    => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'rating'   => 'nullable|integer|min:1|max:5',
        ]);

        if ($data['status'] === 'completed' && ! $enrollment->completed_at) {
            $data['completed_at'] = now();
            $data['progress']     = 100;

            // Issue certificate if applicable
            if ($enrollment->training->issues_certificate) {
                $months = $enrollment->training->certificate_valid_months;
                $data['certificate_expiry'] = $months
                    ? now()->addMonths($months)->toDateString()
                    : null;
            }
        }

        $enrollment->update($data);
        return back()->with('success', 'Enrollment updated.');
    }

    /* ================================================================
     | CERTIFICATIONS
     |================================================================*/
    public function storeCertification(string $tenant, Request $request)
    {
        $data = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'name'           => 'required|string|max:200',
            'issuer'         => 'required|string|max:200',
            'credential_id'  => 'nullable|string|max:100',
            'credential_url' => 'nullable|url',
            'issue_date'     => 'required|date',
            'expiry_date'    => 'nullable|date|after:issue_date',
            'is_verified'    => 'boolean',
            'certificate_file'=> 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('certificate_file')) {
            $data['certificate_file'] = $request->file('certificate_file')
                ->store('certifications', 'public');
        }

        Certification::create($data);
        return back()->with('success', 'Certification added.');
    }

    public function destroyCertification(string $tenant, Certification $certification)
    {
        if ($certification->certificate_file) {
            Storage::disk('public')->delete($certification->certificate_file);
        }
        $certification->delete();
        return back()->with('success', 'Certification removed.');
    }

    /* ================================================================
     | EXPORT
     |================================================================*/
    public function export(string $tenant, Request $request, ExportService $exporter)
    {
        $format = $request->get('format', 'excel');

        $trainings = Training::withCount('enrollments')->latest()->get();

        $columns = ['Title', 'Type', 'Instructor', 'Start Date', 'End Date', 'Duration (hrs)', 'Enrolled', 'Status'];

        $rows = $trainings->map(fn($t) => [
            $t->title,
            ucfirst(str_replace('_', ' ', $t->type ?? 'internal')),
            $t->instructor ?? '-',
            $t->start_date?->format('Y-m-d') ?? '-',
            $t->end_date?->format('Y-m-d') ?? '-',
            $t->duration_hours ?? '-',
            $t->enrollments_count,
            ucfirst($t->status ?? 'scheduled'),
        ]);

        if ($format === 'pdf') {
            return $exporter->pdf('Training Report', $columns, $rows, 'training-'.now()->format('Y-m-d').'.pdf', 'landscape');
        }

        return $exporter->excel($columns, $rows, 'training-'.now()->format('Y-m-d').'.xlsx');
    }
}