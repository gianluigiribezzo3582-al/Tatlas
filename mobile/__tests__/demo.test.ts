import type { Bounds } from '@/api/endpoints';
import { fetchStudios } from '@/api/source';
import { isDemoMode, loadDemoMode, setDemoMode } from '@/config/mode';
import { demo } from '@/demo/dataset';
import { demoImages, imageSource } from '@/demo/images';
import {
  demoFetchArtist,
  demoFetchStudio,
  demoFetchStudios,
  demoFetchStyles,
  haversine,
} from '@/demo/source';

const BARI_PROVINCE: Bounds = [16.25, 40.72, 17.53, 41.32];
const BARI_CITY: Bounds = [16.84, 41.08, 16.92, 41.14];

it('generates the same fictional dataset every time', () => {
  expect(demo.studios).toHaveLength(14);
  expect(demo.artists).toHaveLength(32);
  expect(new Set(demo.artists.map((a) => a.slug)).size).toBe(32);
  expect(demo.artists.every((a) => a.slug.startsWith('demo-'))).toBe(true);
});

it('filters studios by bbox and sorts by distance from the origin', () => {
  const all = demoFetchStudios(BARI_PROVINCE, {});
  const city = demoFetchStudios(BARI_CITY, {}, { lat: 41.1236, lng: 16.8701 });

  expect(all.data.length).toBe(14);
  expect(city.data.length).toBeLessThan(all.data.length);
  expect(city.data[0].name).toBe('Demo Studio Levante');
  const distances = city.data.map((s) => s.distance_m ?? 0);
  expect([...distances].sort((a, b) => a - b)).toEqual(distances);
});

it('applies the style filter including sub-styles', () => {
  const blackwork = demoFetchStudios(BARI_PROVINCE, { style: 'blackwork' }).data;
  const family = new Set(['blackwork', 'dotwork', 'geometric', 'ornamental', 'tribal']);

  for (const studio of blackwork) {
    const detail = demoFetchStudio(studio.id).data;
    expect(detail.artists.some((a) => a.styles?.some((s) => family.has(s.slug)))).toBe(true);
  }
});

it('shows only studios with upcoming guest spots when asked', () => {
  const rows = demoFetchStudios(BARI_PROVINCE, { guestUpcoming: true }).data;

  expect(rows.length).toBeGreaterThan(0);
  expect(rows.every((s) => s.has_upcoming_guest)).toBe(true);
});

it('builds studio and artist details with dates relative to today', () => {
  const today = new Date('2026-12-01T10:00:00Z');
  const withGuest = demo.artists.find((a) => a.presences.some((p) => p.type === 'guest'))!;
  const artist = demoFetchArtist(withGuest.slug, today).data;

  expect(artist.works?.length).toBeGreaterThan(0);
  expect(artist.works?.[0].image_url).toMatch(/^demo-image:\/\//);
  const studio = demoFetchStudio(artist.studios![0].id, today).data;
  expect(studio.artists[0].presence?.type).toBe('resident');
});

it('localizes demo styles', () => {
  const roots = demoFetchStyles().data;
  expect(roots.find((s) => s.slug === 'watercolor')?.name).toBe('Acquerello');
  expect(roots.find((s) => s.slug === 'blackwork')?.children?.map((c) => c.slug)).toContain(
    'dotwork',
  );
});

it('computes great-circle distances', () => {
  // Bari → Monopoli is roughly 40 km
  expect(haversine({ lat: 41.1171, lng: 16.8719 }, { lat: 40.9497, lng: 17.3027 })).toBeGreaterThan(
    35_000,
  );
  expect(haversine({ lat: 41.1171, lng: 16.8719 }, { lat: 41.1171, lng: 16.8719 })).toBe(0);
});

it('resolves bundled demo images and remote URLs', () => {
  expect(imageSource('demo-image://3')).toBe(demoImages[3]);
  expect(imageSource('http://x.test/a.jpg')).toEqual({ uri: 'http://x.test/a.jpg' });
});

it('routes data access to the demo source when demo mode is on', async () => {
  const fetchSpy = jest.spyOn(globalThis, 'fetch');
  await setDemoMode(true);
  expect(isDemoMode()).toBe(true);
  expect(await loadDemoMode()).toBe(true);

  const res = await fetchStudios(BARI_PROVINCE, {});

  expect(res.data.length).toBe(14);
  expect(fetchSpy).not.toHaveBeenCalled();
  await setDemoMode(false);
  fetchSpy.mockRestore();
});
