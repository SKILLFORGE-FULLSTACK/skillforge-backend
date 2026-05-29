<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'body'        => $this->body,
            'votes'       => $this->votes,
            'is_accepted' => $this->is_accepted,
            'parent_id'   => $this->parent_id,
            'created_at'  => $this->created_at?->diffForHumans(),
            'user'        => $this->whenLoaded('user', fn() => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'username'   => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
                'level'      => $this->user->level,
            ]),
            'replies'     => ForumCommentResource::collection(
                $this->whenLoaded('replies')
            ),
        ];
    }
}
