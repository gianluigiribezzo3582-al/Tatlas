<?php

namespace App\Casts;

use App\Support\Geo\Point;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Point, Point>
 */
class AsPoint implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Point
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && str_starts_with($value, 'SRID=')) {
            // Not yet persisted: still in the EWKT form produced by set().
            if (preg_match('/POINT\(([-\d.]+) ([-\d.]+)\)/', $value, $m) !== 1) {
                throw new InvalidArgumentException('Invalid EWKT point');
            }

            return new Point(lat: (float) $m[2], lng: (float) $m[1]);
        }

        return Point::fromWkbHex((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Point) {
            throw new InvalidArgumentException("{$key} must be a ".Point::class);
        }

        return $value->toEwkt();
    }
}
