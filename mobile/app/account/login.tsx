import { router } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { errorMessage } from '@/api/client';
import { useAuth } from '@/auth/AuthProvider';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';

export default function LoginScreen() {
  const { t } = useTranslation();
  const { signIn } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      await signIn(email.trim(), password);
      router.back();
    } catch (e) {
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('account.login')}>
      {error && <Notice tone="error">{error}</Notice>}
      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        keyboardType="email-address"
        autoComplete="email"
        testID="login-email"
      />
      <Field
        label="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        autoComplete="password"
        testID="login-password"
      />
      <Button label={t('account.login')} onPress={submit} busy={busy} testID="login-submit" />
      <Button
        label={t('account.noAccount')}
        variant="secondary"
        onPress={() => router.replace('/account/register')}
      />
    </FormScreen>
  );
}
