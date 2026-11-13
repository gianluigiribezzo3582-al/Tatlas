<?php

namespace App\Support\Geo;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable WGS84 point (SRID 4326).
 */
final readonly class Point implements JsonSerializable
{
    public function __construct(
        public float $lat,
        public float $lng,
    ) {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitude out of range: {$lat}");
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitude out of range: {$lng}");
        }
    }

    /** PostGIS accepts EWKT as text input for geography columns. */
    public function toEwkt(): string
    {
        return sprintf('SRID=4326;POINT(%.7F %.7F)', $this->lng, $this->lat);
    }

    /**
     * Parses the hex-encoded (E)WKB that PostgreSQL returns for a geography(Point).
     */
    public static function fromWkbHex(string $hex): self
    {
        $bin = hex2bin($hex);
        if ($bin === false || strlen($bin) < 21) {
            throw new InvalidArgumentException('Invalid WKB point');
        }

        $littleEndian = ord($bin[0]) === 1;
        $type = unpack($littleEndian ? 'V' : 'N', substr($bin, 1, 4))[1];
        $hasSrid = ($type & 0x20000000) !== 0;
        if (($type & 0xFF) !== 1) {
            throw new InvalidArgumentException('WKB geometry is not a point');
        }

        $offset = 5 + ($hasSrid ? 4 : 0);
        $coords = substr($bin, $offset, 16);
        $format = $littleEndian ? 'ex/ey' : 'Ex/Ey';
        ['x' => $x, 'y' => $y] = unpack($format, $coords);

        return new self(lat: $y, lng: $x);
    }

    /** @return array{lat: float, lng: float} */
    public function jsonSerialize(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }
}
