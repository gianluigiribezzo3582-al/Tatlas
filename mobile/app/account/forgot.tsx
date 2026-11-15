import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { forgotPassword } from '@/api/account';
import { errorMessage } from '@/api/client';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';

export default function ForgotPasswordScreen() {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      await forgotPassword(email.trim());
      setSent(true);
    } catch (e) {
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('account.forgotTitle')}>
      {sent ? (
        <Notice>{t('account.forgotSent')}</Notice>
      ) : (
        <>
          <Notice>{t('account.forgotIntro')}</Notice>
          {error && <Notice tone="error">{error}</Notice>}
          <Field
            label="Email"
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
          />
          <Button
            label={t('account.forgotSend')}
            onPress={submit}
            busy={busy}
            disabled={!email.trim()}
          />
        </>
      )}
    </FormScreen>
  );
}
