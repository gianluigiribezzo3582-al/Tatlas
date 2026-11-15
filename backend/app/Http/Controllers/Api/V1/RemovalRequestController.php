<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RemovalRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GDPR: anyone (no account needed) can ask for a studio, artist or work to be removed.
 */
class RemovalRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:120'],
            'requester_email' => ['required', 'email', 'max:255'],
            'subject_type' => ['nullable', Rule::in(['studio', 'artist', 'work'])],
            'subject_id' => ['nullable', 'required_with:subject_type', 'integer'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if (isset($data['subject_type'])) {
            $class = Relation::getMorphedModel($data['subject_type']);
            abort_unless($class !== null && $class::whereKey($data['subject_id'])->exists(), 422, 'Unknown subject.');
        }

        $removal = RemovalRequest::create($data);

        return response()->json(['data' => ['id' => $removal->id, 'status' => 'pending']], 201);
    }
}
