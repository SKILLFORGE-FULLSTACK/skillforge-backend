<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\SubmitChallengeRequest;
use App\Http\Resources\ChallengeResource;
use App\Models\WeeklyChallenge;
use App\Services\Community\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function __construct(private ChallengeService $service) {}

    public function current(): JsonResponse
    {
        $challenge = $this->service->getCurrentChallenge();

        return response()->json([
            'challenge' => new ChallengeResource($challenge),
        ]);
    }

    public function submit(SubmitChallengeRequest $request, string $id): JsonResponse
    {
        $challenge = WeeklyChallenge::findOrFail($id);

        try {
            $submission = $this->service->submit(
                $request->user(),
                $challenge,
                $request->validated()
            );

            return response()->json([
                'message'   => $submission->score == 100
                    ? 'Félicitations ! Solution correcte.'
                    : 'Solution soumise. Vérifiez le résultat.',
                'score'     => $submission->score,
                'execution' => $submission->execution_result,
                'xp_earned' => $submission->score == 100 ? 30 : 5,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function leaderboard(string $id): JsonResponse
    {
        $challenge   = WeeklyChallenge::findOrFail($id);
        $leaderboard = $this->service->getLeaderboard($challenge);

        return response()->json(['data' => $leaderboard]);
    }
}
