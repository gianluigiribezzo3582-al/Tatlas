import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, spacing } from '@/theme';

type Props = {
  loading?: boolean;
  message?: string;
  actionLabel?: string;
  onAction?: () => void;
};

export function StateMessage({ loading, message, actionLabel, onAction }: Props) {
  return (
    <View style={styles.box}>
      {loading && <ActivityIndicator color={colors.ink} />}
      {message && <Text style={styles.text}>{message}</Text>}
      {actionLabel && onAction && (
        <Pressable onPress={onAction} style={styles.button} accessibilityRole="button">
          <Text style={styles.buttonText}>{actionLabel}</Text>
        </Pressable>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  box: { padding: spacing.xl, alignItems: 'center', gap: spacing.md },
  text: { color: colors.muted, textAlign: 'center', fontSize: 14 },
  button: {
    backgroundColor: colors.ink,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: 8,
  },
  buttonText: { color: colors.paper, fontWeight: '600' },
});
