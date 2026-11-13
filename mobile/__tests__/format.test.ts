import { studiosToGeoJSON } from '@/features/map/geojson';
import { formatAddress, formatDistance, formatPresenceDates, priceLabel } from '@/lib/format';

it('formats distances in metres and kilometres', () => {
  expect(formatDistance(undefined)).toBeNull();
  expect(formatDistance(3)).toBe('10 m');
  expect(formatDistance(884)).toBe('880 m');
  expect(formatDistance(2345)).toBe('2,3 km');
  expect(formatDistance(23456)).toBe('23 km');
});

it('formats addresses skipping missing parts', () => {
  expect(
    formatAddress({
      street: 'Via Sparano',
      housenumber: '12',
      postcode: '70121',
      city: 'Bari',
      country_code: 'IT',
    }),
  ).toBe('Via Sparano 12, 70121 Bari');
  expect(
    formatAddress({
      street: null,
      housenumber: null,
      postcode: null,
      city: 'Bari',
      country_code: null,
    }),
  ).toBe('Bari');
});

it('formats guest spot date ranges', () => {
  expect(formatPresenceDates({ type: 'resident', starts_at: null, ends_at: null })).toBeNull();
  const range = formatPresenceDates({
    type: 'guest',
    starts_at: '2026-12-10',
    ends_at: '2026-12-12',
  });
  expect(range).toMatch(/10.*–.*12/);
});

it('renders price ranges as euro signs', () => {
  expect(priceLabel(null)).toBeNull();
  expect(priceLabel(2)).toBe('€€');
});

it('converts studios to GeoJSON points (lng, lat order)', () => {
  const fc = studiosToGeoJSON([
    {
      id: 7,
      slug: 's',
      name: 'Studio',
      location: { lat: 41.1, lng: 16.8 },
      address: { street: null, housenumber: null, postcode: null, city: null, country_code: null },
      has_upcoming_guest: true,
      artists_count: 2,
      source: 'osm',
    },
  ]);
  expect(fc.features[0].geometry.coordinates).toEqual([16.8, 41.1]);
  expect(fc.features[0].properties).toEqual({ id: 7, name: 'Studio', guest: true, artists: 2 });
});
