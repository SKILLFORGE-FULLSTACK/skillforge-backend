<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewQuestionResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'title' => $this->translated('title', $locale),
            'description' => $this->translated('description', $locale),
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'constraints' => $this->constraints,
            'examples' => $this->examples,
            'stack' => $this->stack,
            // hints cachés jusqu'à demande explicite
            'hints_count' => count($this->hints ?? []),
        ];
    }
}
