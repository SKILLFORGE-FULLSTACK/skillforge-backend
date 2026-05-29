<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\SearchDeveloperRequest;
use App\Http\Resources\DeveloperFullResource;
use App\Http\Resources\DeveloperResource;
use App\Services\Marketplace\DeveloperSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeveloperSearchController extends Controller
{
    public function __construct(private DeveloperSearchService $service) {}

    public function index(SearchDeveloperRequest $request): JsonResponse
    {
        $developers = $this->service->search($request->validated());

        return response()->json([
            'data' => DeveloperResource::collection($developers),
            'meta' => [
                'current_page' => $developers->currentPage(),
                'last_page'    => $developers->lastPage(),
                'total'        => $developers->total(),
                'per_page'     => $developers->perPage(),
            ],
        ]);
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $developer = $this->service->findByIdentifier($identifier);

        $resource = new DeveloperFullResource($developer);
        $resource->isSaved = $request->user()?->isRecruiter()
            ? $this->service->isSavedByRecruiter($developer->id, $request->user()->id)
            : false;

        return response()->json(['developer' => $resource]);
    }
}
