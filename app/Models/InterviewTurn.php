<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewTurn extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id',
        'participant_id',
        'order',
        'transcript',
        'audio_url',
        'duration_sec',
    ];

    public function room()
    {
        return $this->belongsTo(InterviewRoom::class, 'room_id');
    }

    public function participant()
    {
        return $this->belongsTo(InterviewRoomParticipant::class, 'participant_id');
    }
}
