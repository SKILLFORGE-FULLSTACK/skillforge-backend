<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserSkill extends Model
{
    use HasUuids;

    const CREATED_AT = null; // ← pas de created_at dans la table

    protected $fillable = [
        'user_id',
        'skill_name',
        'level',
        'is_certified',
        'score',
        'sessions_count',
    ];

    protected function casts(): array
    {
        return [
            'is_certified' => 'boolean',
            'score'        => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
