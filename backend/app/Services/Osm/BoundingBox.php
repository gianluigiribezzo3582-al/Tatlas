<?php

namespace App\Services\Osm;

use InvalidArgumentException;

/**
 * Geographic bounding box in Overpass order: south, west, north, east.
 */
final readonly class BoundingBox
{
    public function __construct(
        public float $south,
        public float $west,
        public float $north,
        public float $east,
    ) {
        if ($south < -90 || $north > 90 || $west < -180 || $east > 180) {
            throw new InvalidArgumentException('Bounding box out of range.');
        }
        if ($south >= $north || $west >= $east) {
            throw new InvalidArgumentException('Bounding box must satisfy south < north and west < east.');
        }
    }

    /** Parses "south,west,north,east". */
    public static function fromString(string $value): self
    {
        $parts = array_map('trim', explode(',', $value));
        if (count($parts) !== 4 || array_filter($parts, fn ($p) => ! is_numeric($p)) !== []) {
            throw new InvalidArgumentException("Invalid bounding box \"{$value}\": expected south,west,north,east.");
        }

        return new self(...array_map('floatval', $parts));
    }

    public function toOverpass(): string
    {
        return "{$this->south},{$this->west},{$this->north},{$this->east}";
    }
}
