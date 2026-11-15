import { useQueryClient } from '@tanstack/react-query';
import {
  createContext,
  type ReactNode,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';

import * as account from '@/api/account';
import { setAuthToken } from '@/api/client';

import { readToken, writeToken } from './tokenStore';

type AuthState = {
  user: account.User | null;
  ready: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (input: Parameters<typeof account.register>[0]) => Promise<void>;
  signOut: () => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [user, setUser] = useState<account.User | null>(null);
  const [ready, setReady] = useState(false);

  const accept = useCallback(async (res: account.AuthResponse) => {
    setAuthToken(res.token);
    await writeToken(res.token);
    setUser(res.user);
  }, []);

  const refresh = useCallback(async () => {
    try {
      setUser((await account.me()).user);
    } catch {
      setAuthToken(null);
      await writeToken(null);
      setUser(null);
    }
  }, []);

  useEffect(() => {
    readToken().then(async (token) => {
      if (token) {
        setAuthToken(token);
        await refresh();
      }
      setReady(true);
    });
  }, [refresh]);

  const value = useMemo<AuthState>(
    () => ({
      user,
      ready,
      signIn: async (email, password) => accept(await account.login(email, password)),
      signUp: async (input) => accept(await account.register(input)),
      signOut: async () => {
        try {
          await account.logout();
        } catch {
          // token may already be invalid
        }
        setAuthToken(null);
        await writeToken(null);
        setUser(null);
        queryClient.removeQueries({ queryKey: ['me'] });
      },
      refresh,
    }),
    [user, ready, accept, refresh, queryClient],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
