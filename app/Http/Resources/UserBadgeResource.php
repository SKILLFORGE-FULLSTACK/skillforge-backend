<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBadgeResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'badge_type' => $this->badge_type,
            'score' => $this->score,
            'verify_token' => $this->verify_token,
            'verify_url' => rtrim((string) config('services.frontend_url'), '/')."/verify/{$this->verify_token}",
            'badge_image_url' => $this->badge_image_url,
            'certificate_url' => $this->certificate_url,
            'issued_at' => $this->issued_at?->format('Y-m-d'),
            'expires_at' => $this->expires_at?->format('Y-m-d'),
            'is_expired' => $this->isExpired(),
            'certification' => $this->whenLoaded('certification', fn () => [
                'title' => $this->certification->translated('title', $locale),
                'slug' => $this->certification->slug,
                'level' => $this->certification->level,
                'category' => $this->certification->category,
            ]),
        ];
    }
}
