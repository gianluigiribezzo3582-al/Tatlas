import { useQueryClient } from '@tanstack/react-query';
import Constants from 'expo-constants';
import { Stack } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Linking, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';

import { Section } from '@/components/Section';
import { env } from '@/config/env';
import { getApiUrl, normalizeApiUrl, saveApiUrl } from '@/config/server';
import { tileSource } from '@/map/tiles';
import { colors, spacing } from '@/theme';

export default function SettingsScreen() {
  const { t } = useTranslation();
  const queryClient = useQueryClient();
  const [value, setValue] = useState(getApiUrl());
  const [status, setStatus] = useState<{ ok: boolean; text: string } | null>(null);

  const test = async (url: string) => {
    try {
      const res = await fetch(`${url}/styles`, { headers: { Accept: 'application/json' } });
      return res.ok;
    } catch {
      return false;
    }
  };

  const save = async () => {
    const url = normalizeApiUrl(value);
    if (!url) {
      setStatus({ ok: false, text: t('settings.invalid') });
      return;
    }
    setValue(url);
    const ok = await test(url);
    await saveApiUrl(url);
    await queryClient.invalidateQueries();
    setStatus({ ok, text: ok ? t('settings.connected') : t('settings.unreachable') });
  };

  const reset = async () => {
    const url = await saveApiUrl(null);
    setValue(url);
    setStatus(null);
    await queryClient.invalidateQueries();
  };

  return (
    <>
      <Stack.Screen options={{ title: t('settings.title') }} />
      <ScrollView style={styles.page} keyboardShouldPersistTaps="handled">
        <Section title={t('settings.server')}>
          <Text style={styles.meta}>{t('settings.serverHint')}</Text>
          <TextInput
            value={value}
            onChangeText={setValue}
            autoCapitalize="none"
            autoCorrect={false}
            keyboardType="url"
            placeholder="http://192.168.1.20:8000"
            style={styles.input}
            testID="server-url"
          />
          <View style={styles.row}>
            <Pressable onPress={save} style={styles.primary} accessibilityRole="button">
              <Text style={styles.primaryText}>{t('settings.saveAndTest')}</Text>
            </Pressable>
            <Pressable onPress={reset} style={styles.secondary} accessibilityRole="button">
              <Text style={styles.secondaryText}>{t('settings.reset')}</Text>
            </Pressable>
          </View>
          {status && (
            <Text style={{ color: status.ok ? colors.ink : colors.danger }}>{status.text}</Text>
          )}
          <Text style={styles.small}>
            {t('settings.default')}: {env.apiUrl}
          </Text>
        </Section>

        <Section title={t('settings.about')}>
          <Text style={styles.meta}>
            Tatlas {Constants.expoConfig?.version ?? ''} · {t('app.tagline')}
          </Text>
          <Text style={styles.meta}>{tileSource.attribution}</Text>
          <Text
            style={styles.link}
            onPress={() => Linking.openURL('https://www.openstreetmap.org/copyright')}
          >
            {t('settings.osmLicense')}
          </Text>
        </Section>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.paper },
  meta: { fontSize: 14, color: colors.muted },
  small: { fontSize: 12, color: colors.muted },
  input: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 8,
    padding: spacing.md,
    fontSize: 15,
    color: colors.ink,
  },
  row: { flexDirection: 'row', gap: spacing.sm },
  primary: {
    backgroundColor: colors.ink,
    borderRadius: 8,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  primaryText: { color: colors.paper, fontWeight: '600' },
  secondary: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 8,
    paddingVertical: spacing.sm,
    paddingHorizontal: spacing.lg,
  },
  secondaryText: { color: colors.ink },
  link: { fontSize: 14, color: colors.accent, fontWeight: '600' },
});
