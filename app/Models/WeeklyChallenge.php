<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WeeklyChallenge extends Model
{
    use HasUuids;

    protected $fillable = [
        'question_id', 'week_start', 'participants_count',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
        ];
    }

    public function question()
    {
        return $this->belongsTo(InterviewQuestion::class, 'question_id');
    }

    public function submissions()
    {
        return $this->hasMany(ChallengeSubmission::class, 'challenge_id');
    }
}
