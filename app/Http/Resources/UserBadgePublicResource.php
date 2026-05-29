<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgePublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'score'       => $this->score,
            'issued_at'   => $this->issued_at?->format('Y-m-d'),
            'expires_at'  => $this->expires_at?->format('Y-m-d'),
            'is_expired'  => $this->isExpired(),
            'is_valid'    => ! $this->isExpired(),
            'developer'   => [
                'name'     => $this->user->name,
                'username' => $this->user->username,
            ],
            'certification' => $this->whenLoaded('certification', fn() => [
                'title'    => $this->certification->title,
                'category' => $this->certification->category,
                'level'    => $this->certification->level,
            ]),
        ];
    }
}
