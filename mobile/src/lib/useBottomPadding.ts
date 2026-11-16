import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { spacing } from '@/theme';

/**
 * Bottom padding for scrollable screens: Android 15+ draws edge-to-edge, so the
 * last item must clear the system navigation bar (3-button or gesture).
 */
export function useBottomPadding(extra: number = spacing.xl): number {
  return useSafeAreaInsets().bottom + extra;
}
