<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\SaveProfileRequest;
use App\Services\Marketplace\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedProfileController extends Controller
{
    public function __construct(private ContactService $service) {}

    public function index(Request $request): JsonResponse
    {
        $saved = $this->service->getSaved($request->user());
        return response()->json(['data' => $saved]);
    }

    public function store(SaveProfileRequest $request, string $developerId): JsonResponse
    {
        $this->service->saveProfile($request->user(), $developerId, $request->note);
        return response()->json(['message' => 'Profil sauvegardé.'], 201);
    }

    public function destroy(Request $request, string $developerId): JsonResponse
    {
        $this->service->unsaveProfile($request->user(), $developerId);
        return response()->json(['message' => 'Profil retiré des favoris.']);
    }
}
