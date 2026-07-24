<?php

namespace App\Services\Interview;

use App\Models\InterviewRoom;
use App\Models\InterviewRoomParticipant;
use App\Models\InterviewSession;
use App\Models\InterviewTurn;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoiceInterviewService
{
    private const MAX_CANDIDATE_TURNS = 6;

    private const DAILY_ROOM_LIMIT = 8;

    public function __construct(private GroqService $groq) {}

    // ─── DÉMARRER UNE SALLE ─────────────────────────────────────────

    public function start(User $user, ?string $jobPostingId, string $language = 'fr'): array
    {
        $this->ensureDailyQuota($user);

        $jobPosting = $jobPostingId
            ? JobPosting::with('recruiter.recruiterProfile')->findOrFail($jobPostingId)
            : null;

        $room = DB::transaction(function () use ($user, $jobPosting, $language) {
            $room = InterviewRoom::create([
                'job_posting_id' => $jobPosting?->id,
                'created_by' => $user->id,
                'mode' => $jobPosting ? 'job_interview' : 'practice',
                'ai_participates' => true,
                'status' => 'in_progress',
                'language' => $language,
                'started_at' => now(),
            ]);

            InterviewRoomParticipant::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'role' => 'candidate',
                'joined_at' => now(),
            ]);

            InterviewRoomParticipant::create([
                'room_id' => $room->id,
                'user_id' => null,
                'role' => 'ai',
                'joined_at' => now(),
            ]);

            return $room;
        });

        $aiParticipant = $room->participants()->where('role', 'ai')->firstOrFail();

        $context = $this->buildContext($user, $jobPosting);
        $openingText = $this->groq->generateInterviewOpening($context, $language);
        $audioUrl = $this->synthesizeAndStore($room, $openingText);

        $turn = InterviewTurn::create([
            'room_id' => $room->id,
            'participant_id' => $aiParticipant->id,
            'order' => 1,
            'transcript' => $openingText,
            'audio_url' => $audioUrl,
        ]);

        return ['room' => $room->fresh(['participants', 'jobPosting.recruiter.recruiterProfile']), 'turn' => $turn];
    }

    // ─── SOUMETTRE UN TOUR (audio candidat → réponse IA) ────────────

    public function submitTurn(InterviewRoom $room, UploadedFile $audio): array
    {
        abort_unless($room->status === 'in_progress', 422, "Cette session n'est plus active.");

        $candidateParticipant = $room->participants()->where('role', 'candidate')->firstOrFail();
        $aiParticipant = $room->participants()->where('role', 'ai')->firstOrFail();

        $nextOrder = ((int) $room->turns()->max('order')) + 1;
        $absolutePath = $audio->getRealPath();
        // Le chemin temporaire PHP (getRealPath) n'a pas d'extension : Groq
        // détecte le format audio via le nom de fichier, pas le contenu.
        $originalFilename = $audio->getClientOriginalName() ?: 'audio.wav';

        $storedPath = $audio->store("interview-audio/{$room->id}", 's3');
        $transcript = $this->groq->transcribeAudio($absolutePath, $room->language, $originalFilename);

        $candidateTurn = InterviewTurn::create([
            'room_id' => $room->id,
            'participant_id' => $candidateParticipant->id,
            'order' => $nextOrder,
            'transcript' => $transcript,
            'audio_url' => Storage::disk('s3')->url($storedPath),
        ]);

        $candidateTurnsCount = $room->turns()->where('participant_id', $candidateParticipant->id)->count();

        $history = $room->turns()
            ->orderBy('order')
            ->get()
            ->map(fn (InterviewTurn $t) => [
                'role' => $t->participant_id === $aiParticipant->id ? 'assistant' : 'user',
                'content' => $t->transcript,
            ])
            ->toArray();

        $context = $this->buildContext($room->creator, $room->jobPosting);

        $reply = $this->groq->generateInterviewReply(
            context: $context,
            history: $history,
            turnNumber: $candidateTurnsCount,
            maxTurns: self::MAX_CANDIDATE_TURNS,
            language: $room->language,
        );

        $isFinal = $reply['should_end'] || $candidateTurnsCount >= self::MAX_CANDIDATE_TURNS;
        $audioUrl = $this->synthesizeAndStore($room, $reply['reply']);

        $aiTurn = InterviewTurn::create([
            'room_id' => $room->id,
            'participant_id' => $aiParticipant->id,
            'order' => $nextOrder + 1,
            'transcript' => $reply['reply'],
            'audio_url' => $audioUrl,
        ]);

        return [
            'candidate_turn' => $candidateTurn,
            'ai_turn' => $aiTurn,
            'is_final' => $isFinal,
            'progress' => [
                'current' => $candidateTurnsCount,
                'total' => self::MAX_CANDIDATE_TURNS,
            ],
        ];
    }

    // ─── TERMINER LA SALLE ───────────────────────────────────────────

    public function complete(InterviewRoom $room): InterviewRoom
    {
        if ($room->status === 'completed') {
            return $room;
        }

        $turns = $room->turns()->with('participant')->orderBy('order')->get();

        if ($turns->where('participant.role', 'candidate')->isEmpty()) {
            $room->update(['status' => 'abandoned']);

            return $room;
        }

        $transcript = $turns
            ->map(fn (InterviewTurn $t) => ($t->participant->role === 'ai' ? 'Recruteur' : 'Candidat').': '.$t->transcript)
            ->implode("\n");

        $context = $this->buildContext($room->creator, $room->jobPosting);
        $report = $this->groq->generateVoiceInterviewReport($context, $transcript, $room->language);

        $duration = (int) abs(now()->diffInSeconds($room->started_at));
        $score = (float) ($report['score'] ?? 50);
        $xpEarned = (int) round(($score / 100) * 30);

        $room->update([
            'status' => 'completed',
            'score_total' => round($score, 2),
            'ai_feedback' => $report['overall_feedback'] ?? null,
            'score_breakdown' => ['report' => $report, 'duration_sec' => $duration],
            'xp_earned' => $xpEarned,
            'completed_at' => now(),
        ]);

        $room->creator->addXp(
            amount: $xpEarned,
            reason: 'voice_interview_completed',
            refType: 'interview_room',
            refId: $room->id,
        );

        $this->updateDeveloperScore($room->creator);

        return $room->fresh(['participants', 'turns.participant', 'jobPosting.recruiter.recruiterProfile']);
    }

    // ─── HELPERS PRIVÉS ───────────────────────────────────────────

    private function synthesizeAndStore(InterviewRoom $room, string $text): ?string
    {
        $audio = $this->groq->synthesizeSpeech($text);

        if (! $audio) {
            return null;
        }

        $path = "interview-audio/{$room->id}/".Str::uuid().'.'.$this->groq->ttsFormat();
        Storage::disk('s3')->put($path, $audio);

        return Storage::disk('s3')->url($path);
    }

    private function buildContext(User $candidate, ?JobPosting $jobPosting): string
    {
        $profile = $candidate->developerProfile;
        $skills = $candidate->skills()->pluck('skill_name')->take(8)->implode(', ');

        $candidateContext = "Profil du candidat : {$candidate->name}"
            .($profile?->headline ? ", {$profile->headline}" : '')
            .($profile?->current_level ? ", niveau {$profile->current_level}" : '')
            .($skills ? ". Compétences : {$skills}." : '.');

        if (! $jobPosting) {
            return "{$candidateContext}\nC'est un entretien d'entraînement général, sans offre précise — reste généraliste sur les compétences techniques et le comportemental.";
        }

        $company = $jobPosting->recruiter?->recruiterProfile?->company_name ?? "l'entreprise";
        $skillsRequired = collect($jobPosting->required_skills ?? [])->implode(', ');

        return "{$candidateContext}\nOffre visée : \"{$jobPosting->title}\" chez {$company}.\n"
            ."Description : {$jobPosting->description}\n"
            .($skillsRequired ? "Compétences requises : {$skillsRequired}.\n" : '')
            .($jobPosting->min_level ? "Niveau recherché : {$jobPosting->min_level}.\n" : '')
            .'Base tes questions sur cette offre en priorité.';
    }

    private function ensureDailyQuota(User $user): void
    {
        $count = InterviewRoom::where('created_by', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($count >= self::DAILY_ROOM_LIMIT) {
            throw ValidationException::withMessages([
                'quota' => 'Vous avez atteint la limite quotidienne d\'entretiens vocaux ('.self::DAILY_ROOM_LIMIT.'). Réessayez demain.',
            ]);
        }
    }

    private function updateDeveloperScore(User $user): void
    {
        $sessionAvg = InterviewSession::where('user_id', $user->id)->where('status', 'completed')->avg('score_total');
        $sessionCount = InterviewSession::where('user_id', $user->id)->where('status', 'completed')->count();
        $roomAvg = InterviewRoom::where('created_by', $user->id)->where('status', 'completed')->avg('score_total');
        $roomCount = InterviewRoom::where('created_by', $user->id)->where('status', 'completed')->count();

        $totalCount = $sessionCount + $roomCount;
        $combinedAvg = $totalCount > 0
            ? ((($sessionAvg ?? 0) * $sessionCount) + (($roomAvg ?? 0) * $roomCount)) / $totalCount
            : 0;

        $user->developerProfile?->update([
            'interview_score' => round($combinedAvg, 2),
            'sessions_count' => $totalCount,
        ]);
    }
}
