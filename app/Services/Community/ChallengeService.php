<?php

namespace App\Services\Community;

use App\Models\ChallengeSubmission;
use App\Models\InterviewQuestion;
use App\Models\User;
use App\Models\WeeklyChallenge;
use App\Services\Interview\Judge0Service;
use Illuminate\Support\Facades\DB;

class ChallengeService
{
    public function __construct(private Judge0Service $judge0) {}

    public function getCurrentChallenge(): ?WeeklyChallenge
    {
        $weekStart = now()->startOfWeek()->toDateString();

        $challenge = WeeklyChallenge::where('week_start', $weekStart)
            ->with('question')
            ->first();

        // Créer automatiquement si inexistant
        if (! $challenge) {
            $challenge = $this->createWeeklyChallenge($weekStart);
        }

        return $challenge;
    }

    public function submit(User $user, WeeklyChallenge $challenge, array $data): ChallengeSubmission
    {
        // Vérifier si déjà soumis
        $existing = ChallengeSubmission::where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            throw new \Exception('Vous avez déjà soumis une solution pour ce challenge.', 409);
        }

        // Exécuter le code
        $execution = $this->judge0->execute(
            code: $data['code_submitted'],
            language: $data['language'],
        );

        $score = $execution['success'] ? 100 : 0;

        $submission = ChallengeSubmission::create([
            'challenge_id'   => $challenge->id,
            'user_id'        => $user->id,
            'code_submitted' => $data['code_submitted'],
            'language'       => $data['language'],
            'execution_result' => $execution,
            'score'          => $score,
        ]);

        // Mettre à jour le compteur
        $challenge->increment('participants_count');

        // XP selon le résultat
        if ($execution['success']) {
            $user->addXp(30, 'challenge_completed', 'challenge_submission', $submission->id);
        } else {
            $user->addXp(5, 'challenge_attempted', 'challenge_submission', $submission->id);
        }

        // Calculer le rang
        $this->updateRankings($challenge);

        return $submission->fresh();
    }

    public function getLeaderboard(WeeklyChallenge $challenge): array
    {
        return ChallengeSubmission::where('challenge_id', $challenge->id)
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->with('user:id,name,username,avatar_url,level')
            ->limit(50)
            ->get()
            ->map(fn($s, $i) => [
                'rank'     => $i + 1,
                'score'    => $s->score,
                'language' => $s->language,
                'user'     => [
                    'id'         => $s->user->id,
                    'name'       => $s->user->name,
                    'username'   => $s->user->username,
                    'avatar_url' => $s->user->avatar_url,
                    'level'      => $s->user->level,
                ],
            ])
            ->toArray();
    }

    private function createWeeklyChallenge(string $weekStart): WeeklyChallenge
    {
        // Prendre une question aléatoire de niveau medium
        $question = InterviewQuestion::where('difficulty', 'medium')
            ->where('type', 'algo')
            ->inRandomOrder()
            ->first();

        if (! $question) {
            // Créer une question par défaut
            $question = InterviewQuestion::create([
                'category'   => 'algo',
                'type'       => 'algo',
                'difficulty' => 'medium',
                'title'      => 'Challenge de la semaine',
                'description' => 'Écrivez une fonction qui retourne le n-ième nombre de Fibonacci.',
                'examples'   => [
                    ['input' => 'n = 5', 'output' => '5', 'explanation' => 'F(5) = 0,1,1,2,3,5'],
                ],
                'hints'     => ['Pensez à la récursion', 'Ou à la mémoisation'],
            ]);
        }

        return WeeklyChallenge::create([
            'question_id' => $question->id,
            'week_start'  => $weekStart,
        ]);
    }

    private function updateRankings(WeeklyChallenge $challenge): void
    {
        $submissions = ChallengeSubmission::where('challenge_id', $challenge->id)
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->get();

        foreach ($submissions as $index => $submission) {
            $submission->update(['rank' => $index + 1]);
        }
    }
}
