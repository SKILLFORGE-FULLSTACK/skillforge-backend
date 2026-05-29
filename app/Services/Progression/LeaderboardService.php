<?php

namespace App\Services\Progression;

use App\Models\LeaderboardWeekly;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    // TTL du cache Redis en secondes
    private int $cacheTtl = 300; // 5 minutes

    public function getGlobalLeaderboard(string $category = 'all', int $limit = 50): array
    {
        $cacheKey = "leaderboard:global:{$category}:{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category, $limit) {
            return User::query()
                ->where('role', 'developer')
                ->where('is_public', true)
                ->when($category !== 'all', function ($q) use ($category) {
                    $q->whereHas(
                        'skills',
                        fn($s) =>
                        $s->where('skill_name', 'ilike', "%{$category}%")
                    );
                })
                ->orderByDesc('xp_total')
                ->limit($limit)
                ->get(['id', 'name', 'username', 'avatar_url', 'xp_total', 'level', 'current_streak'])
                ->map(fn($user, $index) => [
                    'rank'           => $index + 1,
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'username'       => $user->username,
                    'avatar_url'     => $user->avatar_url,
                    'xp_total'       => $user->xp_total,
                    'level'          => $user->level,
                    'current_streak' => $user->current_streak,
                ])
                ->toArray();
        });
    }

    public function getWeeklyLeaderboard(string $category = 'all', int $limit = 50): array
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $cacheKey  = "leaderboard:weekly:{$category}:{$weekStart}:{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($category, $weekStart, $limit) {
            return LeaderboardWeekly::where('week_start', $weekStart)
                ->where('category', $category)
                ->orderByDesc('xp_earned')
                ->limit($limit)
                ->with('user:id,name,username,avatar_url,level')
                ->get()
                ->map(fn($entry, $index) => [
                    'rank'       => $index + 1,
                    'xp_earned'  => $entry->xp_earned,
                    'user'       => [
                        'id'         => $entry->user->id,
                        'name'       => $entry->user->name,
                        'username'   => $entry->user->username,
                        'avatar_url' => $entry->user->avatar_url,
                        'level'      => $entry->user->level,
                    ],
                ])
                ->toArray();
        });
    }

    public function getUserRank(User $user, string $period = 'global'): array
    {
        if ($period === 'weekly') {
            $weekStart = now()->startOfWeek()->toDateString();
            $rank = LeaderboardWeekly::where('week_start', $weekStart)
                ->where('category', 'all')
                ->where('xp_earned', '>', function ($query) use ($user, $weekStart) {
                    $query->select('xp_earned')
                        ->from('leaderboard_weekly')
                        ->where('user_id', $user->id)
                        ->where('week_start', $weekStart)
                        ->where('category', 'all');
                })
                ->count() + 1;
        } else {
            $rank = User::where('role', 'developer')
                ->where('xp_total', '>', $user->xp_total)
                ->count() + 1;
        }

        return [
            'rank'   => $rank,
            'period' => $period,
        ];
    }

    public function updateWeeklyLeaderboard(User $user, int $xpEarned): void
    {
        $weekStart = now()->startOfWeek()->toDateString();

        $entry = LeaderboardWeekly::where('user_id', $user->id)
            ->where('week_start', $weekStart)
            ->where('category', 'all')
            ->first();

        if ($entry) {
            $entry->increment('xp_earned', $xpEarned);
        } else {
            LeaderboardWeekly::create([
                'user_id'    => $user->id,
                'week_start' => $weekStart,
                'category'   => 'all',
                'xp_earned'  => $xpEarned,
            ]);
        }

        // Invalider le cache
        Cache::forget("leaderboard:weekly:all:{$weekStart}:50");
    }
}
