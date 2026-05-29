<?php

namespace App\Services\Community;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\Notification;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ForumService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = ForumPost::with(['user:id,name,username,avatar_url,level'])
            ->withCount('comments')
            ->latest();

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->where(
                fn($q) =>
                $q->where('title', 'ilike', "%{$filters['search']}%")
                    ->orWhere('body', 'ilike', "%{$filters['search']}%")
            );
        }

        if (! empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        if (! empty($filters['sort'])) {
            match ($filters['sort']) {
                'votes'  => $query->orderByDesc('votes'),
                'views'  => $query->orderByDesc('views'),
                default  => $query->latest(),
            };
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function show(string $id): ForumPost
    {
        $post = ForumPost::with([
            'user:id,name,username,avatar_url,level',
            'comments.user:id,name,username,avatar_url,level',
            'comments.replies.user:id,name,username,avatar_url',
            'acceptedAnswer.user:id,name,username',
        ])->findOrFail($id);

        $post->increment('views');

        return $post;
    }

    public function createPost(User $user, array $data): ForumPost
    {
        $post = ForumPost::create([
            'user_id'  => $user->id,
            'title'    => $data['title'],
            'body'     => $data['body'],
            'category' => $data['category'] ?? null,
            'tags'     => $data['tags'] ?? [],
        ]);

        // XP isolé — une erreur ici ne bloque pas la création
        try {
            $user->addXp(5, 'forum_post_created', 'forum_post', $post->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('XP forum post failed', ['error' => $e->getMessage()]);
        }

        return $post->load('user');
    }

    public function createComment(User $user, ForumPost $post, array $data): ForumComment
    {
        $comment = ForumComment::create([
            'post_id'   => $post->id,
            'user_id'   => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body'      => $data['body'],
        ]);

        $post->increment('answers_count');

        if ($post->user_id !== $user->id) {
            Notification::create([
                'user_id' => $post->user_id,
                'type'    => 'forum_reply',
                'title'   => "{$user->name} a repondu a votre question",
                'body'    => $post->title,
                'data'    => ['post_id' => $post->id, 'comment_id' => $comment->id],
            ]);
        }

        try {
            $user->addXp(3, 'forum_comment_created', 'forum_comment', $comment->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('XP forum comment failed', ['error' => $e->getMessage()]);
        }

        return $comment->load('user');
    }

    public function vote(User $user, string $type, string $id, int $value): array
    {
        $modelClass = $type === 'post'
            ? ForumPost::class
            : ForumComment::class;

        $votableType = $type === 'post'
            ? 'forum_posts'
            : 'forum_comments';

        $model = $modelClass::findOrFail($id);

        // Vérifier le vote existant
        $existing = Vote::where('user_id', $user->id)
            ->where('votable_type', $votableType)
            ->where('votable_id', $id)
            ->first();

        DB::transaction(function () use ($existing, $user, $votableType, $id, $value, $model) {
            if ($existing) {
                if ($existing->value === $value) {
                    // Annuler le vote
                    $model->decrement('votes', $value);
                    $existing->delete();
                    return;
                }
                // Changer le vote
                $diff = $value - $existing->value;
                $model->increment('votes', $diff);
                $existing->update(['value' => $value]);
            } else {
                // Nouveau vote
                Vote::create([
                    'user_id'      => $user->id,
                    'votable_type' => $votableType,
                    'votable_id'   => $id,
                    'value'        => $value,
                ]);
                $model->increment('votes', $value);
            }
        });

        return ['votes' => $model->fresh()->votes];
    }

    public function acceptAnswer(User $user, string $commentId): ForumComment
    {
        $comment = ForumComment::with('post')->findOrFail($commentId);
        $post    = $comment->post;

        if ($post->user_id !== $user->id) {
            throw new \Exception('Seul l\'auteur peut accepter une réponse.', 403);
        }

        DB::transaction(function () use ($post, $comment) {
            // Désaccepter l'ancienne réponse
            if ($post->accepted_answer_id) {
                ForumComment::where('id', $post->accepted_answer_id)
                    ->update(['is_accepted' => false]);
            }

            $comment->update(['is_accepted' => true]);
            $post->update([
                'accepted_answer_id' => $comment->id,
                'is_answered'        => true,
            ]);
        });

        // Notifier l'auteur du commentaire
        if ($comment->user_id !== $user->id) {
            Notification::create([
                'user_id' => $comment->user_id,
                'type'    => 'answer_accepted',
                'title'   => 'Votre réponse a été acceptée !',
                'body'    => "Sur la question : \"{$post->title}\"",
                'data'    => ['post_id' => $post->id],
            ]);

            // Bonus XP
            $comment->user->addXp(15, 'answer_accepted', 'forum_comment', $comment->id);
        }

        return $comment->fresh();
    }
}
