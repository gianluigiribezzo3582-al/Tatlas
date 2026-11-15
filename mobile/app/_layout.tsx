import '@/i18n';

import { QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { AuthProvider } from '@/auth/AuthProvider';
import { StateMessage } from '@/components/StateMessage';
import { loadDemoMode } from '@/config/mode';
import { loadApiUrl } from '@/config/server';
import { createQueryClient } from '@/lib/queryClient';
import { colors } from '@/theme';

export default function RootLayout() {
  const [queryClient] = useState(createQueryClient);
  const [ready, setReady] = useState(false);
  const { t } = useTranslation();

  useEffect(() => {
    Promise.all([loadApiUrl(), loadDemoMode()]).finally(() => setReady(true));
  }, []);

  return (
    <SafeAreaProvider>
      <QueryClientProvider client={queryClient}>
        {ready ? (
          <AuthProvider>
            <Stack
              screenOptions={{
                headerTintColor: colors.ink,
                headerBackTitle: t('common.back'),
                contentStyle: { backgroundColor: colors.paper },
              }}
            >
              <Stack.Screen name="index" options={{ headerShown: false }} />
            </Stack>
          </AuthProvider>
        ) : (
          <StateMessage loading />
        )}
        <StatusBar style="dark" />
      </QueryClientProvider>
    </SafeAreaProvider>
  );
}
