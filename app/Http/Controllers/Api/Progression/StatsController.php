<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Services\Progression\StatsService;
use App\Services\Progression\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private StatsService  $statsService,
        private StreakService  $streakService,
    ) {}

    public function myStats(Request $request): JsonResponse
    {
        $stats = $this->statsService->getDeveloperStats($request->user());

        return response()->json($stats);
    }

    public function updateStreak(Request $request): JsonResponse
    {
        $result = $this->streakService->updateStreak($request->user());

        return response()->json([
            'message'        => $result['is_new_day'] ? 'Streak mis à jour !' : 'Déjà actif aujourd\'hui.',
            'current_streak' => $result['current_streak'],
            'longest_streak' => $result['longest_streak'],
            'is_new_day'     => $result['is_new_day'],
        ]);
    }

    public function xpHistory(Request $request): JsonResponse
    {
        $history = $this->statsService->getXpHistory(
            $request->user(),
            $request->integer('limit', 20)
        );

        return response()->json(['data' => $history]);
    }
}
