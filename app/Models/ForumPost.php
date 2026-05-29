<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumPost extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'category',
        'tags',
        'votes',
        'views',
        'answers_count',
        'is_answered',
        'accepted_answer_id',
    ];

    protected function casts(): array
    {
        return [
            'tags'        => 'array',
            'is_answered' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(ForumComment::class, 'post_id')
            ->whereNull('parent_id')
            ->with('replies.user', 'user')
            ->orderByDesc('votes');
    }

    public function acceptedAnswer()
    {
        return $this->belongsTo(ForumComment::class, 'accepted_answer_id');
    }
}
