<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model implements Translatable
{
    use HasTranslations, HasUuids;

    protected $fillable = [
        'slug',
        'title',
        'title_en',
        'category',
        'level',
        'description',
        'description_en',
        'skills_covered',
        'project_brief',
        'project_brief_en',
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

    public static function translatableFields(): array
    {
        return [
            'title' => 'title_en',
            'description' => 'description_en',
            'project_brief' => 'project_brief_en',
        ];
    }

    protected function casts(): array
    {
        return [
            'skills_covered' => 'array',
            'evaluation_criteria' => 'array',
            'is_active' => 'boolean',
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
