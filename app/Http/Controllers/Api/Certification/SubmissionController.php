<?php

namespace App\Http\Controllers\Api\Certification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Certification\SubmitProjectRequest;
use App\Http\Resources\CertificationSubmissionResource;
use App\Models\CertificationSubmission;
use App\Services\Certification\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function __construct(private CertificationService $service) {}

    public function submit(SubmitProjectRequest $request, string $id): JsonResponse
    {
        $submission = CertificationSubmission::where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $updated = $this->service->submitProject($submission, $request->validated());

            return response()->json([
                'message'    => 'Projet soumis avec succès. La review IA est en cours (2-5 minutes).',
                'submission' => new CertificationSubmissionResource($updated->load('certification')),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function report(Request $request, string $id): JsonResponse
    {
        $submission = CertificationSubmission::where('user_id', $request->user()->id)
            ->with(['certification', 'badge'])
            ->findOrFail($id);

        return response()->json([
            'submission' => new CertificationSubmissionResource($submission),
        ]);
    }
}
