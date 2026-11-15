import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, render, screen } from '@testing-library/react-native';
import * as SecureStore from 'expo-secure-store';
import { Text } from 'react-native';

import { apiSend, ApiError, errorMessage, fieldErrors, setAuthToken } from '@/api/client';
import { AuthProvider, useAuth } from '@/auth/AuthProvider';

const json = (body: unknown, status = 200) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

afterEach(() => {
  jest.restoreAllMocks();
  setAuthToken(null);
});

it('sends the bearer token and JSON bodies', async () => {
  const fetchMock = jest.spyOn(globalThis, 'fetch').mockResolvedValue(json({ ok: true }));
  setAuthToken('abc');

  await apiSend('PATCH', 'me/artist', { name: 'Mara' });

  const [, init] = fetchMock.mock.calls[0];
  expect((init!.headers as Record<string, string>).Authorization).toBe('Bearer abc');
  expect((init!.headers as Record<string, string>)['Content-Type']).toBe('application/json');
  expect(init!.body).toBe('{"name":"Mara"}');
});

it('turns Laravel 422 responses into field errors', async () => {
  jest
    .spyOn(globalThis, 'fetch')
    .mockResolvedValue(json({ message: 'Invalid', errors: { name: ['Too short', 'x'] } }, 422));

  const error = await apiSend('POST', 'me/artist', {}).catch((e) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect(fieldErrors(error)).toEqual({ name: 'Too short' });
  expect(errorMessage(error, 'fallback')).toBe('Invalid');
  expect(errorMessage(new Error('x'), 'fallback')).toBe('fallback');
});

function Probe() {
  const { user, signIn } = useAuth();
  return (
    <>
      <Text testID="who">{user?.email ?? 'guest'}</Text>
      <Text testID="login" onPress={() => signIn('a@example.com', 'secret-pass')}>
        login
      </Text>
    </>
  );
}

it('signs in, stores the token in the secure store and exposes the user', async () => {
  jest.spyOn(globalThis, 'fetch').mockResolvedValue(
    json({
      token: 'tok-1',
      user: {
        id: 1,
        name: 'A',
        email: 'a@example.com',
        role: 'artist',
        email_verified: false,
        artist_slug: null,
      },
    }),
  );

  await render(
    <QueryClientProvider client={new QueryClient()}>
      <AuthProvider>
        <Probe />
      </AuthProvider>
    </QueryClientProvider>,
  );
  expect(screen.getByTestId('who').props.children).toBe('guest');

  await act(async () => {
    screen.getByTestId('login').props.onPress();
  });

  expect(screen.getByTestId('who').props.children).toBe('a@example.com');
  expect(await SecureStore.getItemAsync('tatlas.token')).toBe('tok-1');
});
