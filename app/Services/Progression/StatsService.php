<?php

namespace App\Services\Progression;

use App\Models\CertificationSubmission;
use App\Models\DailyActivity;
use App\Models\InterviewSession;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\Cache;

class StatsService
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function getDeveloperStats(User $user): array
    {
        $cacheKey = "stats:developer:{$user->id}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            $profile = $user->developerProfile;

            // Stats interviews
            $interviewStats = InterviewSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->selectRaw('
                    COUNT(*) as total,
                    AVG(score_total) as avg_score,
                    MAX(score_total) as best_score,
                    SUM(xp_earned) as total_xp
                ')
                ->first();

            // Stats certifications
            $certStats = CertificationSubmission::where('user_id', $user->id)
                ->selectRaw('
                    COUNT(*) as total_attempts,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as passed,
                    AVG(CASE WHEN status = ? THEN score_total END) as avg_score
                ', ['passed', 'passed'])
                ->first();

            // XP cette semaine
            $weeklyXp = XpTransaction::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfWeek())
                ->sum('amount');

            // XP ce mois
            $monthlyXp = XpTransaction::where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount');

            // Niveau suivant
            $levels = [
                1 => 0,
                2 => 100,
                3 => 250,
                4 => 500,
                5 => 1000,
                6 => 2000,
                7 => 3500,
                8 => 5000,
                9 => 7500,
                10 => 10000,
            ];

            $nextLevel    = min($user->level + 1, 10);
            $xpForNext    = $levels[$nextLevel] ?? 10000;
            $xpForCurrent = $levels[$user->level] ?? 0;
            $xpProgress   = $user->xp_total - $xpForCurrent;
            $xpNeeded     = $xpForNext - $xpForCurrent;
            $progressPct  = $xpNeeded > 0 ? round(($xpProgress / $xpNeeded) * 100) : 100;

            // Rank
            $globalRank = $this->leaderboard->getUserRank($user, 'global');
            $weeklyRank = $this->leaderboard->getUserRank($user, 'weekly');

            return [
                'user' => [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'username'       => $user->username,
                    'avatar_url'     => $user->avatar_url,
                    'level'          => $user->level,
                    'xp_total'       => $user->xp_total,
                    'current_streak' => $user->current_streak,
                    'longest_streak' => $user->longest_streak,
                    'plan'           => $user->plan,
                ],
                'level_progress' => [
                    'current_level'  => $user->level,
                    'next_level'     => $nextLevel,
                    'xp_current'     => $user->xp_total,
                    'xp_for_next'    => $xpForNext,
                    'progress_pct'   => $progressPct,
                    'xp_remaining'   => max(0, $xpForNext - $user->xp_total),
                ],
                'xp_summary' => [
                    'total'   => $user->xp_total,
                    'weekly'  => $weeklyXp,
                    'monthly' => $monthlyXp,
                ],
                'interview_stats' => [
                    'total_sessions' => (int) ($interviewStats->total ?? 0),
                    'avg_score'      => round($interviewStats->avg_score ?? 0, 1),
                    'best_score'     => round($interviewStats->best_score ?? 0, 1),
                    'overall_score'  => $profile?->interview_score ?? 0,
                ],
                'certification_stats' => [
                    'total_attempts' => (int) ($certStats->total_attempts ?? 0),
                    'passed'         => (int) ($certStats->passed ?? 0),
                    'avg_score'      => round($certStats->avg_score ?? 0, 1),
                    'badges_count'   => $user->badges()->where('badge_type', 'certification')->count(),
                ],
                'ranking' => [
                    'global_rank' => $globalRank['rank'],
                    'weekly_rank' => $weeklyRank['rank'],
                ],
                'profile_score' => [
                    'overall'   => $profile?->overall_score ?? 0,
                    'interview' => $profile?->interview_score ?? 0,
                    'cert'      => $profile?->cert_score ?? 0,
                ],
            ];
        });
    }

    public function getActivityHeatmap(User $user, int $days = 365): array
    {
        $from = now()->subDays($days)->toDateString();

        $activities = DailyActivity::where('user_id', $user->id)
            ->where('date', '>=', $from)
            ->orderBy('date')
            ->get(['date', 'xp_earned', 'sessions_count', 'time_spent_min'])
            ->keyBy('date');

        // Générer tous les jours avec valeur 0 si pas d'activité
        $heatmap = [];
        $current = now()->subDays($days);
        $end     = now();

        while ($current <= $end) {
            $dateStr    = $current->toDateString();
            $activity   = $activities->get($dateStr);

            $heatmap[] = [
                'date'          => $dateStr,
                'xp_earned'     => $activity?->xp_earned ?? 0,
                'sessions_count' => $activity?->sessions_count ?? 0,
                'time_spent_min' => $activity?->time_spent_min ?? 0,
                'level'         => $this->getActivityLevel($activity?->xp_earned ?? 0),
            ];

            $current->addDay();
        }

        return $heatmap;
    }

    public function getXpHistory(User $user, int $limit = 20): array
    {
        return XpTransaction::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($t) => [
                'amount'         => $t->amount,
                'reason'         => $t->reason,
                'reference_type' => $t->reference_type,
                'earned_at'      => $t->created_at?->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    private function getActivityLevel(int $xp): int
    {
        return match (true) {
            $xp === 0   => 0,
            $xp < 20    => 1,
            $xp < 50    => 2,
            $xp < 100   => 3,
            default     => 4,
        };
    }

    public function invalidateCache(User $user): void
    {
        Cache::forget("stats:developer:{$user->id}");
    }
}
