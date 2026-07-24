<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewRoom extends Model
{
    use HasUuids;

    protected $fillable = [
        'job_posting_id',
        'created_by',
        'mode',
        'ai_participates',
        'livekit_room_name',
        'status',
        'language',
        'score_total',
        'score_breakdown',
        'ai_feedback',
        'xp_earned',
        'recording_url',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_participates' => 'boolean',
            'score_breakdown' => 'array',
            'score_total' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(InterviewRoomParticipant::class, 'room_id');
    }

    public function turns()
    {
        return $this->hasMany(InterviewTurn::class, 'room_id')->orderBy('order');
    }
}
