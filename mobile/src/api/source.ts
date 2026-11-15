import { isDemoMode } from '@/config/mode';
import { demoFetchArtist, demoFetchStudio, demoFetchStudios, demoFetchStyles } from '@/demo/source';

import * as remote from './endpoints';

/**
 * Data access used by the screens: the real API, or bundled demo data in demo mode.
 * Demo calls resolve after a short delay to behave like the network.
 */
const later = <T>(fn: () => T) =>
  new Promise<T>((resolve, reject) =>
    setTimeout(() => {
      try {
        resolve(fn());
      } catch (e) {
        reject(e);
      }
    }, 120),
  );

export const fetchStudios: typeof remote.fetchStudios = (bounds, filters, origin, signal) =>
  isDemoMode()
    ? later(() => demoFetchStudios(bounds, filters, origin))
    : remote.fetchStudios(bounds, filters, origin, signal);

export const fetchStudio: typeof remote.fetchStudio = (id) =>
  isDemoMode() ? later(() => demoFetchStudio(id)) : remote.fetchStudio(id);

export const fetchArtist: typeof remote.fetchArtist = (slug) =>
  isDemoMode() ? later(() => demoFetchArtist(slug)) : remote.fetchArtist(slug);

export const fetchStyles: typeof remote.fetchStyles = () =>
  isDemoMode() ? later(demoFetchStyles) : remote.fetchStyles();
