<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_code',
        'first_name', 'middle_name', 'last_name', 'preferred_name',
        'email', 'personal_email', 'phone', 'mobile',
        'date_of_birth', 'gender', 'marital_status', 'nationality', 'avatar',
        'ssn_encrypted', 'employee_type', 'i9_verified', 'i9_verified_date',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
        'department_id', 'position_id', 'location_id', 'manager_id',
        'employment_status', 'employment_type',
        'hire_date', 'probation_end_date', 'confirmation_date',
        'termination_date', 'termination_reason',
        'base_salary', 'salary_frequency', 'hourly_rate', 'currency', 'is_exempt',
        'bank_name', 'bank_account_number_encrypted', 'bank_routing_number_encrypted', 'bank_account_type',
        'federal_filing_status', 'federal_allowances', 'additional_federal_withholding',
        'state_filing_status', 'state_allowances',
        'custom_fields', 'privacy_settings', 'notes',
    ];

    protected $hidden = [
        'ssn_encrypted',
        'bank_account_number_encrypted',
        'bank_routing_number_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'                  => 'date',
            'hire_date'                      => 'date',
            'probation_end_date'             => 'date',
            'confirmation_date'              => 'date',
            'termination_date'               => 'date',
            'i9_verified_date'               => 'date',
            'i9_verified'                    => 'boolean',
            'is_exempt'                      => 'boolean',
            'base_salary'                    => 'decimal:2',
            'hourly_rate'                    => 'decimal:2',
            'additional_federal_withholding' => 'decimal:2',
            'custom_fields'                  => 'array',
            'privacy_settings'               => 'array',
        ];
    }

    /* ============ Privacy helpers ============ */
    public static function defaultPrivacySettings(): array
    {
        return [
            'show_in_directory'    => true,
            'show_phone'           => false,
            'show_email'           => false,
            'show_department'      => true,
            'show_position'        => true,
        ];
    }

    public function getPrivacySetting(string $key): bool
    {
        $settings = $this->privacy_settings ?? [];
        return (bool) ($settings[$key] ?? self::defaultPrivacySettings()[$key] ?? false);
    }

    /* ============ Relationships ============ */
    public function user(): BelongsTo            { return $this->belongsTo(User::class); }
    public function department(): BelongsTo      { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo        { return $this->belongsTo(Position::class); }
    public function location(): BelongsTo        { return $this->belongsTo(Location::class); }
    public function manager(): BelongsTo         { return $this->belongsTo(self::class, 'manager_id'); }
    public function subordinates(): HasMany      { return $this->hasMany(self::class, 'manager_id'); }
    public function emergencyContacts(): HasMany { return $this->hasMany(EmergencyContact::class); }
    public function documents(): HasMany         { return $this->hasMany(EmployeeDocument::class); }
    public function attendances(): HasMany       { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany     { return $this->hasMany(LeaveRequest::class); }
    public function leaveBalances(): HasMany     { return $this->hasMany(LeaveBalance::class); }
    public function shiftAssignments(): HasMany  { return $this->hasMany(ShiftAssignment::class); }
    public function payslips(): HasMany          { return $this->hasMany(Payslip::class); }
    public function goals(): HasMany             { return $this->hasMany(Goal::class); }
    public function performanceReviews(): HasMany{ return $this->hasMany(PerformanceReview::class); }
    public function assignedAssets(): HasMany    { return $this->hasMany(AssetAssignment::class); }
    public function trainingEnrollments(): HasMany { return $this->hasMany(TrainingEnrollment::class); }
    public function certifications(): HasMany    { return $this->hasMany(Certification::class); }
    public function expenses(): HasMany          { return $this->hasMany(Expense::class); }
    public function loans(): HasMany             { return $this->hasMany(Loan::class); }
    public function salaryAdvances(): HasMany    { return $this->hasMany(SalaryAdvance::class); }
    public function projectMembers(): HasMany    { return $this->hasMany(ProjectMember::class); }
    public function taskAssignments(): HasMany   { return $this->hasMany(TaskAssignee::class); }

    /* ============ Accessors / Helpers ============ */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->preferred_name ?: $this->first_name;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->full_name).'&background=6C7DF7&color=fff';
    }

    public function getServiceMonthsAttribute(): int
    {
        return $this->hire_date ? $this->hire_date->diffInMonths(now()) : 0;
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    // Salary
public function salaryComponents()
{
    return $this->hasMany(EmployeeSalaryComponent::class)->with('component');
}


}