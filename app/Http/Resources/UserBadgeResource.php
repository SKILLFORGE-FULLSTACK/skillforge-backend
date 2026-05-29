<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'badge_type'   => $this->badge_type,
            'score'        => $this->score,
            'verify_token' => $this->verify_token,
            'verify_url'   => url("/verify/{$this->verify_token}"),
            'issued_at'    => $this->issued_at?->format('Y-m-d'),
            'expires_at'   => $this->expires_at?->format('Y-m-d'),
            'is_expired'   => $this->isExpired(),
            'certification' => $this->whenLoaded('certification', fn() => [
                'title'    => $this->certification->title,
                'slug'     => $this->certification->slug,
                'level'    => $this->certification->level,
                'category' => $this->certification->category,
            ]),
        ];
    }
}
