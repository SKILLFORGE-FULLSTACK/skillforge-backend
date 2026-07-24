<?php

namespace App\Http\Controllers\Api\Interview;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterviewCategoryResource;
use App\Models\InterviewCategory;
use Illuminate\Http\JsonResponse;

class InterviewCategoryController extends Controller
{
    // Catégories actives, pour peupler le sélecteur "Démarrer un entretien"
    public function index(): JsonResponse
    {
        $categories = InterviewCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => InterviewCategoryResource::collection($categories),
        ]);
    }
}
