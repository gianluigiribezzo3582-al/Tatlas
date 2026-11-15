<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR erasure: removes the user, their artist profile, portfolio files, presences,
 * styles, claims and tokens. Studios they managed stay (they are businesses, often
 * from OSM) but lose their owner.
 */
class DeleteAccount
{
    public function __invoke(User $user): void
    {
        $artist = $user->artistProfile();
        $files = $artist?->works()->get(['image_path', 'thumbnail_path'])
            ->flatMap(fn ($w) => [$w->image_path, $w->thumbnail_path])->filter()->values()->all() ?? [];

        DB::transaction(function () use ($user, $artist) {
            $artist?->tags()->detach();
            $artist?->delete(); // cascades: works, work_style, artist_style, artist_studio
            $user->tokens()->delete();
            $user->delete();    // cascades: claim_requests, favorites; nulls studio ownership
        });

        if ($files !== []) {
            Storage::disk(config('filesystems.works_disk', 'public'))->delete($files);
        }
    }
}
