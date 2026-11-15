import type { Bounds, StudioFilters } from '@/api/endpoints';
import type {
  Artist,
  Collection,
  Item,
  Presence,
  StudioDetail,
  StudioSummary,
  Style,
} from '@/api/types';
import { env } from '@/config/env';
import i18n from '@/i18n';

import {
  demo,
  type DemoArtist,
  type DemoPresence,
  type DemoStudio,
  demoStyleRoots,
  demoStyles,
} from './dataset';
import { DEMO_IMAGE_SCHEME } from './images';

/**
 * Local implementation of the API used in demo mode. Mirrors the backend rules:
 * bbox filter, distance sort, style filter with sub-styles, guest spots that are
 * ongoing or start within 60 days, past guest spots hidden.
 */

const GUEST_HORIZON_DAYS = 60;
const DAY = 86_400_000;

const lang = () => (i18n.language === 'en' ? 'en' : 'it');

export function haversine(
  a: { lat: number; lng: number },
  b: { lat: number; lng: number },
): number {
  const R = 6_371_000;
  const toRad = (d: number) => (d * Math.PI) / 180;
  const dLat = toRad(b.lat - a.lat);
  const dLng = toRad(b.lng - a.lng);
  const h =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(h));
}

const isoDate = (offsetDays: number, today: Date) =>
  new Date(today.getTime() + offsetDays * DAY).toISOString().slice(0, 10);

function presenceOf(p: DemoPresence, today: Date): Presence {
  if (p.type === 'resident') return { type: 'resident', starts_at: null, ends_at: null };
  return {
    type: 'guest',
    starts_at: isoDate(p.startOffset ?? 0, today),
    ends_at: isoDate((p.startOffset ?? 0) + (p.days ?? 1), today),
  };
}

/** Resident, or guest that has not ended yet. */
const isCurrent = (p: DemoPresence) =>
  p.type === 'resident' || (p.startOffset ?? 0) + (p.days ?? 1) >= 0;

/** Guest ongoing or starting within the horizon. */
const isUpcomingGuest = (p: DemoPresence) =>
  p.type === 'guest' && isCurrent(p) && (p.startOffset ?? 0) <= GUEST_HORIZON_DAYS;

function styleWithDescendants(slug: string): Set<string> {
  return new Set([slug, ...demoStyles.filter((s) => s.parent === slug).map((s) => s.slug)]);
}

function toStyle(slug: string, weight?: number): Style {
  const s = demoStyles.find((x) => x.slug === slug)!;
  return {
    slug,
    name: lang() === 'en' ? s.name_en : s.name_it,
    name_it: s.name_it,
    name_en: s.name_en,
    ...(weight !== undefined ? { weight } : {}),
  };
}

function summary(s: DemoStudio, origin?: { lat: number; lng: number } | null): StudioSummary {
  const here = demo.artists.filter((a) =>
    a.presences.some((p) => p.studioId === s.id && isCurrent(p)),
  );
  return {
    id: s.id,
    slug: s.slug,
    name: s.name,
    location: { lat: s.lat, lng: s.lng },
    address: {
      street: s.street,
      housenumber: null,
      postcode: s.postcode,
      city: s.city,
      country_code: 'IT',
    },
    ...(origin ? { distance_m: Math.round(haversine(origin, s)) } : {}),
    artists_count: here.length,
    has_upcoming_guest: demo.artists.some((a) =>
      a.presences.some((p) => p.studioId === s.id && isUpcomingGuest(p)),
    ),
    source: 'user',
  };
}

function artistBase(a: DemoArtist): Artist {
  return {
    id: a.id,
    slug: a.slug,
    name: a.name,
    bio: a.bio,
    instagram_url: null,
    website: null,
    languages: a.languages,
    accepts_walk_in: a.accepts_walk_in,
    price_range: a.price_range,
    is_verified: false,
    styles: a.styles.map((s) => toStyle(s.slug, s.weight)),
    public_url: `${env.publicUrl}/artisti/${a.slug}`,
  };
}

export function demoFetchStudios(
  bounds: Bounds,
  filters: StudioFilters,
  origin?: { lat: number; lng: number } | null,
): Collection<StudioSummary> {
  const [w, s, e, n] = bounds;
  const styleSet = filters.style ? styleWithDescendants(filters.style) : null;

  const rows = demo.studios
    .filter((st) => st.lng >= w && st.lng <= e && st.lat >= s && st.lat <= n)
    .filter((st) => {
      const linked = demo.artists.filter((a) =>
        a.presences.some((p) => p.studioId === st.id && isCurrent(p)),
      );
      if (styleSet && !linked.some((a) => a.styles.some((x) => styleSet.has(x.slug)))) return false;
      if (
        filters.guestUpcoming &&
        !demo.artists.some((a) =>
          a.presences.some((p) => p.studioId === st.id && isUpcomingGuest(p)),
        )
      ) {
        return false;
      }
      return true;
    })
    .map((st) => summary(st, origin));

  rows.sort((a, b) =>
    origin ? (a.distance_m ?? 0) - (b.distance_m ?? 0) : a.name.localeCompare(b.name),
  );

  return { data: rows, meta: { count: rows.length, attribution: 'Dati demo fittizi' } };
}

export function demoFetchStudio(id: number | string, today = new Date()): Item<StudioDetail> {
  const st = demo.studios.find((s) => s.id === Number(id));
  if (!st) throw new Error('Demo studio not found');

  const artists = demo.artists
    .flatMap((a) =>
      a.presences
        .filter((p) => p.studioId === st.id && isCurrent(p))
        .map((p) => ({ ...artistBase(a), presence: presenceOf(p, today) })),
    )
    .sort((a, b) =>
      a.presence.type === b.presence.type
        ? (a.presence.starts_at ?? '').localeCompare(b.presence.starts_at ?? '')
        : a.presence.type === 'resident'
          ? -1
          : 1,
    );

  return {
    data: {
      ...summary(st),
      opening_hours: st.opening_hours,
      phone: null,
      email: null,
      website: null,
      instagram_url: null,
      artists,
      attribution: null,
    },
  };
}

export function demoFetchArtist(slug: string, today = new Date()): Item<Artist> {
  const a = demo.artists.find((x) => x.slug === slug);
  if (!a) throw new Error('Demo artist not found');

  return {
    data: {
      ...artistBase(a),
      works: a.works.map((img, i) => ({
        id: a.id * 100 + i,
        image_url: `${DEMO_IMAGE_SCHEME}${img}`,
        thumbnail_url: `${DEMO_IMAGE_SCHEME}${img}`,
        width: 400,
        height: 400,
        description: 'Immagine dimostrativa',
      })),
      studios: a.presences.filter(isCurrent).map((p) => ({
        ...summary(demo.studios.find((s) => s.id === p.studioId)!),
        presence: presenceOf(p, today),
      })),
    },
  };
}

export function demoFetchStyles(): Collection<Style> {
  return {
    data: demoStyleRoots.map((root) => ({
      ...toStyle(root.slug),
      children: (root.children ?? []).map((c) => toStyle(c.slug)),
    })),
  };
}
