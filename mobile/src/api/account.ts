import { apiSend } from './client';
import type { Artist, Item, StudioSummary, Work } from './types';

export type User = {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'artist' | 'admin';
  email_verified: boolean;
  artist_slug: string | null;
};

export type AuthResponse = { token: string; user: User };

export type MyArtist = {
  data: Artist | null;
  meta?: { is_public: boolean; pending_verification: boolean; works_limit: number };
};

export type MyPresence = {
  presence_id: number;
  studio: { id: number; name: string; city: string | null };
  type: 'resident' | 'guest';
  starts_at: string | null;
  ends_at: string | null;
};

export type ArtistInput = {
  name?: string;
  bio?: string | null;
  instagram_url?: string | null;
  website?: string | null;
  languages?: string[];
  accepts_walk_in?: boolean;
  price_range?: number | null;
  styles?: string[];
};

const device = 'tatlas-android';

export const login = (email: string, password: string) =>
  apiSend<AuthResponse>('POST', 'auth/login', { email, password, device_name: device });

export const register = (input: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: 'user' | 'artist';
  accept_privacy: boolean;
}) => apiSend<AuthResponse>('POST', 'auth/register', { ...input, device_name: device });

export const logout = () => apiSend<void>('POST', 'auth/logout');
export const me = () => apiSend<{ user: User }>('GET', 'me');

export const getMyArtist = () => apiSend<MyArtist>('GET', 'me/artist');
export const createMyArtist = (input: ArtistInput) => apiSend<MyArtist>('POST', 'me/artist', input);
export const updateMyArtist = (input: ArtistInput) =>
  apiSend<MyArtist>('PATCH', 'me/artist', input);

export const addPresence = (input: {
  studio_id: number;
  type: 'resident' | 'guest';
  starts_at?: string;
  ends_at?: string;
}) => apiSend<{ data: MyPresence[] }>('POST', 'me/presences', input);
export const removePresence = (id: number) =>
  apiSend<{ data: MyPresence[] }>('DELETE', `me/presences/${id}`);

export const searchStudios = (q: string, origin?: { lat: number; lng: number } | null) =>
  apiSend<{ data: StudioSummary[] }>(
    'GET',
    `studios/search?q=${encodeURIComponent(q)}${origin ? `&lat=${origin.lat}&lng=${origin.lng}` : ''}`,
  );

export const claimStudio = (studioId: number, message: string, evidenceUrl?: string) =>
  apiSend<{ data: { id: number; status: string } }>('POST', `studios/${studioId}/claim`, {
    message: message || null,
    evidence_url: evidenceUrl || null,
  });

export const myWorks = () => apiSend<{ data: Work[] }>('GET', 'me/works');
export const deleteWork = (id: number) => apiSend<void>('DELETE', `me/works/${id}`);
export const uploadWork = (
  file: { uri: string; name: string },
  description: string,
  styles: string[],
) => {
  const form = new FormData();
  // React Native FormData accepts { uri, name, type } file descriptors.
  form.append('image', { uri: file.uri, name: file.name, type: 'image/jpeg' } as unknown as Blob);
  if (description) form.append('description', description);
  styles.forEach((s, i) => form.append(`styles[${i}]`, s));
  return apiSend<Item<Work>>('POST', 'me/works', form);
};

export const myPresences = () => apiSend<{ data: MyPresence[] }>('GET', 'me/presences');

export const createStudio = (input: {
  name: string;
  street?: string;
  housenumber?: string;
  city: string;
  lat?: number;
  lng?: number;
}) => apiSend<Item<StudioSummary>>('POST', 'studios', input);
