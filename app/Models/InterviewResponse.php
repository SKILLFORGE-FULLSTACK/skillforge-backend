<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewResponse extends Model
{
    use HasUuids;

    protected $fillable = [
        'session_id',
        'question_id',
        'code_submitted',
        'language',
        'text_answer',
        'execution_result',
        'score',
        'ai_analysis',
        'hints_used',
        'time_taken_sec',
    ];

    protected function casts(): array
    {
        return [
            'execution_result' => 'array',
            'ai_analysis'      => 'array',
            'score'            => 'decimal:2',
        ];
    }

    public function session()
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function question()
    {
        return $this->belongsTo(InterviewQuestion::class, 'question_id');
    }
}
