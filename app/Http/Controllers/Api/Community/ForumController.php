<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommentRequest;
use App\Http\Requests\Community\StorePostRequest;
use App\Http\Resources\ForumCommentResource;
use App\Http\Resources\ForumPostResource;
use App\Models\ForumPost;
use App\Services\Community\ForumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function __construct(private ForumService $service) {}

    public function index(Request $request): JsonResponse
    {
        $posts = $this->service->list($request->only([
            'category',
            'search',
            'tag',
            'sort',
            'per_page',
        ]));

        return response()->json([
            'data' => ForumPostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'title'    => 'required|string|min:10|max:500',
            'body'     => 'required|string|min:20|max:10000',
            'category' => 'nullable|string|max:100',
            'tags'     => 'nullable|array|max:5',
            'tags.*'   => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $post = $this->service->createPost($request->user(), $validator->validated());

        return response()->json([
            'message' => 'Question publiee.',
            'post'    => new ForumPostResource($post),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $post = $this->service->show($id);

        return response()->json([
            'post' => new ForumPostResource($post),
        ]);
    }

    public function storeComment(StoreCommentRequest $request, string $id): JsonResponse
    {
        $post    = ForumPost::findOrFail($id);
        $comment = $this->service->createComment($request->user(), $post, $request->validated());

        return response()->json([
            'message' => 'Commentaire ajouté.',
            'comment' => new ForumCommentResource($comment),
        ], 201);
    }

    public function vote(Request $request, string $type, string $id): JsonResponse
    {
        $request->validate(['value' => 'required|in:-1,1']);

        $result = $this->service->vote(
            $request->user(),
            $type,
            $id,
            (int) $request->value
        );

        return response()->json($result);
    }

    public function acceptAnswer(Request $request, string $commentId): JsonResponse
    {
        try {
            $comment = $this->service->acceptAnswer($request->user(), $commentId);

            return response()->json([
                'message' => 'Réponse acceptée.',
                'comment' => new ForumCommentResource($comment),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
