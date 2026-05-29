<?php

namespace App\Http\Controllers\Api\Certification;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserBadgePublicResource;
use App\Models\UserBadge;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function verify(string $token): JsonResponse
    {
        $badge = UserBadge::where('verify_token', $token)
            ->where('is_public', true)
            ->with(['user:id,name,username,avatar_url', 'certification'])
            ->firstOrFail();

        return response()->json([
            'valid' => ! $badge->isExpired(),
            'badge' => new UserBadgePublicResource($badge),
        ]);
    }
}
