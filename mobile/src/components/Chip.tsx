import { Pressable, StyleSheet, Text } from 'react-native';

import { colors } from '@/theme';

type Props = { label: string; selected?: boolean; onPress?: () => void; tone?: 'ink' | 'guest' };

export function Chip({ label, selected = false, onPress, tone = 'ink' }: Props) {
  const active = tone === 'guest' ? colors.guest : colors.ink;
  return (
    <Pressable
      onPress={onPress}
      disabled={!onPress}
      accessibilityRole={onPress ? 'button' : 'text'}
      accessibilityState={{ selected }}
      style={[
        styles.chip,
        selected && { backgroundColor: active, borderColor: active },
        !onPress && styles.static,
      ]}
    >
      <Text style={[styles.label, selected && styles.labelSelected]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  chip: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.line,
    backgroundColor: colors.paper,
  },
  static: { paddingVertical: 4, paddingHorizontal: 10 },
  label: { fontSize: 13, color: colors.ink },
  labelSelected: { color: colors.paper, fontWeight: '600' },
});
