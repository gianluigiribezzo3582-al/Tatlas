<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ClaimRequest;
use App\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A user asks to manage an existing studio; an admin approves or rejects it.
 */
class ClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = ClaimRequest::where('user_id', $request->user()->id)->latest()->get();

        return response()->json(['data' => $claims->map(fn (ClaimRequest $c) => [
            'id' => $c->id,
            'type' => $c->claimable_type,
            'target_id' => $c->claimable_id,
            'status' => $c->status->value,
            'rejection_reason' => $c->rejection_reason,
            'created_at' => $c->created_at?->toIso8601String(),
        ])]);
    }

    public function storeForStudio(Request $request, Studio $studio): JsonResponse
    {
        abort_if($studio->hidden_at !== null, 404);
        abort_if($studio->claimed_by_user_id === $request->user()->id, 409, 'You already manage this studio.');
        abort_if($studio->claimed_by_user_id !== null, 409, 'This studio is already managed by someone else. Contact us if this is wrong.');

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'evidence_url' => ['nullable', 'url:https', 'max:255'],
        ]);

        $pending = ClaimRequest::where('user_id', $request->user()->id)
            ->where('claimable_type', 'studio')->where('claimable_id', $studio->id)
            ->where('status', RequestStatus::Pending)->exists();
        abort_if($pending, 409, 'You already have a pending claim for this studio.');

        $claim = ClaimRequest::create([
            'user_id' => $request->user()->id,
            'claimable_type' => 'studio',
            'claimable_id' => $studio->id,
            ...$data,
        ]);

        return response()->json(['data' => ['id' => $claim->id, 'status' => 'pending']], 201);
    }
}
