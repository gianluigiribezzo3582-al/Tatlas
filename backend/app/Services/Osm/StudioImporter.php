<?php

namespace App\Services\Osm;

use App\Enums\StudioSource;
use App\Models\Studio;
use App\Support\Geo\Point;
use App\Support\Slugger;
use Illuminate\Support\Facades\DB;

/**
 * Creates/updates studios from Overpass elements. Idempotent on (osm_type, osm_id):
 * - fields listed in a studio's locked_fields (edited by its owner/admin) are never overwritten;
 * - OSM studios inside the bbox that are no longer returned are flagged with
 *   osm_missing_since (never deleted: they may have owners, artists, history).
 */
class StudioImporter
{
    public function __construct(private readonly OsmStudioMapper $mapper) {}

    /**
     * @param  list<array<string, mixed>>  $elements
     */
    public function import(array $elements, BoundingBox $bbox, bool $dryRun = false): ImportResult
    {
        $result = new ImportResult;

        $run = function () use ($elements, $bbox, $result) {
            $seen = [];
            foreach ($elements as $element) {
                $attributes = $this->mapper->map($element);
                if ($attributes === null) {
                    $result->skipped++;

                    continue;
                }
                $seen[$attributes['osm_type'].':'.$attributes['osm_id']] = true;
                $this->upsert($attributes, $result);
            }
            $this->flagMissing($bbox, $seen, $result);
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $run();
            } finally {
                DB::rollBack();
            }
        } else {
            DB::transaction($run);
        }

        return $result;
    }

    /** @param array<string, mixed> $attributes */
    private function upsert(array $attributes, ImportResult $result): void
    {
        $studio = Studio::where('osm_type', $attributes['osm_type'])
            ->where('osm_id', $attributes['osm_id'])
            ->first();

        if ($studio === null) {
            Studio::create($attributes + [
                'slug' => Slugger::unique(Studio::class, $attributes['name'], $attributes['city']),
                'source' => StudioSource::Osm,
                'osm_synced_at' => now(),
            ]);
            $result->created++;

            return;
        }

        $wasMissing = $studio->osm_missing_since !== null;

        foreach (OsmStudioMapper::SYNCED_FIELDS as $field) {
            if (! $studio->isLocked($field) && ! $this->same($studio->{$field}, $attributes[$field])) {
                $studio->{$field} = $attributes[$field];
            }
        }
        if ($studio->osm_tags != $attributes['osm_tags']) { // loose: jsonb reorders keys
            $studio->osm_tags = $attributes['osm_tags'];
        }
        $studio->osm_missing_since = null;

        $changed = $studio->isDirty();
        $studio->osm_synced_at = now();
        $studio->save();

        if ($wasMissing) {
            $result->restored++;
        } elseif ($changed) {
            $result->updated++;
        } else {
            $result->unchanged++;
        }
    }

    /** @param array<string, true> $seen */
    private function flagMissing(BoundingBox $bbox, array $seen, ImportResult $result): void
    {
        Studio::query()
            ->where('source', StudioSource::Osm)
            ->whereNull('osm_missing_since')
            ->whereRaw(
                'ST_Intersects(location, ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography)',
                [$bbox->west, $bbox->south, $bbox->east, $bbox->north],
            )
            ->each(function (Studio $studio) use ($seen, $result) {
                if (! isset($seen[$studio->osm_type.':'.$studio->osm_id])) {
                    $studio->forceFill(['osm_missing_since' => now()])->save();
                    $result->missing++;
                }
            });
    }

    private function same(mixed $current, mixed $new): bool
    {
        if ($current instanceof Point && $new instanceof Point) {
            return abs($current->lat - $new->lat) < 1e-7 && abs($current->lng - $new->lng) < 1e-7;
        }

        return $current === $new;
    }
}
