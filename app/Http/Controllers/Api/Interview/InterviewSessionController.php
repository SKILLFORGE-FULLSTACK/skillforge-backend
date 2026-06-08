<?php

namespace App\Http\Controllers\Api\Interview;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\RespondInterviewRequest;
use App\Http\Requests\Interview\StartInterviewRequest;
use App\Http\Resources\InterviewReportResource;
use App\Http\Resources\InterviewSessionResource;
use App\Models\InterviewSession;
use App\Services\Interview\InterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewSessionController extends Controller
{
    public function __construct(private InterviewService $service) {}

    // Démarrer une session
    public function start(StartInterviewRequest $request): JsonResponse
    {
        $session = $this->service->startSession($request->user(), $request->validated());

        return response()->json([
            'message' => 'Session démarrée.',
            'session' => new InterviewSessionResource($session),
        ], 201);
    }

    // Répondre à une question
    public function respond(RespondInterviewRequest $request, string $id): JsonResponse
    {
        $session = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->findOrFail($id);

        $result = $this->service->respond($session, $request->validated());

        return response()->json([
            'score'         => $result['response']->score,
            'verdict'       => $result['analysis']['verdict'] ?? null,
            'feedback'      => $result['analysis']['feedback'] ?? null,
            'strengths'     => $result['analysis']['strengths'] ?? [],
            'weaknesses'    => $result['analysis']['weaknesses'] ?? [],
            'model_answer'  => $result['analysis']['model_answer'] ?? null,
            'execution'     => $result['execution'],
            'next_question' => $result['next_question']
                ? new \App\Http\Resources\InterviewQuestionResource($result['next_question'])
                : null,
            'is_last'       => $result['is_last'],
            'progress'      => $result['progress'],
        ]);
    }

    // Demander un indice
    public function hint(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'question_id'    => 'required|uuid',
            'partial_answer' => 'nullable|string|max:2000',
        ]);

        $session = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->findOrFail($id);

        $hint = $this->service->getHint(
            session: $session,
            questionId: $request->question_id,
            partialAnswer: $request->partial_answer ?? '',
        );

        return response()->json([
            'hint'       => $hint,
            'hints_used' => $session->fresh()->hints_used,
        ]);
    }

    // Terminer la session
    public function complete(Request $request, string $id): JsonResponse
    {
        $session = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'in_progress')
            ->findOrFail($id);

        $completed = $this->service->completeSession($session);

        return response()->json([
            'message'    => 'Session terminée.',
            'score'      => $completed->score_total,
            'xp_earned'  => $completed->xp_earned,
            'session_id' => $completed->id,
        ]);
    }

    // Rapport complet
    public function report(Request $request, string $id): JsonResponse
    {
        $session = InterviewSession::where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->with(['responses.question'])
            ->findOrFail($id);

        return response()->json([
            'report' => new InterviewReportResource($session),
        ]);
    }

    // Détail d'une session (reprise ou consultation)
    public function show(Request $request, string $id): JsonResponse
    {
        $session = InterviewSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'session' => new InterviewSessionResource($session),
        ]);
    }

    // Historique
    public function index(Request $request): JsonResponse
    {
        $sessions = $this->service->getHistory($request->user(), $request->all());

        return response()->json([
            'data' => InterviewSessionResource::collection($sessions),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }
}
