<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'category',
        'level',
        'description',
        'skills_covered',
        'project_brief',
        'evaluation_criteria',
        'passing_score',
        'duration_days',
        'validity_months',
        'price_credits',
        'attempts_allowed',
        'badge_svg_url',
        'badge_color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'skills_covered'      => 'array',
            'evaluation_criteria' => 'array',
            'is_active'           => 'boolean',
        ];
    }

    public function submissions()
    {
        return $this->hasMany(CertificationSubmission::class);
    }

    public function badges()
    {
        return $this->hasMany(UserBadge::class);
    }
}
