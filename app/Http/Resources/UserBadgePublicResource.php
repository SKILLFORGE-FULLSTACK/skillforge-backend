<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgePublicResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'title' => $this->title,
            'description' => $this->description,
            'score' => $this->score,
            'issued_at' => $this->issued_at?->format('Y-m-d'),
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'is_expired' => $this->isExpired(),
            'is_valid' => ! $this->isExpired(),
            'certificate_url' => $this->certificate_url,
            'developer' => [
                'name' => $this->user->name,
                'username' => $this->user->username,
            ],
            'certification' => $this->whenLoaded('certification', fn () => [
                'title' => $this->certification->translated('title', $locale),
                'category' => $this->certification->category,
                'level' => $this->certification->level,
            ]),
        ];
    }
}
