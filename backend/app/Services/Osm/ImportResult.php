<?php

namespace App\Services\Osm;

final class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $unchanged = 0;

    public int $skipped = 0;

    public int $missing = 0;

    public int $restored = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
