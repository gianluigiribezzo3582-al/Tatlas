<?php

namespace App\Services\Osm;

use App\Support\Geo\Point;

/**
 * Maps a raw Overpass element (node/way/relation with "out center") to studio attributes.
 */
class OsmStudioMapper
{
    /** Studio attributes owned by OSM (can be overwritten on resync unless locked). */
    public const SYNCED_FIELDS = [
        'name', 'location', 'street', 'housenumber', 'postcode', 'city', 'country_code',
        'opening_hours', 'phone', 'email', 'website', 'instagram_url',
    ];

    /**
     * @param  array<string, mixed>  $element
     * @return array<string, mixed>|null null when the element cannot become a studio
     */
    public function map(array $element): ?array
    {
        $type = $element['type'] ?? null;
        $id = $element['id'] ?? null;
        $tags = $element['tags'] ?? [];

        if (! in_array($type, ['node', 'way', 'relation'], true) || ! is_int($id) || ! is_array($tags)) {
            return null;
        }

        $name = $this->clean($tags['name'] ?? $tags['brand'] ?? null);
        $point = $this->point($element);

        if ($name === null || $point === null) {
            return null;
        }

        $country = $this->clean($tags['addr:country'] ?? null);

        return [
            'osm_type' => $type,
            'osm_id' => $id,
            'name' => $name,
            'location' => $point,
            'street' => $this->clean($tags['addr:street'] ?? null),
            'housenumber' => $this->clean($tags['addr:housenumber'] ?? null),
            'postcode' => $this->clean($tags['addr:postcode'] ?? null),
            'city' => $this->clean($tags['addr:city'] ?? null),
            'country_code' => $country !== null && strlen($country) === 2 ? strtoupper($country) : null,
            'opening_hours' => $this->clean($tags['opening_hours'] ?? null),
            'phone' => $this->clean($tags['phone'] ?? $tags['contact:phone'] ?? $tags['contact:mobile'] ?? null),
            'email' => $this->clean($tags['email'] ?? $tags['contact:email'] ?? null),
            'website' => $this->url($tags['website'] ?? $tags['contact:website'] ?? $tags['url'] ?? null),
            'instagram_url' => $this->instagram($tags['contact:instagram'] ?? $tags['instagram'] ?? null),
            'osm_tags' => $tags,
        ];
    }

    /** @param array<string, mixed> $element */
    private function point(array $element): ?Point
    {
        $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
        $lng = $element['lon'] ?? $element['center']['lon'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return new Point(lat: (float) $lat, lng: (float) $lng);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : $value;
    }

    private function url(mixed $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }
        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    /** Accepts "@handle", "handle" or a full instagram.com URL. */
    private function instagram(mixed $value): ?string
    {
        $value = $this->clean($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('#instagram\.com/([A-Za-z0-9._]+)#i', $value, $m)) {
            $handle = $m[1];
        } else {
            $handle = ltrim($value, '@');
        }

        return preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle)
            ? 'https://www.instagram.com/'.$handle
            : null;
    }
}
