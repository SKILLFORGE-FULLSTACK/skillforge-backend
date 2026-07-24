<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'week_start' => $this->week_start?->format('Y-m-d'),
            'participants_count' => $this->participants_count,
            'question' => $this->whenLoaded('question', fn () => [
                'id' => $this->question->id,
                'title' => $this->question->translated('title', $locale),
                'description' => $this->question->translated('description', $locale),
                'difficulty' => $this->question->difficulty,
                'examples' => $this->question->examples,
                'hints_count' => count($this->question->hints ?? []),
            ]),
        ];
    }
}
