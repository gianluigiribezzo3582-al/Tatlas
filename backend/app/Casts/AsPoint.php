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

        return $value->toWkbHex();
    }
}
