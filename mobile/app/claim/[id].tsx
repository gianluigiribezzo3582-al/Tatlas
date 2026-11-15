import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { claimStudio } from '@/api/account';
import { errorMessage, fieldErrors } from '@/api/client';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';

export default function ClaimScreen() {
  const { id, name } = useLocalSearchParams<{ id: string; name?: string }>();
  const { t } = useTranslation();
  const [message, setMessage] = useState('');
  const [evidence, setEvidence] = useState('');
  const [busy, setBusy] = useState(false);
  const [done, setDone] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setError(null);
    setErrors({});
    try {
      await claimStudio(Number(id), message.trim(), evidence.trim());
      setDone(true);
    } catch (e) {
      setErrors(fieldErrors(e));
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('claim.title')}>
      {done ? (
        <>
          <Notice>{t('claim.sent')}</Notice>
          <Button label={t('common.back')} onPress={() => router.back()} />
        </>
      ) : (
        <>
          <Notice>{t('claim.intro', { name: name ?? '' })}</Notice>
          {error && <Notice tone="error">{error}</Notice>}
          <Field label={t('claim.message')} value={message} onChangeText={setMessage} multiline />
          <Field
            label={t('claim.evidence')}
            value={evidence}
            onChangeText={setEvidence}
            autoCapitalize="none"
            keyboardType="url"
            placeholder="https://www.instagram.com/…"
            hint={t('claim.evidenceHint')}
            error={errors.evidence_url}
          />
          <Button label={t('claim.submit')} onPress={submit} busy={busy} />
        </>
      )}
    </FormScreen>
  );
}
