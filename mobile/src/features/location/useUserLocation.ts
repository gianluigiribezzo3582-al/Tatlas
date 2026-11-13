import * as Location from 'expo-location';
import { useCallback, useEffect, useState } from 'react';
import { Linking } from 'react-native';

export type LocationStatus = 'checking' | 'undetermined' | 'granted' | 'denied';

export type UserLocation = {
  status: LocationStatus;
  coords: { lat: number; lng: number } | null;
  canAskAgain: boolean;
};

/**
 * Foreground location only, never requested at startup: we check the current
 * permission silently and ask only when the user taps "use my location".
 * If the user refuses, the app keeps working on Bari as default area.
 */
export function useUserLocation() {
  const [state, setState] = useState<UserLocation>({
    status: 'checking',
    coords: null,
    canAskAgain: true,
  });

  const readPosition = useCallback(async () => {
    try {
      const last = await Location.getLastKnownPositionAsync();
      if (last) {
        setState((s) => ({
          ...s,
          coords: { lat: last.coords.latitude, lng: last.coords.longitude },
        }));
      }
      const pos = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.Balanced,
      });
      setState((s) => ({
        ...s,
        coords: { lat: pos.coords.latitude, lng: pos.coords.longitude },
      }));
    } catch {
      // GPS off or timeout: keep last known / default area
    }
  }, []);

  useEffect(() => {
    let alive = true;
    Location.getForegroundPermissionsAsync()
      .then((p) => {
        if (!alive) return;
        const status: LocationStatus = p.granted
          ? 'granted'
          : p.status === 'undetermined'
            ? 'undetermined'
            : 'denied';
        setState((s) => ({ ...s, status, canAskAgain: p.canAskAgain }));
        if (p.granted) readPosition();
      })
      .catch(() => alive && setState((s) => ({ ...s, status: 'denied' })));
    return () => {
      alive = false;
    };
  }, [readPosition]);

  const request = useCallback(async () => {
    if (state.status === 'denied' && !state.canAskAgain) {
      await Linking.openSettings();
      return;
    }
    const p = await Location.requestForegroundPermissionsAsync();
    setState((s) => ({
      ...s,
      status: p.granted ? 'granted' : 'denied',
      canAskAgain: p.canAskAgain,
    }));
    if (p.granted) await readPosition();
  }, [state.status, state.canAskAgain, readPosition]);

  return { ...state, request, refresh: readPosition };
}
