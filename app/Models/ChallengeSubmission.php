<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChallengeSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'challenge_id',
        'user_id',
        'code_submitted',
        'language',
        'execution_result',
        'score',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'execution_result' => 'array',
            'score'            => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(WeeklyChallenge::class, 'challenge_id');
    }
}
