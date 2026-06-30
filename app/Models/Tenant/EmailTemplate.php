<?php

namespace App\Models\Tenant;

class EmailTemplate extends TenantModel
{
    protected $table = 'email_templates';

    protected $fillable = [
        'name', 'slug', 'subject', 'body',
        'category', 'variables', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render the subject and body with given variable replacements.
     *
     * @param array $vars  e.g. ['employee_name' => 'John', 'date' => '2025-06-01']
     */
    public function render(array $vars): array
    {
        $subject = $this->subject;
        $body    = $this->body;

        foreach ($vars as $key => $value) {
            $subject = str_replace("{{{$key}}}", $value, $subject);
            $body    = str_replace("{{{$key}}}", $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}