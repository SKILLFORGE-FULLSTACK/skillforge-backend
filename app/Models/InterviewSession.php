<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'mode',
        'company_target',
        'difficulty',
        'stack_focus',
        'duration_min',
        'actual_duration_sec',
        'status',
        'score_total',
        'score_breakdown',
        'ai_feedback',
        'questions_count',
        'hints_used',
        'recording_url',
        'xp_earned',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score_breakdown' => 'array',
            'score_total'     => 'decimal:2',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responses()
    {
        return $this->hasMany(InterviewResponse::class, 'session_id');
    }
}
