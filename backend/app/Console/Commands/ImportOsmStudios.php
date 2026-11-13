<?php

namespace App\Console\Commands;

use App\Services\Osm\BoundingBox;
use App\Services\Osm\OverpassClient;
use App\Services\Osm\StudioImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;

class ImportOsmStudios extends Command
{
    protected $signature = 'tatlas:import-osm
        {--bbox= : south,west,north,east (default: config osm.default_bbox)}
        {--dry-run : Fetch and map, but roll back all database changes}';

    protected $description = 'Import or refresh tattoo studios (shop=tattoo) from OpenStreetMap via Overpass';

    public function handle(OverpassClient $client, StudioImporter $importer): int
    {
        try {
            $bbox = BoundingBox::fromString($this->option('bbox') ?: config('osm.default_bbox'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        }

        $this->info("Querying Overpass for shop=tattoo in {$bbox->toOverpass()}…");

        try {
            $elements = $client->tattooShops($bbox);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $result = $importer->import($elements, $bbox, dryRun: (bool) $this->option('dry-run'));

        $this->table(['created', 'updated', 'unchanged', 'restored', 'skipped', 'missing'], [[
            $result->created, $result->updated, $result->unchanged,
            $result->restored, $result->skipped, $result->missing,
        ]]);

        if ($this->option('dry-run')) {
            $this->warn('Dry run: no changes were saved.');
        }
        $this->line('Data © OpenStreetMap contributors, ODbL 1.0 — https://www.openstreetmap.org/copyright');

        return self::SUCCESS;
    }
}
