<?php

namespace App\Services\Progression;

use App\Models\DailyActivity;
use App\Models\User;

class StreakService
{
    public function updateStreak(User $user): array
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Enregistrer l'activité du jour
        $activity = DailyActivity::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['xp_earned' => 0, 'sessions_count' => 0, 'time_spent_min' => 0]
        );

        $activity->increment('sessions_count');

        // Calculer le streak
        $wasActiveYesterday = DailyActivity::where('user_id', $user->id)
            ->where('date', $yesterday)
            ->where('sessions_count', '>', 0)
            ->exists();

        $isFirstActivityToday = $activity->sessions_count === 1;

        if ($isFirstActivityToday) {
            if ($wasActiveYesterday) {
                // Continuer le streak
                $newStreak = $user->current_streak + 1;
            } else {
                // Streak brisé ou premier jour
                $newStreak = 1;
            }

            $longestStreak = max($user->longest_streak, $newStreak);

            $user->update([
                'current_streak'  => $newStreak,
                'longest_streak'  => $longestStreak,
                'last_active_date' => $today,
            ]);

            // Bonus XP pour milestone de streak
            $bonusXp = $this->getStreakBonus($newStreak);
            if ($bonusXp > 0) {
                $user->addXp($bonusXp, 'streak_bonus');
            }
        }

        return [
            'current_streak' => $user->fresh()->current_streak,
            'longest_streak' => $user->fresh()->longest_streak,
            'is_new_day'     => $isFirstActivityToday,
        ];
    }

    private function getStreakBonus(int $streak): int
    {
        return match (true) {
            $streak === 7   => 50,
            $streak === 30  => 200,
            $streak === 100 => 500,
            $streak % 7 === 0 => 25,
            default         => 0,
        };
    }
}
