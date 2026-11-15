import { useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { createMyArtist, getMyArtist, updateMyArtist } from '@/api/account';
import type { Artist } from '@/api/types';
import { errorMessage, fieldErrors } from '@/api/client';
import { useAuth } from '@/auth/AuthProvider';
import { Chip } from '@/components/Chip';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';
import { StateMessage } from '@/components/StateMessage';
import { StylePicker } from '@/components/StylePicker';
import { colors } from '@/theme';

const LANGS = ['it', 'en', 'es', 'fr', 'de'];

export default function ArtistProfileScreen() {
  const { t } = useTranslation();
  const { user } = useAuth();
  const current = useQuery({
    queryKey: ['me', 'artist', user?.id],
    queryFn: getMyArtist,
    enabled: !!user,
  });

  if (!user) return <StateMessage message={t('account.loginRequired')} />;
  if (current.isLoading) return <StateMessage loading />;

  const existing = current.data?.data ?? null;
  return <ArtistForm key={existing?.id ?? 'new'} existing={existing} />;
}

function ArtistForm({ existing }: { existing: Artist | null }) {
  const { t } = useTranslation();
  const { refresh } = useAuth();
  const queryClient = useQueryClient();

  const [name, setName] = useState(existing?.name ?? '');
  const [bio, setBio] = useState(existing?.bio ?? '');
  const [instagram, setInstagram] = useState(existing?.instagram_url ?? '');
  const [website, setWebsite] = useState(existing?.website ?? '');
  const [languages, setLanguages] = useState<string[]>(existing?.languages ?? ['it']);
  const [walkIn, setWalkIn] = useState(existing?.accepts_walk_in ?? false);
  const [price, setPrice] = useState<number | null>(existing?.price_range ?? null);
  const [styles, setStyles] = useState<string[]>(existing?.styles?.map((x) => x.slug) ?? []);
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);

  const normalizeInstagram = (v: string) => {
    const handle = v.trim().replace(/^@/, '');
    if (!handle) return null;
    return handle.startsWith('http') ? handle : `https://www.instagram.com/${handle}`;
  };

  const save = async () => {
    setBusy(true);
    setErrors({});
    setError(null);
    const input = {
      name: name.trim(),
      bio: bio.trim() || null,
      instagram_url: normalizeInstagram(instagram),
      website: website.trim() || null,
      languages,
      accepts_walk_in: walkIn,
      price_range: price,
      styles,
    };
    try {
      await (existing ? updateMyArtist(input) : createMyArtist(input));
      await queryClient.invalidateQueries({ queryKey: ['me'] });
      await refresh();
      router.back();
    } catch (e) {
      setErrors(fieldErrors(e));
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={existing ? t('account.editProfile') : t('account.createProfile')}>
      {!existing && <Notice>{t('account.verificationNotice')}</Notice>}
      {error && <Notice tone="error">{error}</Notice>}
      <Field
        label={t('account.stageName')}
        value={name}
        onChangeText={setName}
        error={errors.name}
      />
      <Field
        label={t('artist.about')}
        value={bio}
        onChangeText={setBio}
        multiline
        error={errors.bio}
      />
      <Field
        label="Instagram"
        value={instagram}
        onChangeText={setInstagram}
        autoCapitalize="none"
        placeholder="@nomeutente"
        error={errors.instagram_url}
      />
      <Field
        label={t('studio.website')}
        value={website}
        onChangeText={setWebsite}
        autoCapitalize="none"
        keyboardType="url"
        error={errors.website}
      />

      <Text style={s.label}>{t('account.languages')}</Text>
      <View style={s.row}>
        {LANGS.map((l) => (
          <Chip
            key={l}
            label={l.toUpperCase()}
            selected={languages.includes(l)}
            onPress={() =>
              setLanguages((cur) => (cur.includes(l) ? cur.filter((x) => x !== l) : [...cur, l]))
            }
          />
        ))}
      </View>

      <Text style={s.label}>{t('account.priceRange')}</Text>
      <View style={s.row}>
        {[1, 2, 3].map((p) => (
          <Chip
            key={p}
            label={'€'.repeat(p)}
            selected={price === p}
            onPress={() => setPrice(price === p ? null : p)}
          />
        ))}
      </View>

      <View style={s.switchRow}>
        <Text style={s.text}>{t('artist.walkIn')}</Text>
        <Switch value={walkIn} onValueChange={setWalkIn} />
      </View>

      <Text style={s.label}>{t('account.styles')}</Text>
      <StylePicker value={styles} onChange={setStyles} max={5} />
      {errors.styles && <Text style={s.error}>{errors.styles}</Text>}

      <Button label={t('account.save')} onPress={save} busy={busy} />
    </FormScreen>
  );
}

const s = StyleSheet.create({
  label: { fontSize: 13, fontWeight: '600', color: colors.ink },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  switchRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  text: { flex: 1, fontSize: 14, color: colors.ink },
  error: { fontSize: 12, color: colors.danger },
});
