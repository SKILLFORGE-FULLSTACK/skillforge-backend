<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CertificationSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'certification_id',
        'attempt_number',
        'status',
        'github_repo_url',
        'live_url',
        'notes',
        'submitted_at',
        'deadline_at',
        'score_total',
        'score_breakdown',
        'ai_review',
        'human_review',
        'human_reviewer_id',
        'reviewed_at',
        'xp_earned',
    ];

    protected function casts(): array
    {
        return [
            'score_breakdown' => 'array',
            'score_total'     => 'decimal:2',
            'submitted_at'    => 'datetime',
            'deadline_at'     => 'datetime',
            'reviewed_at'     => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function certification()
    {
        return $this->belongsTo(Certification::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'human_reviewer_id');
    }

    public function badge()
    {
        return $this->hasOne(UserBadge::class, 'submission_id');
    }
}
