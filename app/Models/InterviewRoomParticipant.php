<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewRoomParticipant extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function room()
    {
        return $this->belongsTo(InterviewRoom::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function turns()
    {
        return $this->hasMany(InterviewTurn::class, 'participant_id');
    }

    public function isAi(): bool
    {
        return $this->role === 'ai';
    }
}
