<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $breakdown = $this->score_breakdown ?? [];
        $report    = $breakdown['report'] ?? [];

        return [
            'session' => [
                'id'              => $this->id,
                'type'            => $this->type,
                'difficulty'      => $this->difficulty,
                'stack_focus'     => $this->stack_focus,
                'score_total'     => $this->score_total,
                'questions_count' => $this->questions_count,
                'hints_used'      => $this->hints_used,
                'xp_earned'       => $this->xp_earned,
                'duration_sec'    => $this->actual_duration_sec,
                'completed_at'    => $this->completed_at?->format('Y-m-d H:i'),
            ],
            'ai_feedback'       => $this->ai_feedback,
            'strengths'         => $report['strengths'] ?? [],
            'improvement_areas' => $report['improvement_areas'] ?? [],
            'resources'         => $report['resources'] ?? [],
            'readiness_level'   => $report['readiness_level'] ?? 'not_ready',
            'weeks_to_improve'  => $report['estimated_weeks_to_improve'] ?? null,
            'responses'         => InterviewResponseResource::collection(
                $this->whenLoaded('responses')
            ),
        ];
    }
}
