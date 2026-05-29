<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Services\Progression\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period'   => 'nullable|in:global,weekly',
            'category' => 'nullable|string|max:100',
            'limit'    => 'nullable|integer|min:5|max:100',
        ]);

        $period   = $request->input('period', 'global');
        $category = $request->input('category', 'all');
        $limit    = $request->integer('limit', 50);

        $data = $period === 'weekly'
            ? $this->service->getWeeklyLeaderboard($category, $limit)
            : $this->service->getGlobalLeaderboard($category, $limit);

        // Trouver le rang de l'utilisateur connecté
        $myRank = $this->service->getUserRank($request->user(), $period);

        return response()->json([
            'period'   => $period,
            'category' => $category,
            'data'     => $data,
            'my_rank'  => $myRank['rank'],
        ]);
    }
}
