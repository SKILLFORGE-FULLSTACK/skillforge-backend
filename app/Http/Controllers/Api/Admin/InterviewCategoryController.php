<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInterviewCategoryRequest;
use App\Http\Requests\Admin\UpdateInterviewCategoryRequest;
use App\Http\Resources\InterviewCategoryResource;
use App\Models\InterviewCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewCategoryController extends Controller
{
    // Toutes les catégories (actives et inactives), pour la gestion admin
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $categories = InterviewCategory::orderBy('sort_order')->get();

        return response()->json([
            'data' => InterviewCategoryResource::collection($categories),
        ]);
    }

    public function store(StoreInterviewCategoryRequest $request): JsonResponse
    {
        $category = InterviewCategory::create($request->validated());

        return response()->json([
            'message' => 'Catégorie créée.',
            'category' => new InterviewCategoryResource($category),
        ], 201);
    }

    public function update(UpdateInterviewCategoryRequest $request, string $id): JsonResponse
    {
        $category = InterviewCategory::findOrFail($id);
        $category->update($request->validated());

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'category' => new InterviewCategoryResource($category->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        InterviewCategory::findOrFail($id)->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
