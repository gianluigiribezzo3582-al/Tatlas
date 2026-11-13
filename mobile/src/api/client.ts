import { env } from '@/config/env';

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly body?: unknown,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

type Query = Record<string, string | number | boolean | undefined | null>;

export function buildUrl(path: string, query: Query = {}, base = env.apiUrl): string {
  const url = new URL(base.replace(/\/$/, '') + '/' + path.replace(/^\//, ''));
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, String(value));
    }
  }
  return url.toString();
}

export async function apiGet<T>(path: string, query?: Query, init?: RequestInit): Promise<T> {
  const response = await fetch(buildUrl(path, query), {
    ...init,
    headers: { Accept: 'application/json', ...(init?.headers ?? {}) },
  });
  const body = await response.json().catch(() => undefined);
  if (!response.ok) {
    throw new ApiError(response.status, `GET ${path} failed with ${response.status}`, body);
  }
  return body as T;
}
