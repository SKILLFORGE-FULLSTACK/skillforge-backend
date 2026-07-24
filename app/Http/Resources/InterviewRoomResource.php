<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewRoomResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'mode' => $this->mode,
            'status' => $this->status,
            'language' => $this->language,
            'score_total' => $this->score_total,
            'ai_feedback' => $this->ai_feedback,
            'score_breakdown' => $this->score_breakdown,
            'xp_earned' => $this->xp_earned,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'job_posting' => $this->whenLoaded('jobPosting', fn () => $this->jobPosting ? [
                'id' => $this->jobPosting->id,
                'title' => $this->jobPosting->translated('title', $locale),
                'company_name' => $this->jobPosting->recruiter?->recruiterProfile?->company_name,
            ] : null),
            'turns' => InterviewTurnResource::collection($this->whenLoaded('turns')),
        ];
    }
}
