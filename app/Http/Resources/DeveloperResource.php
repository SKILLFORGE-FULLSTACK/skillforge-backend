<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeveloperResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'username'       => $this->username,
            'avatar_url'     => $this->avatar_url,
            'level'          => $this->level,
            'xp_total'       => $this->xp_total,
            'current_streak' => $this->current_streak,
            'is_available'   => $this->is_available,
            'profile'        => new DeveloperProfileResource(
                $this->whenLoaded('developerProfile')
            ),
            'skills'         => UserSkillResource::collection(
                $this->whenLoaded('skills')
            ),
            'badges'         => UserBadgeResource::collection(
                $this->whenLoaded('badges')
            ),
        ];
    }
}
