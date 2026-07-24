<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model implements Translatable
{
    use HasTranslations, HasUuids;

    protected $fillable = [
        'recruiter_id',
        'title',
        'title_en',
        'description',
        'description_en',
        'required_certs',
        'required_skills',
        'min_level',
        'contract_type',
        'work_mode',
        'location',
        'salary_min',
        'salary_max',
        'currency',
        'status',
        'views_count',
        'applications_count',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'required_certs' => 'array',
            'required_skills' => 'array',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function translatableFields(): array
    {
        return [
            'title' => 'title_en',
            'description' => 'description_en',
        ];
    }

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function interviewRooms()
    {
        return $this->hasMany(InterviewRoom::class);
    }
}
