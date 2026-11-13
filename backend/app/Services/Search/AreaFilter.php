<?php

namespace App\Services\Search;

final readonly class AreaFilter
{
    /** Days ahead considered "upcoming" for guest spots. */
    public const GUEST_HORIZON_DAYS = 60;

    /**
     * @param  list<float>|null  $bbox  west, south, east, north
     */
    public function __construct(
        public ?array $bbox = null,
        public ?float $lat = null,
        public ?float $lng = null,
        public ?int $radius = null,
        public ?string $style = null,
        public bool $guestUpcoming = false,
        public int $limit = 300,
    ) {}

    public function hasOrigin(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }
}
