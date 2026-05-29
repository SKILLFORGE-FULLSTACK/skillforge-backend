<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeveloperProfile extends Model
{
    use HasUuids;

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'years_experience',
        'current_level',
        'target_level',
        'target_salary_min',
        'target_salary_max',
        'preferred_contract',
        'work_mode',
        'location_country',
        'location_city',
        'github_stats',
        'overall_score',
        'interview_score',
        'cert_score',
        'sessions_count',
        'certifications_count',
        'linkedin_url',
        'portfolio_url',
        'resume_url',
    ];

    protected function casts(): array
    {
        return [
            'github_stats'   => 'array',
            'overall_score'  => 'decimal:2',
            'interview_score' => 'decimal:2',
            'cert_score'     => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
