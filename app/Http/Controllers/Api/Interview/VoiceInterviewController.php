<?php

namespace App\Http\Controllers\Api\Interview;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\StartVoiceInterviewRequest;
use App\Http\Requests\Interview\VoiceInterviewTurnRequest;
use App\Http\Resources\InterviewRoomResource;
use App\Http\Resources\InterviewTurnResource;
use App\Models\InterviewRoom;
use App\Services\Interview\VoiceInterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceInterviewController extends Controller
{
    public function __construct(private VoiceInterviewService $service) {}

    // Démarrer une salle (practice libre ou basée sur une offre)
    public function start(StartVoiceInterviewRequest $request): JsonResponse
    {
        $result = $this->service->start(
            user: $request->user(),
            jobPostingId: $request->validated('job_posting_id'),
            language: $request->validated('language') ?? 'fr',
        );

        return response()->json([
            'room' => new InterviewRoomResource($result['room']),
            'turn' => new InterviewTurnResource($result['turn']->load('participant')),
        ], 201);
    }

    // Soumettre l'audio du candidat pour un tour, recevoir la relance IA
    public function turn(VoiceInterviewTurnRequest $request, string $id): JsonResponse
    {
        $room = InterviewRoom::where('created_by', $request->user()->id)->findOrFail($id);

        $result = $this->service->submitTurn($room, $request->file('audio'));

        return response()->json([
            'candidate_turn' => new InterviewTurnResource($result['candidate_turn']->load('participant')),
            'ai_turn' => new InterviewTurnResource($result['ai_turn']->load('participant')),
            'is_final' => $result['is_final'],
            'progress' => $result['progress'],
        ]);
    }

    // Terminer la salle et générer le rapport final
    public function complete(Request $request, string $id): JsonResponse
    {
        $room = InterviewRoom::where('created_by', $request->user()->id)->findOrFail($id);

        $room = $this->service->complete($room);

        return response()->json([
            'room' => new InterviewRoomResource($room),
        ]);
    }

    // Détail d'une salle (reprise ou consultation du rapport)
    public function show(Request $request, string $id): JsonResponse
    {
        $room = InterviewRoom::where('created_by', $request->user()->id)
            ->with(['turns.participant', 'jobPosting.recruiter.recruiterProfile'])
            ->findOrFail($id);

        return response()->json([
            'room' => new InterviewRoomResource($room),
        ]);
    }

    // Historique des entretiens vocaux
    public function index(Request $request): JsonResponse
    {
        $rooms = InterviewRoom::where('created_by', $request->user()->id)
            ->with('jobPosting.recruiter.recruiterProfile')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => InterviewRoomResource::collection($rooms),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'total' => $rooms->total(),
                'per_page' => $rooms->perPage(),
            ],
        ]);
    }
}
