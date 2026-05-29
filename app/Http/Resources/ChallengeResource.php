<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'week_start'         => $this->week_start?->format('Y-m-d'),
            'participants_count' => $this->participants_count,
            'question'           => $this->whenLoaded('question', fn() => [
                'id'          => $this->question->id,
                'title'       => $this->question->title,
                'description' => $this->question->description,
                'difficulty'  => $this->question->difficulty,
                'examples'    => $this->question->examples,
                'hints_count' => count($this->question->hints ?? []),
            ]),
        ];
    }
}
