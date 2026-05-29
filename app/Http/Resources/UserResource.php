<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'username'       => $this->username,
            'email'          => $this->email,
            'avatar_url'     => $this->avatar_url,
            'role'           => $this->role,
            'plan'           => $this->plan,
            'xp_total'       => $this->xp_total,
            'level'          => $this->level,
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'is_available'   => $this->is_available,
            'is_public'      => $this->is_public,
            'created_at'     => $this->created_at?->format('Y-m-d'),
        ];
    }
}
