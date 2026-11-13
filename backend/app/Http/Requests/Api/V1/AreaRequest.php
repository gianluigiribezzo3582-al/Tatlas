<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Search\AreaFilter;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared query parameters for "things on the map":
 * - bbox=west,south,east,north (GeoJSON order, like MapLibre bounds), or
 * - lat & lng & radius (metres) — lat/lng alone only sorts by distance;
 * - style=<slug> (includes sub-styles), guest_upcoming=1, limit.
 */
class AreaRequest extends FormRequest
{
    public const MAX_RADIUS_M = 100_000;

    public const MAX_LIMIT = 1000;

    public function rules(): array
    {
        return [
            'bbox' => ['nullable', 'string', 'regex:/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/'],
            'lat' => ['nullable', 'required_with:lng,radius', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat,radius', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:'.self::MAX_RADIUS_M],
            'style' => ['nullable', 'string', 'exists:styles,slug'],
            'guest_upcoming' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if (! $this->filled('bbox') && ! $this->filled('radius')) {
                $validator->errors()->add('bbox', 'Specify bbox or lat/lng/radius.');

                return;
            }
            if ($this->filled('bbox')) {
                [$w, $s, $e, $n] = array_map('floatval', explode(',', $this->string('bbox')));
                if ($w >= $e || $s >= $n || $s < -90 || $n > 90 || $w < -180 || $e > 180) {
                    $validator->errors()->add('bbox', 'Invalid bbox: expected west,south,east,north.');
                }
            }
        });
    }

    public function filter(): AreaFilter
    {
        $bbox = $this->filled('bbox')
            ? array_map('floatval', explode(',', $this->string('bbox')))
            : null;

        return new AreaFilter(
            bbox: $bbox,
            lat: $this->filled('lat') ? (float) $this->input('lat') : null,
            lng: $this->filled('lng') ? (float) $this->input('lng') : null,
            radius: $this->filled('radius') ? (int) $this->input('radius') : null,
            style: $this->input('style'),
            guestUpcoming: $this->boolean('guest_upcoming'),
            limit: (int) $this->input('limit', 300),
        );
    }
}
