<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeveloperFullResource extends JsonResource
{
    public bool $isSaved = false;

    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'username'         => $this->username,
            'avatar_url'       => $this->avatar_url,
            'github_username'  => $this->github_username,
            'level'            => $this->level,
            'xp_total'         => $this->xp_total,
            'current_streak'   => $this->current_streak,
            'longest_streak'   => $this->longest_streak,
            'is_available'     => $this->is_available,
            'is_saved'         => $this->isSaved,
            'profile'          => new DeveloperProfileResource(
                $this->whenLoaded('developerProfile')
            ),
            'skills'           => UserSkillResource::collection(
                $this->whenLoaded('skills')
            ),
            'badges'           => UserBadgeResource::collection(
                $this->whenLoaded('badges')
            ),
            'recent_sessions'  => $this->whenLoaded(
                'interviewSessions',
                fn() =>
                $this->interviewSessions->map(fn($s) => [
                    'type'         => $s->type,
                    'score_total'  => $s->score_total,
                    'completed_at' => $s->completed_at?->format('Y-m-d'),
                ])
            ),
        ];
    }
}
