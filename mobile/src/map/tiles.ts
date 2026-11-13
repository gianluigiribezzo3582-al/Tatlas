/**
 * Map tile source abstraction. Phase 1 uses OpenFreeMap (free, no key);
 * switching to self-hosted Protomaps (.pmtiles on R2) only changes this file.
 */
export type TileSource = {
  id: 'openfreemap' | 'protomaps';
  styleUrl: string;
  attribution: string;
};

export const openFreeMap: TileSource = {
  id: 'openfreemap',
  styleUrl: 'https://tiles.openfreemap.org/styles/positron',
  attribution: '© OpenFreeMap © OpenMapTiles © OpenStreetMap contributors',
};

export const tileSource: TileSource = openFreeMap;

/** Fallback viewport when location is unavailable: Bari. */
export const DEFAULT_CENTER: [number, number] = [16.8719, 41.1171];
export const DEFAULT_ZOOM = 12;
