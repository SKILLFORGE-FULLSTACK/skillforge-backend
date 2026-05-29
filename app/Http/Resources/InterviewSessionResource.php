<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $breakdown       = $this->score_breakdown ?? [];
        $currentQuestion = null;

        // Récupérer la question courante
        if (! empty($breakdown['questions'])) {
            $currentIndex = ($breakdown['current_question'] ?? 1) - 1;
            $qData        = $breakdown['questions'][$currentIndex] ?? null;

            if ($qData) {
                $question        = \App\Models\InterviewQuestion::find($qData['question_id']);
                $currentQuestion = $question
                    ? new InterviewQuestionResource($question)
                    : null;
            }
        }

        return [
            'id'              => $this->id,
            'type'            => $this->type,
            'mode'            => $this->mode,
            'difficulty'      => $this->difficulty,
            'stack_focus'     => $this->stack_focus,
            'company_target'  => $this->company_target,
            'duration_min'    => $this->duration_min,
            'status'          => $this->status,
            'score_total'     => $this->score_total,
            'questions_count' => $this->questions_count,
            'hints_used'      => $this->hints_used,
            'xp_earned'       => $this->xp_earned,
            'started_at'      => $this->started_at?->toISOString(),
            'completed_at'    => $this->completed_at?->toISOString(),
            'progress'        => [
                'current' => $breakdown['current_question'] ?? 1,
                'total'   => $breakdown['total_questions'] ?? 3,
            ],
            'current_question' => $currentQuestion,
        ];
    }
}
