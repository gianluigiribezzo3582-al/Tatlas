export type LatLng = { lat: number; lng: number };

export type Address = {
  street: string | null;
  housenumber: string | null;
  postcode: string | null;
  city: string | null;
  country_code: string | null;
};

export type Presence = {
  type: 'resident' | 'guest';
  starts_at: string | null;
  ends_at: string | null;
};

export type Style = {
  slug: string;
  name: string;
  name_it: string;
  name_en: string;
  weight?: number;
  children?: Style[];
};

export type StudioSummary = {
  id: number;
  slug: string;
  name: string;
  location: LatLng;
  address: Address;
  distance_m?: number;
  artists_count?: number;
  has_upcoming_guest?: boolean;
  source: 'osm' | 'user';
};

export type Work = {
  id: number;
  image_url: string;
  thumbnail_url: string;
  width: number | null;
  height: number | null;
  description: string | null;
};

export type Artist = {
  id: number;
  slug: string;
  name: string;
  bio: string | null;
  instagram_url: string | null;
  website: string | null;
  languages: string[];
  accepts_walk_in: boolean;
  price_range: number | null;
  is_verified: boolean;
  distance_m?: number;
  styles?: Style[];
  works?: Work[];
  presence?: Presence;
  studios?: (StudioSummary & { presence: Presence })[];
  public_url: string;
};

export type StudioDetail = StudioSummary & {
  opening_hours: string | null;
  phone: string | null;
  email: string | null;
  website: string | null;
  instagram_url: string | null;
  artists: Artist[];
  attribution: { text: string; url: string; osm_url: string } | null;
};

export type Collection<T> = { data: T[]; meta?: { count: number; attribution?: string } };
export type Item<T> = { data: T };
