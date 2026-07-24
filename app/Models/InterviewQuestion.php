<?php

namespace App\Models;

use App\Contracts\Translatable;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model implements Translatable
{
    use HasTranslations, HasUuids;

    protected $fillable = [
        'category',
        'type',
        'difficulty',
        'title',
        'title_en',
        'description',
        'description_en',
        'constraints',
        'examples',
        'hints',
        'solution',
        'tags',
        'companies',
        'stack',
        'times_asked',
        'avg_score',
        'is_community',
        'created_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'examples' => 'array',
            'hints' => 'array',
            'tags' => 'array',
            'companies' => 'array',
            'stack' => 'array',
            'is_community' => 'boolean',
            'avg_score' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public static function translatableFields(): array
    {
        return [
            'title' => 'title_en',
            'description' => 'description_en',
        ];
    }

    public function responses()
    {
        return $this->hasMany(InterviewResponse::class, 'question_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
