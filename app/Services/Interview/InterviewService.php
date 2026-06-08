<?php

namespace App\Services\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewResponse;
use App\Models\InterviewSession;
use App\Models\User;

class InterviewService
{
    public function __construct(
        private GroqService  $groq,
        private Judge0Service $judge0,
    ) {}

    // ─── DÉMARRER UNE SESSION ────────────────────────────────────

    public function startSession(User $user, array $data): InterviewSession
    {
        // Générer la première question via IA
        $questionData = $this->groq->generateQuestion(
            type: $data['type'],
            difficulty: $data['difficulty'] ?? 'medium',
            stack: $data['stack_focus'] ?? '',
        );

        // Sauvegarder la question en base
        $question = InterviewQuestion::create([
            'category'   => $data['type'],
            'type'       => $data['type'],
            'difficulty' => $data['difficulty'] ?? 'medium',
            'title'      => $questionData['title'],
            'description' => $questionData['description'],
            'constraints' => $questionData['constraints'] ?? null,
            'examples'   => $questionData['examples'] ?? [],
            'hints'      => $questionData['hints'] ?? [],
            'stack'      => !empty($data['stack_focus']) ? [$data['stack_focus']] : [],
            'is_community' => false,
        ]);

        // Créer la session
        $session = InterviewSession::create([
            'user_id'        => $user->id,
            'type'           => $data['type'],
            'mode'           => $data['mode'] ?? 'mock',
            'difficulty'     => $data['difficulty'] ?? 'medium',
            'stack_focus'    => $data['stack_focus'] ?? null,
            'company_target' => $data['company_target'] ?? null,
            'duration_min'   => $data['duration_min'] ?? 30,
            'status'         => 'in_progress',
            'started_at'     => now(),
        ]);

        // Attacher la première question à la session via metadata
        $session->update([
            'score_breakdown' => [
                'questions' => [
                    [
                        'question_id' => $question->id,
                        'order'       => 1,
                        'answered'    => false,
                    ]
                ],
                'current_question' => 1,
                'total_questions'  => $this->getQuestionsCount($data['duration_min'] ?? 30),
            ],
        ]);

        return $session->load('user');
    }

    // ─── RÉPONDRE À UNE QUESTION ─────────────────────────────────

    public function respond(InterviewSession $session, array $data): array
    {
        $question = InterviewQuestion::findOrFail($data['question_id']);

        // Exécuter le code si fourni
        $executionResult = null;
        if (! empty($data['code_submitted']) && ! empty($data['language'])) {
            $executionResult = $this->judge0->execute(
                code: $data['code_submitted'],
                language: $data['language'],
            );
        }

        // Analyser la réponse avec l'IA
        $analysis = $this->groq->analyzeResponse(
            question: $question->description,
            answer: $data['text_answer'] ?? '',
            code: $data['code_submitted'] ?? null,
            executionResult: $executionResult ? json_encode($executionResult) : null,
            difficulty: $session->difficulty,
        );

        // Sauvegarder la réponse
        $response = InterviewResponse::create([
            'session_id'       => $session->id,
            'question_id'      => $question->id,
            'text_answer'      => $data['text_answer'] ?? null,
            'code_submitted'   => $data['code_submitted'] ?? null,
            'language'         => $data['language'] ?? null,
            'execution_result' => $executionResult,
            'score'            => $analysis['score'] ?? 0,
            'ai_analysis'      => $analysis,
            'time_taken_sec'   => $data['time_taken_sec'] ?? null,
        ]);

        // Mettre à jour le compteur de questions
        $session->increment('questions_count');

        // Générer la question suivante si la session n'est pas terminée
        $nextQuestion = null;
        $breakdown    = $session->score_breakdown ?? [];
        $currentQ     = $breakdown['current_question'] ?? 1;
        $totalQ       = $breakdown['total_questions'] ?? 3;

        if ($currentQ < $totalQ) {
            $nextQuestionData = $this->groq->generateQuestion(
                type: $session->type,
                difficulty: $this->getNextDifficulty($session->difficulty, $analysis['score'] ?? 50),
                stack: $session->stack_focus ?? '',
            );

            $nextQuestion = InterviewQuestion::create([
                'category'   => $session->type,
                'type'       => $session->type,
                'difficulty' => $this->getNextDifficulty($session->difficulty, $analysis['score'] ?? 50),
                'title'      => $nextQuestionData['title'],
                'description' => $nextQuestionData['description'],
                'constraints' => $nextQuestionData['constraints'] ?? null,
                'examples'   => $nextQuestionData['examples'] ?? [],
                'hints'      => $nextQuestionData['hints'] ?? [],
            ]);

            // Mettre à jour le score_breakdown
            $questions   = $breakdown['questions'] ?? [];
            $questions[] = [
                'question_id' => $nextQuestion->id,
                'order'       => $currentQ + 1,
                'answered'    => false,
            ];

            $session->update([
                'score_breakdown' => array_merge($breakdown, [
                    'questions'        => $questions,
                    'current_question' => $currentQ + 1,
                ]),
            ]);
        }

        return [
            'response'      => $response,
            'analysis'      => $analysis,
            'execution'     => $executionResult,
            'next_question' => $nextQuestion,
            'is_last'       => $currentQ >= $totalQ,
            'progress'      => [
                'current' => $currentQ,
                'total'   => $totalQ,
            ],
        ];
    }

