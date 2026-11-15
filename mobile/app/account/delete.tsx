import { router } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { deleteAccount } from '@/api/account';
import { errorMessage, fieldErrors } from '@/api/client';
import { useAuth } from '@/auth/AuthProvider';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';

export default function DeleteAccountScreen() {
  const { t } = useTranslation();
  const { signOut } = useAuth();
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      await deleteAccount(password);
      await signOut();
      router.replace('/');
    } catch (e) {
      setError(fieldErrors(e).password ?? errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('account.deleteTitle')}>
      <Notice tone="error">{t('account.deleteWarning')}</Notice>
      <Field
        label="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        error={error ?? undefined}
      />
      <Button
        label={t('account.deleteConfirm')}
        variant="danger"
        onPress={submit}
        busy={busy}
        disabled={!password}
      />
      <Button label={t('account.cancel')} variant="secondary" onPress={() => router.back()} />
    </FormScreen>
  );
}
