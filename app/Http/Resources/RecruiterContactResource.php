<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\ResolvesLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruiterContactResource extends JsonResource
{
    use ResolvesLocale;

    public function toArray(Request $request): array
    {
        $locale = $this->resolveLocale($request);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'message' => $this->message,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'developer' => $this->whenLoaded('developer', fn () => [
                'id' => $this->developer->id,
                'name' => $this->developer->name,
                'username' => $this->developer->username,
                'avatar_url' => $this->developer->avatar_url,
            ]),
            'job_posting' => $this->whenLoaded('jobPosting', fn () => [
                'id' => $this->jobPosting->id,
                'title' => $this->jobPosting->translated('title', $locale),
            ]),
        ];
    }
}
