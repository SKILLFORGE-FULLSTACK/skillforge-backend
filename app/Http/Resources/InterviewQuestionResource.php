<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'type'        => $this->type,
            'difficulty'  => $this->difficulty,
            'constraints' => $this->constraints,
            'examples'    => $this->examples,
            'stack'       => $this->stack,
            // hints cachés jusqu'à demande explicite
            'hints_count' => count($this->hints ?? []),
        ];
    }
}
