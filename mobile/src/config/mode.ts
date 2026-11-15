import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * Demo mode: the app runs on fictional data bundled in the APK, with no backend.
 * Useful to try the app on a phone alone, or to show it to someone.
 */
const KEY = 'tatlas.demoMode';
// Test APKs can start in demo mode until the user picks a server (build-time flag).
const DEFAULT = process.env.EXPO_PUBLIC_DEMO_DEFAULT === '1';
let demo = DEFAULT;

export const isDemoMode = () => demo;

export async function loadDemoMode(): Promise<boolean> {
  try {
    const stored = await AsyncStorage.getItem(KEY);
    demo = stored === null ? DEFAULT : stored === '1';
  } catch {
    demo = DEFAULT;
  }
  return demo;
}

export async function setDemoMode(on: boolean): Promise<void> {
  demo = on;
  try {
    await AsyncStorage.setItem(KEY, on ? '1' : '0');
  } catch {
    // applies to this session anyway
  }
}
