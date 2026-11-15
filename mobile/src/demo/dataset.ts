import styleTree from './styles.json';
import { seeded } from './random';

/*
 * Fictional demo dataset bundled in the app. Names, places and people are invented;
 * coordinates are real towns of the Bari area so the map looks plausible.
 * Guest spot dates are stored as offsets from "today" so they stay upcoming.
 */

export type DemoStyle = { slug: string; name_it: string; name_en: string; parent: string | null };

export type DemoStudio = {
  id: number;
  slug: string;
  name: string;
  lat: number;
  lng: number;
  street: string;
  city: string;
  postcode: string;
  opening_hours: string;
};

export type DemoPresence = {
  studioId: number;
  type: 'resident' | 'guest';
  startOffset?: number; // days from today
  days?: number;
};

export type DemoArtist = {
  id: number;
  slug: string;
  name: string;
  bio: string;
  languages: string[];
  accepts_walk_in: boolean;
  price_range: number;
  styles: { slug: string; weight: number }[];
  presences: DemoPresence[];
  works: number[]; // indexes into bundled demo images
};

type TreeNode = { slug: string; name_it: string; name_en: string; children?: TreeNode[] };

export const demoStyles: DemoStyle[] = (styleTree as TreeNode[]).flatMap((root) => [
  { slug: root.slug, name_it: root.name_it, name_en: root.name_en, parent: null },
  ...(root.children ?? []).map((c) => ({
    slug: c.slug,
    name_it: c.name_it,
    name_en: c.name_en,
    parent: root.slug,
  })),
]);

export const demoStyleRoots = styleTree as TreeNode[];

const PLACES: [string, number, number, string, string][] = [
  ['Levante', 41.1236, 16.8701, 'Bari', '70121'],
  ['Murat', 41.1205, 16.8664, 'Bari', '70122'],
  ['Città Vecchia', 41.1281, 16.8705, 'Bari', '70122'],
  ['Poggiofranco', 41.1035, 16.8667, 'Bari', '70124'],
  ['Japigia', 41.1062, 16.9002, 'Bari', '70126'],
  ['Torre a Mare', 41.0868, 16.9969, 'Bari', '70126'],
  ['Molo', 41.2017, 16.5986, 'Molfetta', '70056'],
  ['Lama', 41.1652, 16.7569, 'Modugno', '70026'],
  ['Ulivo', 41.1093, 16.6911, 'Bitonto', '70032'],
  ['Scogliera', 40.9963, 17.2197, 'Polignano a Mare', '70044'],
  ['Porto', 40.9497, 17.3027, 'Monopoli', '70043'],
  ['Murgia', 40.8279, 16.5519, 'Altamura', '70022'],
  ['Castello', 40.9905, 17.1153, 'Conversano', '70014'],
  ['Stazione', 41.0603, 16.9271, 'Triggiano', '70019'],
];

const STREETS = [
  'Via Roma',
  'Via Dante',
  'Corso Italia',
  'Via Garibaldi',
  'Via Mazzini',
  'Via Verdi',
];
const FIRST = [
  'Alba',
  'Bruno',
  'Chiara',
  'Dario',
  'Elena',
  'Fabio',
  'Giada',
  'Ivo',
  'Luna',
  'Marco',
  'Nina',
  'Oscar',
  'Paola',
  'Rocco',
  'Sara',
  'Tito',
  'Vera',
  'Zeno',
  'Ada',
  'Leo',
];
const SUFFIX = ['Ink', 'Needle', 'Lines', 'Dots', 'Black', 'Art', 'Skin', 'Tattoo'];
const BIOS = [
  'Profilo demo con dati fittizi. Linee pulite e progetti su misura.',
  'Profilo demo con dati fittizi. Amo i lavori grandi e le sessioni lunghe.',
  'Profilo demo con dati fittizi. Piccoli tatuaggi, molta attenzione ai dettagli.',
  'Profilo demo con dati fittizi. Disegni originali, niente copie.',
];

export const DEMO_IMAGE_COUNT = 8;

function build() {
  const r = seeded(2026);

  const studios: DemoStudio[] = PLACES.map(([name, lat, lng, city, postcode], i) => ({
    id: i + 1,
    slug: `demo-studio-${i + 1}`,
    name: `Demo Studio ${name}`,
    lat,
    lng,
    city,
    postcode,
    street: `${r.pick(STREETS)} ${r.int(1, 120)}`,
    opening_hours: r.pick([
      'Tu-Sa 10:00-19:00',
      'Mo-Fr 15:00-21:00',
      'Tu-Sa 10:00-13:00,16:00-20:00',
    ]),
  }));

  const used = new Set<string>();
  const artists: DemoArtist[] = Array.from({ length: 32 }, (_, i) => {
    let name = '';
    do {
      name = `${r.pick(FIRST)} ${r.pick(SUFFIX)}`;
    } while (used.has(name));
    used.add(name);

    const home = r.pick(studios);
    const presences: DemoPresence[] = [{ studioId: home.id, type: 'resident' }];
    if (r.bool(0.4)) {
      const others = studios.filter((s) => s.id !== home.id);
      presences.push({
        studioId: r.pick(others).id,
        type: 'guest',
        startOffset: r.int(-2, 45),
        days: r.int(1, 5),
      });
    }

    const styleCount = r.int(1, 3);
    const styles = new Map<string, number>();
    while (styles.size < styleCount) styles.set(r.pick(demoStyles).slug, r.int(50, 100) / 100);

    return {
      id: i + 1,
      slug: `demo-${name.toLowerCase().replace(/\s+/g, '-')}`,
      name,
      bio: r.pick(BIOS),
      languages: r.pick([['it'], ['it', 'en'], ['it', 'en', 'es']]),
      accepts_walk_in: r.bool(),
      price_range: r.int(1, 3),
      styles: [...styles].map(([slug, weight]) => ({ slug, weight })),
      presences,
      works: Array.from({ length: r.int(2, 6) }, () => r.int(0, DEMO_IMAGE_COUNT - 1)),
    };
  });

  return { studios, artists };
}

export const demo = build();
