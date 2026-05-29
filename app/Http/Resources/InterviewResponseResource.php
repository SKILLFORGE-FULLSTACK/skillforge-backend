<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analysis = $this->ai_analysis ?? [];

        return [
            'id'             => $this->id,
            'question'       => new InterviewQuestionResource(
                $this->whenLoaded('question')
            ),
            'text_answer'    => $this->text_answer,
            'code_submitted' => $this->code_submitted,
            'language'       => $this->language,
            'score'          => $this->score,
            'verdict'        => $analysis['verdict'] ?? null,
            'strengths'      => $analysis['strengths'] ?? [],
            'weaknesses'     => $analysis['weaknesses'] ?? [],
            'feedback'       => $analysis['feedback'] ?? null,
            'model_answer'   => $analysis['model_answer'] ?? null,
            'execution'      => $this->execution_result,
            'time_taken_sec' => $this->time_taken_sec,
        ];
    }
}
