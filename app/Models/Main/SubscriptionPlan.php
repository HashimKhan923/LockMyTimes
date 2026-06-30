<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $connection = 'main';
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name', 'slug', 'description', 'badge',
        'monthly_price', 'yearly_price', 'currency',
        'stripe_monthly_price_id', 'stripe_yearly_price_id', 'stripe_product_id',
        'max_employees', 'max_storage_gb', 'max_admins',
        'features',
        'has_attendance', 'has_payroll', 'has_performance', 'has_assets',
        'has_training', 'has_recruitment', 'has_documents', 'has_expenses',
        'has_api_access', 'has_white_label', 'has_priority_support',
        'sort_order', 'is_active', 'is_featured', 'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'features'              => 'array',
            'monthly_price'         => 'decimal:2',
            'yearly_price'          => 'decimal:2',
            'is_active'             => 'boolean',
            'is_featured'           => 'boolean',
            'has_attendance'        => 'boolean',
            'has_payroll'           => 'boolean',
            'has_performance'       => 'boolean',
            'has_assets'            => 'boolean',
            'has_training'          => 'boolean',
            'has_recruitment'       => 'boolean',
            'has_documents'         => 'boolean',
            'has_expenses'          => 'boolean',
            'has_api_access'        => 'boolean',
            'has_white_label'       => 'boolean',
            'has_priority_support'  => 'boolean',
        ];
    }

    /* =====================================================
     | Relationships
     |====================================================*/

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /* =====================================================
     | Scopes & Accessors
     |====================================================*/

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('monthly_price');
    }

    public function getYearlySavingsPercentAttribute(): int
    {
        if ($this->monthly_price <= 0) return 0;
        $monthlyAnnual = $this->monthly_price * 12;
        if ($monthlyAnnual <= 0) return 0;
        return (int) round((($monthlyAnnual - $this->yearly_price) / $monthlyAnnual) * 100);
    }
}