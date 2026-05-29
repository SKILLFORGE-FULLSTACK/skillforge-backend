<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruiterContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'message'     => $this->message,
            'created_at'  => $this->created_at?->format('Y-m-d H:i'),
            'developer'   => $this->whenLoaded('developer', fn() => [
                'id'         => $this->developer->id,
                'name'       => $this->developer->name,
                'username'   => $this->developer->username,
                'avatar_url' => $this->developer->avatar_url,
            ]),
            'job_posting' => $this->whenLoaded('jobPosting', fn() => [
                'id'    => $this->jobPosting->id,
                'title' => $this->jobPosting->title,
            ]),
        ];
    }
}
