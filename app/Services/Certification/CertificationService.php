<?php

namespace App\Services\Certification;

use App\Models\Certification;
use App\Models\CertificationSubmission;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CertificationService
{
    public function __construct(
        private ReviewService $reviewService,
        private BadgeService  $badgeService,
    ) {}

    public function list(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Certification::where('is_active', true);

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->orderBy('level')->get();
    }

    public function startCertification(User $user, Certification $certification): CertificationSubmission
    {
        // Vérifier si une soumission en cours existe déjà
        $existing = CertificationSubmission::where('user_id', $user->id)
            ->where('certification_id', $certification->id)
            ->whereIn('status', ['in_progress', 'submitted', 'reviewing'])
            ->first();

        if ($existing) {
            throw new \Exception('Vous avez déjà une soumission en cours pour cette certification.', 409);
        }

        // Vérifier le nombre de tentatives
        $attempts = CertificationSubmission::where('user_id', $user->id)
            ->where('certification_id', $certification->id)
            ->count();

        if ($attempts >= $certification->attempts_allowed) {
            throw new \Exception("Nombre maximum de tentatives atteint ({$certification->attempts_allowed}).", 403);
        }

        return CertificationSubmission::create([
            'user_id'          => $user->id,
            'certification_id' => $certification->id,
            'attempt_number'   => $attempts + 1,
            'status'           => 'in_progress',
            'deadline_at'      => now()->addDays($certification->duration_days),
        ]);
    }

    public function submitProject(CertificationSubmission $submission, array $data): CertificationSubmission
    {
        if ($submission->status !== 'in_progress') {
            throw new \Exception('Cette soumission n\'est plus modifiable.', 422);
        }

        if ($submission->deadline_at && $submission->deadline_at->isPast()) {
            throw new \Exception('La date limite de soumission est dépassée.', 422);
        }

        $submission->update([
            'github_repo_url' => $data['github_repo_url'],
            'live_url'        => $data['live_url'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'status'          => 'reviewing',
            'submitted_at'    => now(),
        ]);

        // Lancer la review IA en arrière-plan
        \App\Jobs\ReviewCertificationJob::dispatch($submission->id);

        return $submission->fresh();
    }

    public function processReview(string $submissionId): void
    {
        $submission = CertificationSubmission::with('certification')->findOrFail($submissionId);

        if ($submission->status !== 'reviewing') return;

        $review = $this->reviewService->reviewProject($submission);

        $passed = ($review['score_total'] ?? 0) >= $submission->certification->passing_score
            && ($review['verdict'] ?? 'failed') === 'passed';

        DB::transaction(function () use ($submission, $review, $passed) {
            $submission->update([
                'status'         => $passed ? 'passed' : 'failed',
                'score_total'    => $review['score_total'],
                'score_breakdown' => $review['score_breakdown'] ?? [],
                'ai_review'      => $review['feedback'],
                'reviewed_at'    => now(),
                'xp_earned'      => $passed ? $this->calculateXP($review['score_total'], $submission->certification->level) : 0,
            ]);

            if ($passed) {
                // Générer le badge
                $badge = $this->badgeService->generateBadge($submission);

                // XP au développeur
                $submission->user->addXp(
                    amount: $submission->xp_earned,
                    reason: 'certification_passed',
                    refType: 'user_badge',
                    refId: $badge->id,
                );

                // Mettre à jour le profil
                $submission->user->developerProfile?->increment('certifications_count');

                // Ajouter la compétence certifiée
                foreach ($submission->certification->skills_covered ?? [] as $skill) {
                    \App\Models\UserSkill::updateOrCreate(
                        ['user_id' => $submission->user_id, 'skill_name' => $skill],
                        ['is_certified' => true, 'level' => 'advanced']
                    );
                }

                // Notification
                \App\Models\Notification::create([
                    'user_id' => $submission->user_id,
                    'type'    => 'cert_passed',
                    'title'   => "🎉 Félicitations ! Vous avez obtenu la certification {$submission->certification->title}",
                    'body'    => "Score : {$submission->score_total}/100. Votre badge est disponible sur votre profil.",
                    'data'    => ['badge_id' => $badge->id, 'score' => $submission->score_total],
                ]);
            } else {
                \App\Models\Notification::create([
                    'user_id' => $submission->user_id,
                    'type'    => 'cert_failed',
                    'title'   => "Certification {$submission->certification->title} — Non obtenue",
                    'body'    => "Score : {$submission->score_total}/100 (minimum requis : {$submission->certification->passing_score}). Consultez votre rapport pour vous améliorer.",
                    'data'    => ['submission_id' => $submission->id, 'score' => $submission->score_total],
                ]);
            }
        });
    }

    public function getMySubmissions(User $user): LengthAwarePaginator
    {
        return CertificationSubmission::where('user_id', $user->id)
            ->with(['certification', 'badge.certification'])
            ->latest()
            ->paginate(10);
    }

    private function calculateXP(float $score, string $level): int
    {
        $base = match ($level) {
            'junior' => 100,
            'mid'    => 200,
            'senior' => 350,
            default  => 150,
        };

        return (int) ($base * ($score / 100));
    }
}
