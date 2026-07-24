<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewTurnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'speaker' => $this->whenLoaded('participant', fn () => $this->participant->role),
            'transcript' => $this->transcript,
            'audio_url' => $this->audio_url,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
