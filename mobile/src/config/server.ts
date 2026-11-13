import AsyncStorage from '@react-native-async-storage/async-storage';

import { env } from './env';

/**
 * API base URL, changeable at runtime from the Settings screen so a test APK can
 * point to a backend on the local network without rebuilding.
 */
const KEY = 'tatlas.apiUrl';
let current = env.apiUrl;

export function getApiUrl(): string {
  return current;
}

export function normalizeApiUrl(input: string): string | null {
  let value = input.trim().replace(/\/+$/, '');
  if (value === '') return null;
  if (!/^https?:\/\//i.test(value)) value = `http://${value}`;
  try {
    const url = new URL(value);
    if (!url.pathname.endsWith('/api/v1')) {
      url.pathname = `${url.pathname.replace(/\/+$/, '')}/api/v1`;
    }
    return url.toString().replace(/\/+$/, '');
  } catch {
    return null;
  }
}

export async function loadApiUrl(): Promise<string> {
  try {
    const stored = await AsyncStorage.getItem(KEY);
    if (stored) current = stored;
  } catch {
    // keep default
  }
  return current;
}

export async function saveApiUrl(url: string | null): Promise<string> {
  current = url ?? env.apiUrl;
  try {
    if (url) await AsyncStorage.setItem(KEY, url);
    else await AsyncStorage.removeItem(KEY);
  } catch {
    // non-fatal: the value still applies to this session
  }
  return current;
}
