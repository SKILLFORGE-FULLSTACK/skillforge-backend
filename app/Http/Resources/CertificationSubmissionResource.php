<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'attempt_number'  => $this->attempt_number,
            'github_repo_url' => $this->github_repo_url,
            'live_url'        => $this->live_url,
            'score_total'     => $this->score_total,
            'score_breakdown' => $this->score_breakdown,
            'ai_review'       => $this->ai_review,
            'xp_earned'       => $this->xp_earned,
            'submitted_at'    => $this->submitted_at?->format('Y-m-d H:i'),
            'deadline_at'     => $this->deadline_at?->format('Y-m-d H:i'),
            'reviewed_at'     => $this->reviewed_at?->format('Y-m-d H:i'),
            'days_remaining'  => $this->deadline_at
                ? max(0, now()->diffInDays($this->deadline_at, false))
                : null,
            'certification'   => new CertificationResource(
                $this->whenLoaded('certification')
            ),
            'badge'           => $this->whenLoaded(
                'badge',
                fn() =>
                new UserBadgeResource($this->badge)
            ),
        ];
    }
}
