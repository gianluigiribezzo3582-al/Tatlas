import { type ReactNode, useCallback, useMemo, useState } from 'react';
import {
  Animated,
  PanResponder,
  Pressable,
  StyleSheet,
  useWindowDimensions,
  View,
} from 'react-native';

import { colors } from '@/theme';

type Props = {
  header: ReactNode;
  children: ReactNode;
  /** Visible height when collapsed, as a fraction of the window. */
  collapsed?: number;
  /** Visible height when expanded. */
  expanded?: number;
};

/**
 * Lightweight two-position bottom sheet (no native deps): drag the handle or tap it.
 */
export function BottomSheet({ header, children, collapsed = 0.3, expanded = 0.8 }: Props) {
  const { height } = useWindowDimensions();
  const full = height * expanded;
  const offsetCollapsed = full - height * collapsed;
  const [translateY] = useState(() => new Animated.Value(offsetCollapsed));
  const [open, setOpen] = useState(false);

  const snap = useCallback(
    (toOpen: boolean) => {
      setOpen(toOpen);
      Animated.spring(translateY, {
        toValue: toOpen ? 0 : offsetCollapsed,
        useNativeDriver: true,
        bounciness: 2,
      }).start();
    },
    [translateY, offsetCollapsed],
  );

  const pan = useMemo(() => {
    let start = offsetCollapsed; // drag origin, local to this responder
    return PanResponder.create({
      onMoveShouldSetPanResponder: (_, g) => Math.abs(g.dy) > 6,
      onPanResponderGrant: () => {
        translateY.stopAnimation((v) => {
          start = v;
        });
      },
      onPanResponderMove: (_, g) => {
        translateY.setValue(Math.min(offsetCollapsed, Math.max(0, start + g.dy)));
      },
      onPanResponderRelease: (_, g) => {
        snap(start + g.dy + g.vy * 120 < offsetCollapsed / 2);
      },
    });
  }, [offsetCollapsed, translateY, snap]);

  return (
    <Animated.View
      style={[styles.sheet, { height: full, transform: [{ translateY }] }]}
      testID="bottom-sheet"
    >
      <View {...pan.panHandlers}>
        <Pressable
          onPress={() => snap(!open)}
          accessibilityRole="button"
          accessibilityLabel={open ? 'Riduci lista' : 'Espandi lista'}
          style={styles.handleArea}
        >
          <View style={styles.handle} />
        </Pressable>
        {header}
      </View>
      <View style={styles.body}>{children}</View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  sheet: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: colors.paper,
    borderTopLeftRadius: 18,
    borderTopRightRadius: 18,
    elevation: 12,
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: -2 },
  },
  handleArea: { alignItems: 'center', paddingVertical: 10 },
  handle: { width: 42, height: 5, borderRadius: 3, backgroundColor: colors.line },
  body: { flex: 1 },
});
