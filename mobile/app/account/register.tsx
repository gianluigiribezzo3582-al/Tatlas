import { router } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { errorMessage, fieldErrors } from '@/api/client';
import { useAuth } from '@/auth/AuthProvider';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';
import { colors } from '@/theme';

export default function RegisterScreen() {
  const { t } = useTranslation();
  const { signUp } = useAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isArtist, setIsArtist] = useState(true);
  const [privacy, setPrivacy] = useState(false);
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setErrors({});
    setError(null);
    try {
      await signUp({
        name: name.trim(),
        email: email.trim(),
        password,
        password_confirmation: password,
        role: isArtist ? 'artist' : 'user',
        accept_privacy: privacy,
      });
      router.replace(isArtist ? '/account/artist' : '/account');
    } catch (e) {
      setErrors(fieldErrors(e));
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('account.register')}>
      {error && <Notice tone="error">{error}</Notice>}
      <Field label={t('account.name')} value={name} onChangeText={setName} error={errors.name} />
      <Field
        label="Email"
        value={email}
        onChangeText={setEmail}
        autoCapitalize="none"
        keyboardType="email-address"
        error={errors.email}
      />
      <Field
        label="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        hint={t('account.passwordHint')}
        error={errors.password}
      />
      <View style={styles.switchRow}>
        <Text style={styles.text}>{t('account.iAmArtist')}</Text>
        <Switch value={isArtist} onValueChange={setIsArtist} />
      </View>
      <View style={styles.switchRow}>
        <Text style={styles.text}>{t('account.privacy')}</Text>
        <Switch value={privacy} onValueChange={setPrivacy} testID="privacy-switch" />
      </View>
      {errors.accept_privacy && <Text style={styles.error}>{t('account.privacyRequired')}</Text>}
      <Button label={t('account.register')} onPress={submit} busy={busy} />
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  switchRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  text: { flex: 1, fontSize: 14, color: colors.ink },
  error: { fontSize: 12, color: colors.danger },
});
