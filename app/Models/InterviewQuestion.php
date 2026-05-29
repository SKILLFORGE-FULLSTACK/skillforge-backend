<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model
{
    use HasUuids;

    protected $fillable = [
        'category',
        'type',
        'difficulty',
        'title',
        'description',
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
            'examples'     => 'array',
            'hints'        => 'array',
            'tags'         => 'array',
            'companies'    => 'array',
            'stack'        => 'array',
            'is_community' => 'boolean',
            'avg_score'    => 'decimal:2',
            'approved_at'  => 'datetime',
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
