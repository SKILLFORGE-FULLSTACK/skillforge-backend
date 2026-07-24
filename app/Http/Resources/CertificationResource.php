<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->translated('title', $locale),
            'category' => $this->category,
            'level' => $this->level,
            'description' => $this->translated('description', $locale),
            'skills_covered' => $this->skills_covered,
            'project_brief' => $this->translated('project_brief', $locale),
            'evaluation_criteria' => $this->evaluation_criteria,
            'passing_score' => $this->passing_score,
            'duration_days' => $this->duration_days,
            'validity_months' => $this->validity_months,
            'price_credits' => $this->price_credits,
            'attempts_allowed' => $this->attempts_allowed,
            'badge_color' => $this->badge_color,
        ];
    }
}
