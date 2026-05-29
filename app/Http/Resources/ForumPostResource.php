<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'body'         => $this->body,
            'category'     => $this->category,
            'tags'         => $this->tags,
            'votes'        => $this->votes,
            'views'        => $this->views,
            'answers_count' => $this->answers_count,
            'is_answered'  => $this->is_answered,
            'created_at'   => $this->created_at?->diffForHumans(),
            'user'         => $this->whenLoaded('user', fn() => [
                'id'         => $this->user->id,
                'name'       => $this->user->name,
                'username'   => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
                'level'      => $this->user->level,
            ]),
            'comments'     => ForumCommentResource::collection(
                $this->whenLoaded('comments')
            ),
            'accepted_answer' => $this->whenLoaded(
                'acceptedAnswer',
                fn() =>
                $this->acceptedAnswer
                    ? new ForumCommentResource($this->acceptedAnswer)
                    : null
            ),
        ];
    }
}
