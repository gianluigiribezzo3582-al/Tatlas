import type { Address, Presence } from '@/api/types';

export function formatDistance(meters: number | undefined | null): string | null {
  if (meters === undefined || meters === null) return null;
  if (meters < 1000) return `${Math.max(10, Math.round(meters / 10) * 10)} m`;
  const km = meters / 1000;
  return `${km < 10 ? km.toFixed(1).replace('.', ',') : Math.round(km)} km`;
}

export function formatAddress(a: Address): string {
  const street = [a.street, a.housenumber].filter(Boolean).join(' ');
  const city = [a.postcode, a.city].filter(Boolean).join(' ');
  return [street, city].filter(Boolean).join(', ');
}

const dateFmt = (iso: string, locale: string) =>
  new Date(`${iso}T12:00:00`).toLocaleDateString(locale, { day: 'numeric', month: 'short' });

export function formatPresenceDates(p: Presence, locale = 'it-IT'): string | null {
  if (!p.starts_at) return null;
  const start = dateFmt(p.starts_at, locale);
  if (!p.ends_at || p.ends_at === p.starts_at) return start;
  return `${start} – ${dateFmt(p.ends_at, locale)}`;
}

export function priceLabel(range: number | null): string | null {
  return range ? '€'.repeat(range) : null;
}