    // ─── DEMANDER UN INDICE ───────────────────────────────────────

    public function getHint(InterviewSession $session, string $questionId, string $partialAnswer): string
    {
        $question = InterviewQuestion::findOrFail($questionId);

        $session->increment('hints_used');

        $hintNumber = min($session->hints_used, 3);

        // Utiliser les hints pré-générés si disponibles
        if (! empty($question->hints) && isset($question->hints[$hintNumber - 1])) {
            return $question->hints[$hintNumber - 1];
        }

        // Sinon générer via IA
        return $this->groq->generateHint(
            question: $question->description,
            partialAnswer: $partialAnswer,
            hintNumber: $hintNumber,
        );
    }

    // ─── TERMINER LA SESSION ──────────────────────────────────────

    public function completeSession(InterviewSession $session): InterviewSession
    {
        $responses = InterviewResponse::where('session_id', $session->id)->get();

        if ($responses->isEmpty()) {
            $session->update(['status' => 'abandoned']);
            return $session;
        }

        // Calculer le score global
        $avgScore = $responses->avg('score');

        // Générer le rapport final via IA
        $questionsData = $responses->map(fn($r) => [
            'question' => InterviewQuestion::find($r->question_id)?->description,
            'score'    => $r->score,
            'analysis' => $r->ai_analysis,
        ])->toArray();

        $report = $this->groq->generateSessionReport([
            'type'       => $session->type,
            'difficulty' => $session->difficulty,
            'score'      => $avgScore,
            'questions'  => $questionsData,
        ]);

        $duration = (int) abs(now()->diffInSeconds($session->started_at));

        // XP gagné selon le score
        $xpEarned = $this->calculateXP($avgScore, $session->difficulty);

        $session->update([
            'status'              => 'completed',
            'score_total'         => round($avgScore, 2),
            'ai_feedback'         => $report['overall_feedback'] ?? null,
            'actual_duration_sec' => $duration,
            'completed_at'        => now(),
            'xp_earned'           => $xpEarned,
            'score_breakdown'     => array_merge($session->score_breakdown ?? [], [
                'report'             => $report,
                'avg_score'          => $avgScore,
                'responses_count'    => $responses->count(),
            ]),
        ]);

        // Ajouter les XP au profil utilisateur
        $session->user->addXp(
            amount: $xpEarned,
            reason: 'interview_completed',
            refType: 'interview_session',
            refId: $session->id,
        );

        // Mettre à jour le score interview dans le profil
        $this->updateDeveloperScore($session->user);

        return $session->fresh();
    }

    // ─── HISTORIQUE ───────────────────────────────────────────────

    public function getHistory(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return InterviewSession::where('user_id', $user->id)
            ->when(! empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->with(['responses' => fn($q) => $q->select('id', 'session_id', 'score')])
            ->latest()
            ->paginate(10);
    }

    // ─── HELPERS PRIVÉS ───────────────────────────────────────────

    private function getQuestionsCount(int $durationMin): int
    {
        return match (true) {
            $durationMin <= 20 => 2,
            $durationMin <= 30 => 3,
            $durationMin <= 45 => 4,
            default            => 5,
        };
    }

    private function getNextDifficulty(string $current, float $score): string
    {
        $levels = ['easy', 'medium', 'hard', 'expert'];
        $index  = array_search($current, $levels);

        if ($score >= 80 && $index < 3) return $levels[$index + 1]; // Augmenter
        if ($score < 40 && $index > 0) return $levels[$index - 1];  // Baisser
        return $current;
    }

    private function calculateXP(float $score, string $difficulty): int
    {
        $base = match ($difficulty) {
            'easy'   => 10,
            'medium' => 20,
            'hard'   => 35,
            'expert' => 50,
            default  => 15,
        };

        return (int) ($base * ($score / 100));
    }

    private function updateDeveloperScore(User $user): void
    {
        $avgScore = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->avg('score_total');

        $user->developerProfile?->update([
            'interview_score'  => round($avgScore ?? 0, 2),
            'sessions_count'   => InterviewSession::where('user_id', $user->id)
                ->where('status', 'completed')->count(),
        ]);
    }
}
