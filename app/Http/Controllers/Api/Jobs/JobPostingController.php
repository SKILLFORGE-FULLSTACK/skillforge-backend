<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreJobPostingRequest;
use App\Http\Requests\Marketplace\UpdateJobPostingRequest;
use App\Http\Resources\JobPostingResource;
use App\Services\Marketplace\JobPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    public function __construct(private JobPostingService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();

        // "mine=1" : un recruteur ne voit que ses propres offres (tous statuts).
        // On ignore toute valeur recruiter_id envoyée par le client — seul
        // l'utilisateur authentifié peut être utilisé comme filtre.
        if ($request->boolean('mine')) {
            $filters['recruiter_id'] = $request->user()->id;
        } else {
            unset($filters['recruiter_id']);
        }

        $jobs = $this->service->list($filters);

        return response()->json([
            'data' => JobPostingResource::collection($jobs),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
            ],
        ]);
    }

    public function store(StoreJobPostingRequest $request): JsonResponse
    {
        $job = $this->service->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Offre publiée avec succès.',
            'job' => new JobPostingResource($job),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $job = $this->service->findOrFail($id);

        return response()->json(['job' => new JobPostingResource($job)]);
    }

    public function update(UpdateJobPostingRequest $request, string $id): JsonResponse
    {
        $job = $this->service->update($request->user(), $id, $request->validated());

        return response()->json([
            'message' => 'Offre mise à jour.',
            'job' => new JobPostingResource($job),
        ]);
    }
}
