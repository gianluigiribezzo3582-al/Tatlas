import type { StudioSummary } from '@/api/types';

export type StudioFeatureProps = {
  id: number;
  name: string;
  guest: boolean;
  artists: number;
};

export function studiosToGeoJSON(
  studios: StudioSummary[],
): GeoJSON.FeatureCollection<GeoJSON.Point, StudioFeatureProps> {
  return {
    type: 'FeatureCollection',
    features: studios.map((s) => ({
      type: 'Feature',
      id: s.id,
      geometry: { type: 'Point', coordinates: [s.location.lng, s.location.lat] },
      properties: {
        id: s.id,
        name: s.name,
        guest: Boolean(s.has_upcoming_guest),
        artists: s.artists_count ?? 0,
      },
    })),
  };
}
