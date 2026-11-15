import { getApiUrl } from '@/config/server';

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

export function buildUrl(path: string, query: Query = {}, base = getApiUrl()): string {
  const url = new URL(base.replace(/\/$/, '') + '/' + path.replace(/^\//, ''));
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null && value !== '' && value !== false) {
      url.searchParams.set(key, value === true ? '1' : String(value));
    }
  }
  return url.toString();
}

export async function apiGet<T>(path: string, query?: Query, init?: RequestInit): Promise<T> {
  const response = await fetch(buildUrl(path, query), {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      ...(init?.headers ?? {}),
    },
  });
  const body = await response.json().catch(() => undefined);
  if (!response.ok) {
    throw new ApiError(response.status, `GET ${path} failed with ${response.status}`, body);
  }
  return body as T;
}

let authToken: string | null = null;

export function setAuthToken(token: string | null) {
  authToken = token;
}

/** Laravel validation errors (422) flattened to field → first message. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError)) return {};
  const errors = (error.body as { errors?: Record<string, string[]> } | undefined)?.errors ?? {};
  return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
}

export function errorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    const msg = (error.body as { message?: string } | undefined)?.message;
    if (msg) return msg;
  }
  return fallback;
}

/** Authenticated request; body may be JSON or FormData (uploads). */
export async function apiSend<T>(
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  body?: unknown,
): Promise<T> {
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
  const response = await fetch(buildUrl(path), {
    method,
    headers: {
      Accept: 'application/json',
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      ...(body !== undefined && !isForm ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
  });
  const data = response.status === 204 ? undefined : await response.json().catch(() => undefined);
  if (!response.ok) {
    throw new ApiError(response.status, `${method} ${path} failed with ${response.status}`, data);
  }
  return data as T;
}
