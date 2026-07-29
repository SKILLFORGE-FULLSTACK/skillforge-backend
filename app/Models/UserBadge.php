<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserBadge extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'certification_id',
        'submission_id',
        'badge_type',
        'verify_token',
        'title',
        'description',
        'score',
        'badge_image_url',
        'certificate_url',
        'issued_at',
        'expires_at',
        'is_public',
        'share_count',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_public' => 'boolean',
            'score' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($badge) {
            if (empty($badge->verify_token)) {
                $badge->verify_token = Str::random(32);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function certification()
    {
        return $this->belongsTo(Certification::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
