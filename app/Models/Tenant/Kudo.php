<?php

namespace App\Models\Tenant;

class Kudo extends TenantModel
{
    protected $fillable = [
        'from_employee_id', 'to_employee_id',
        'badge', 'message',
        'is_public', 'reactions_count',
    ];

    protected $casts = [
        'is_public'      => 'boolean',
        'reactions_count'=> 'integer',
    ];

    public function fromEmployee()
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    public function toEmployee()
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }

    // Badge options
    public static function badges(): array
    {
        return [
            'star'         => '⭐ Star Performer',
            'teamwork'     => '🤝 Team Player',
            'innovation'   => '💡 Innovator',
            'leadership'   => '🚀 Leader',
            'customer'     => '❤️ Customer Hero',
            'above_beyond' => '🏆 Above & Beyond',
            'mentor'       => '📚 Mentor',
            'problem_solver'=> '🔧 Problem Solver',
        ];
    }
}