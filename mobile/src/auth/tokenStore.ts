import * as SecureStore from 'expo-secure-store';

/** The Sanctum token lives in the OS keystore, never in AsyncStorage. */
const KEY = 'tatlas.token';

export async function readToken(): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(KEY);
  } catch {
    return null;
  }
}

export async function writeToken(token: string | null): Promise<void> {
  try {
    if (token) await SecureStore.setItemAsync(KEY, token);
    else await SecureStore.deleteItemAsync(KEY);
  } catch {
    // ignore: the session still works until the app is closed
  }
}
