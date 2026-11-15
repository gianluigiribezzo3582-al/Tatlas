<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WorkResource;
use App\Models\ClaimRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /** GDPR art. 20: everything we hold about the user, as JSON. */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $artist = $user->artistProfile()?->load(['styles', 'studios', 'works']);

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'role', 'email_verified_at', 'created_at']),
            'artist_profile' => $artist ? [
                ...$artist->only(['name', 'slug', 'bio', 'instagram_url', 'website', 'languages', 'accepts_walk_in', 'price_range', 'is_verified', 'created_at']),
                'styles' => $artist->styles->pluck('slug'),
                'presences' => $artist->studios->map(fn ($s) => [
                    'studio' => $s->name, 'type' => $s->pivot->type->value,
                    'starts_at' => $s->pivot->starts_at?->toDateString(), 'ends_at' => $s->pivot->ends_at?->toDateString(),
                ]),
                'works' => $artist->works->map(fn ($w) => [
                    'image_url' => WorkResource::url($w->image_path),
                    'description' => $w->description, 'created_at' => $w->created_at,
                ]),
            ] : null,
            'claims' => ClaimRequest::where('user_id', $user->id)->get(['claimable_type', 'claimable_id', 'status', 'message', 'created_at']),
        ]);
    }

    public function destroy(Request $request, DeleteAccount $delete): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        $delete($request->user());

        return response()->json(status: 204);
    }
}
