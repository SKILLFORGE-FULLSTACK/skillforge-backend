<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\ContactDeveloperRequest;
use App\Http\Resources\RecruiterContactResource;
use App\Services\Marketplace\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private ContactService $service) {}

    public function store(ContactDeveloperRequest $request): JsonResponse
    {
        try {
            $result = $this->service->contact($request->user(), $request->validated());

            return response()->json([
                'message'           => 'Message envoyé avec succès.',
                'contact_id'        => $result['contact']->id,
                'credits_remaining' => $result['credits_remaining'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $contacts = $this->service->getContacts($request->user());

        return response()->json([
            'data' => RecruiterContactResource::collection($contacts),
        ]);
    }
}
