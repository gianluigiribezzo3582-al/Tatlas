<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PresenceType;
use App\Http\Controllers\Controller;
use App\Models\ArtistStudio;
use App\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Where the authenticated artist works: resident studios and guest spots.
 */
class MyPresenceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $artist = $request->user()->artistProfile();
        abort_if($artist === null, 409, 'Create your artist profile first.');

        $data = $request->validate([
            'studio_id' => ['required', 'integer', Rule::exists('studios', 'id')->whereNull('hidden_at')],
            'type' => ['required', Rule::enum(PresenceType::class)],
            'starts_at' => ['nullable', 'required_if:type,guest', 'date', 'after_or_equal:today', 'before:+2 years'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($data['type'] === PresenceType::Resident->value) {
            $exists = $artist->studios()->wherePivot('type', 'resident')->whereKey($data['studio_id'])->exists();
            abort_if($exists, 409, 'You are already resident in this studio.');
            $data['starts_at'] = $data['ends_at'] = null;
        }

        $artist->studios()->attach(Studio::findOrFail($data['studio_id']), [
            'type' => $data['type'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ]);

        return response()->json(['data' => $this->presences($request)], 201);
    }

    public function destroy(Request $request, ArtistStudio $presence): JsonResponse
    {
        $artist = $request->user()->artistProfile();
        abort_unless($artist !== null && $presence->artist_id === $artist->id, 404);

        $presence->delete();

        return response()->json(['data' => $this->presences($request)]);
    }

    /** @return list<array<string, mixed>> */
    private function presences(Request $request): array
    {
        return $request->user()->artistProfile()->studios()->get()->map(fn (Studio $s) => [
            'presence_id' => $s->pivot->id,
            'studio' => ['id' => $s->id, 'name' => $s->name, 'city' => $s->city],
            'type' => $s->pivot->type->value,
            'starts_at' => $s->pivot->starts_at?->toDateString(),
            'ends_at' => $s->pivot->ends_at?->toDateString(),
        ])->values()->all();
    }
}
