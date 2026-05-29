<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LeaderboardWeekly extends Model
{
    use HasUuids;

    protected $table = 'leaderboard_weekly'; // ← forcer le bon nom de table

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'week_start',
        'xp_earned',
        'rank',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
