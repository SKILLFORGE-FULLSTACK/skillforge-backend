<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'email', 'name', 'username', 'avatar_url', 'password',
        'github_id', 'google_id', 'github_username', 'github_token_enc',
        'role', 'plan', 'plan_expires_at', 'stripe_customer_id',
        'xp_total', 'level', 'current_streak', 'longest_streak',
        'last_active_date', 'timezone', 'locale', 'preferences',
        'is_public', 'is_available',
    ];

    protected $hidden = [
        'password', 'remember_token', 'github_token_enc',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expires_at'   => 'datetime',
            'last_active_date'  => 'date',
            'password'          => 'hashed',
            'preferences'       => 'array',
            'is_public'         => 'boolean',
            'is_available'      => 'boolean',
        ];
    }

    // ─── RELATIONS ──────────────────────────────────────────────

    public function developerProfile()
    {
        return $this->hasOne(DeveloperProfile::class);
    }

    public function recruiterProfile()
    {
        return $this->hasOne(RecruiterProfile::class);
    }

    public function interviewSessions()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function certificationSubmissions()
    {
        return $this->hasMany(CertificationSubmission::class);
    }

    public function badges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function xpTransactions()
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function skills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function dailyActivities()
    {
        return $this->hasMany(DailyActivity::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function forumPosts()
    {
        return $this->hasMany(ForumPost::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class, 'recruiter_id');
    }

    // ─── HELPERS ────────────────────────────────────────────────

    public function isDeveloper(): bool
    {
        return $this->role === 'developer';
    }

    public function isRecruiter(): bool
    {
        return $this->role === 'recruiter';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function addXp(int $amount, string $reason, string $refType = null, string $refId = null): void
    {
        $this->increment('xp_total', $amount);

        XpTransaction::create([
            'user_id'        => $this->id,
            'amount'         => $amount,
            'reason'         => $reason,
            'reference_type' => $refType,
            'reference_id'   => $refId,
        ]);

        $this->updateLevel();

        // Mettre à jour le leaderboard hebdomadaire
        app(\App\Services\Progression\LeaderboardService::class)
            ->updateWeeklyLeaderboard($this, $amount);

        // Invalider le cache des stats
        \Illuminate\Support\Facades\Cache::forget("stats:developer:{$this->id}");
    }

    private function updateLevel(): void
    {
        $levels = [
            1 => 0, 2 => 100, 3 => 250, 4 => 500,
            5 => 1000, 6 => 2000, 7 => 3500, 8 => 5000,
            9 => 7500, 10 => 10000,
        ];

        $newLevel = 1;
        foreach ($levels as $level => $xpRequired) {
            if ($this->xp_total >= $xpRequired) {
                $newLevel = $level;
            }
        }

        if ($newLevel !== $this->level) {
            $this->update(['level' => $newLevel]);
        }
    }
}
