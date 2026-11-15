import type { ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  View,
} from 'react-native';

import { colors, spacing } from '@/theme';

export function Field({
  label,
  error,
  hint,
  ...input
}: TextInputProps & { label: string; error?: string; hint?: string }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        placeholderTextColor={colors.muted}
        {...input}
        style={[styles.input, input.multiline && styles.multiline, error && styles.inputError]}
      />
      {hint && !error && <Text style={styles.hint}>{hint}</Text>}
      {error && <Text style={styles.error}>{error}</Text>}
    </View>
  );
}

export function Button({
  label,
  onPress,
  busy,
  variant = 'primary',
  disabled,
  testID,
}: {
  label: string;
  onPress: () => void;
  busy?: boolean;
  variant?: 'primary' | 'secondary' | 'danger';
  disabled?: boolean;
  testID?: string;
}) {
  return (
    <Pressable
      onPress={onPress}
      disabled={busy || disabled}
      accessibilityRole="button"
      testID={testID}
      style={[
        styles.button,
        variant === 'secondary' && styles.secondary,
        variant === 'danger' && styles.danger,
        (busy || disabled) && { opacity: 0.6 },
      ]}
    >
      {busy ? (
        <ActivityIndicator color={variant === 'secondary' ? colors.ink : colors.paper} />
      ) : (
        <Text style={[styles.buttonText, variant === 'secondary' && { color: colors.ink }]}>
          {label}
        </Text>
      )}
    </Pressable>
  );
}

export function Notice({
  children,
  tone = 'info',
}: {
  children: ReactNode;
  tone?: 'info' | 'error';
}) {
  return (
    <View style={[styles.notice, tone === 'error' && styles.noticeError]}>
      <Text style={{ color: tone === 'error' ? colors.danger : colors.ink }}>{children}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  field: { gap: 4 },
  label: { fontSize: 13, fontWeight: '600', color: colors.ink },
  input: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 8,
    padding: spacing.md,
    fontSize: 15,
    color: colors.ink,
    backgroundColor: colors.paper,
  },
  multiline: { minHeight: 90, textAlignVertical: 'top' },
  inputError: { borderColor: colors.danger },
  hint: { fontSize: 12, color: colors.muted },
  error: { fontSize: 12, color: colors.danger },
  button: {
    backgroundColor: colors.ink,
    borderRadius: 8,
    paddingVertical: spacing.md,
    alignItems: 'center',
  },
  secondary: { backgroundColor: colors.paper, borderWidth: 1, borderColor: colors.line },
  danger: { backgroundColor: colors.danger },
  buttonText: { color: colors.paper, fontWeight: '700', fontSize: 15 },
  notice: { backgroundColor: colors.surface, borderRadius: 8, padding: spacing.md },
  noticeError: { backgroundColor: '#FEF2F2' },
});
