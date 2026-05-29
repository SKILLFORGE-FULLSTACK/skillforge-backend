<?php

namespace App\Http\Controllers\Api\Progression;

use App\Http\Controllers\Controller;
use App\Services\Progression\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(private StatsService $service) {}

    public function heatmap(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:7|max:365',
        ]);

        $heatmap = $this->service->getActivityHeatmap(
            $request->user(),
            $request->integer('days', 365)
        );

        return response()->json([
            'data'  => $heatmap,
            'total' => count($heatmap),
        ]);
    }
}
