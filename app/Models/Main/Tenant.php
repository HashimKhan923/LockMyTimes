<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'main';
    protected $table = 'tenants';

    protected $fillable = [
        'company_name', 'slug', 'subdomain', 'logo', 'favicon', 'industry', 'company_size', 'website',
        'contact_name', 'contact_email', 'contact_phone',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'timezone',
        'database_name', 'database_provisioned',
        'stripe_customer_id',
        'primary_color', 'secondary_color',
        'status', 'trial_ends_at', 'suspended_at', 'suspension_reason',
        'is_onboarded', 'onboarding_steps',
        'employees_count', 'admins_count', 'storage_used_bytes',
        'settings', 'feature_flags', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'database_provisioned' => 'boolean',
            'is_onboarded'         => 'boolean',
            'trial_ends_at'        => 'datetime',
            'suspended_at'         => 'datetime',
            'last_activity_at'     => 'datetime',
            'onboarding_steps'     => 'array',
            'settings'             => 'array',
            'feature_flags'        => 'array',
        ];
    }

    /* =====================================================
     | Relationships
     |====================================================*/

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['trialing', 'active'])
            ->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /* =====================================================
     | Scopes
     |====================================================*/

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['trial', 'active']);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /* =====================================================
     | Helpers
     |====================================================*/

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']);
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo
            ? asset('storage/'.$this->logo)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->company_name).'&background=6C7DF7&color=fff';
    }

    public function getStorageUsedMbAttribute(): float
    {
        return round($this->storage_used_bytes / 1024 / 1024, 2);
    }

    public function getStorageUsedGbAttribute(): float
    {
        return round($this->storage_used_bytes / 1024 / 1024 / 1024, 2);
    }

    /**
     * Get the URL for this tenant's admin portal.
     */
    public function adminUrl(): string
    {
        return url('/t/'.$this->slug.'/admin');
    }

    /**
     * Get the URL for this tenant's employee portal.
     */
    public function employeeUrl(): string
    {
        return url('/t/'.$this->slug.'/portal');
    }

    /**
     * Generate a unique database name for this tenant.
     */
    public static function generateDatabaseName(string $slug): string
    {
        return config('tenancy.db_prefix', 'lockmytimes_tenant_').preg_replace('/[^a-z0-9_]/', '', strtolower($slug));
    }

    /* =====================================================
     | Plan Limit Helpers
     |====================================================*/

    public function getPlanLimit(string $key, mixed $default = null): mixed
    {
        return $this->settings['plan_limits'][$key] ?? $default;
    }

    public function hasModule(string $module): bool
    {
        $flags = $this->feature_flags ?? [];
        return (bool) ($flags["has_{$module}"] ?? true); // default true if no plan applied yet
    }

    public function canAddEmployee(): bool
    {
        $max = $this->getPlanLimit('max_employees');
        if ($max === null) return true;
        return $this->employees_count < $max;
    }

    public function canAddAdmin(): bool
    {
        $max = $this->getPlanLimit('max_admins');
        if ($max === null) return true;
        return $this->admins_count < $max;
    }

    public function canUploadBytes(int $bytes): bool
    {
        $maxGb = $this->getPlanLimit('max_storage_gb');
        if ($maxGb === null) return true;
        $maxBytes = $maxGb * 1024 * 1024 * 1024;
        return ($this->storage_used_bytes + $bytes) <= $maxBytes;
    }

    public function incrementStorage(int $bytes): void
    {
        $this->increment('storage_used_bytes', $bytes);
    }

    public function decrementStorage(int $bytes): void
    {
        // Use GREATEST to prevent storage_used_bytes going below 0
        $this->newQuery()->where('id', $this->id)->update([
            'storage_used_bytes' => \Illuminate\Support\Facades\DB::raw("GREATEST(0, storage_used_bytes - {$bytes})"),
        ]);
    }

    /**
     * Sync a subscription plan's feature flags and limits onto this tenant.
     * Call this whenever a tenant subscribes to or changes a plan.
     */
    public function applyPlan(SubscriptionPlan $plan): void
    {
        $this->update([
            'feature_flags' => [
                'has_attendance'       => $plan->has_attendance,
                'has_payroll'          => $plan->has_payroll,
                'has_performance'      => $plan->has_performance,
                'has_assets'           => $plan->has_assets,
                'has_training'         => $plan->has_training,
                'has_recruitment'      => $plan->has_recruitment,
                'has_documents'        => $plan->has_documents,
                'has_expenses'         => $plan->has_expenses,
                'has_api_access'       => $plan->has_api_access,
                'has_white_label'      => $plan->has_white_label,
                'has_priority_support' => $plan->has_priority_support,
            ],
            'settings' => array_merge($this->settings ?? [], [
                'plan_limits' => [
                    'max_employees'  => $plan->max_employees,
                    'max_storage_gb' => $plan->max_storage_gb,
                    'max_admins'     => $plan->max_admins,
                ],
                'plan_slug' => $plan->slug,
                'plan_name' => $plan->name,
            ]),
        ]);
    }
}