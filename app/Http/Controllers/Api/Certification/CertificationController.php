<?php

namespace App\Http\Controllers\Api\Certification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certification\StartCertificationRequest;
use App\Http\Resources\CertificationResource;
use App\Http\Resources\CertificationSubmissionResource;
use App\Models\Certification;
use App\Services\Certification\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificationController extends Controller
{
    public function __construct(private CertificationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $certs = $this->service->list($request->only(['category', 'level']));

        return response()->json([
            'data' => CertificationResource::collection($certs),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $cert = Certification::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'certification' => new CertificationResource($cert),
        ]);
    }

    public function start(StartCertificationRequest $request, string $slug): JsonResponse
    {
        $cert = Certification::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $submission = $this->service->startCertification($request->user(), $cert);

            return response()->json([
                'message'    => 'Certification démarrée. Vous avez ' . $cert->duration_days . ' jours pour soumettre.',
                'submission' => new CertificationSubmissionResource($submission->load('certification')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function mySubmissions(Request $request): JsonResponse
    {
        $submissions = $this->service->getMySubmissions($request->user());

        return response()->json([
            'data' => CertificationSubmissionResource::collection($submissions),
        ]);
    }
}
