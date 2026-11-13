import { apiGet } from './client';
import type { Artist, Collection, Item, StudioDetail, StudioSummary, Style } from './types';

export type Bounds = [west: number, south: number, east: number, north: number];

export type StudioFilters = {
  style?: string | null;
  guestUpcoming?: boolean;
};

export function roundBounds(bounds: Bounds): Bounds {
  // ~100 m precision: keeps cache keys stable while panning slightly.
  return bounds.map((v) => Math.round(v * 1000) / 1000) as Bounds;
}

export function fetchStudios(
  bounds: Bounds,
  filters: StudioFilters,
  origin?: { lat: number; lng: number } | null,
  signal?: AbortSignal,
) {
  return apiGet<Collection<StudioSummary>>(
    'studios',
    {
      bbox: roundBounds(bounds).join(','),
      style: filters.style ?? undefined,
      guest_upcoming: filters.guestUpcoming ?? false,
      lat: origin?.lat,
      lng: origin?.lng,
      limit: 500,
    },
    { signal },
  );
}

export const fetchStudio = (id: number | string) => apiGet<Item<StudioDetail>>(`studios/${id}`);

export const fetchArtist = (slug: string) =>
  apiGet<Item<Artist>>(`artists/${encodeURIComponent(slug)}`);

export const fetchStyles = () => apiGet<Collection<Style>>('styles');
