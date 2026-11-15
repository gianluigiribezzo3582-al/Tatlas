import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Pressable, StyleSheet, Text, View } from 'react-native';

import {
  addPresence,
  createStudio,
  myPresences,
  type MyPresence,
  removePresence,
  searchStudios,
} from '@/api/account';
import { errorMessage, fieldErrors } from '@/api/client';
import type { StudioSummary } from '@/api/types';
import { Chip } from '@/components/Chip';
import { DateField, toIsoDate } from '@/components/DateField';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';
import { StateMessage } from '@/components/StateMessage';
import { useUserLocation } from '@/features/location/useUserLocation';
import { formatAddress, formatPresenceDates } from '@/lib/format';
import { useDebounced } from '@/lib/useDebounced';
import { colors, spacing } from '@/theme';

export default function MyStudiosScreen() {
  const { t, i18n } = useTranslation();
  const queryClient = useQueryClient();
  const location = useUserLocation();
  const presences = useQuery({ queryKey: ['me', 'presences'], queryFn: myPresences });

  const [q, setQ] = useState('');
  const debounced = useDebounced(q.trim(), 300);
  const results = useQuery({
    queryKey: ['studio-search', debounced, location.coords],
    queryFn: () => searchStudios(debounced, location.coords),
    enabled: debounced.length >= 2,
  });

  const [selected, setSelected] = useState<StudioSummary | null>(null);
  const [type, setType] = useState<'resident' | 'guest'>('resident');
  const [start, setStart] = useState<Date | null>(null);
  const [end, setEnd] = useState<Date | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);

  const refresh = (data: MyPresence[]) => {
    queryClient.setQueryData(['me', 'presences'], { data });
    queryClient.invalidateQueries({ queryKey: ['me', 'artist'] });
    queryClient.invalidateQueries({ queryKey: ['studios'] });
  };

  const save = async () => {
    if (!selected) return;
    setBusy(true);
    setError(null);
    try {
      const res = await addPresence({
        studio_id: selected.id,
        type,
        ...(type === 'guest' && start ? { starts_at: toIsoDate(start) } : {}),
        ...(type === 'guest' && end ? { ends_at: toIsoDate(end) } : {}),
      });
      refresh(res.data);
      setSelected(null);
      setQ('');
      setStart(null);
      setEnd(null);
    } catch (e) {
      const f = fieldErrors(e);
      setError(f.starts_at ?? f.ends_at ?? errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  const remove = (p: MyPresence) =>
    Alert.alert(t('account.removePresence'), p.studio.name, [
      { text: t('account.cancel'), style: 'cancel' },
      {
        text: t('account.remove'),
        style: 'destructive',
        onPress: async () => refresh((await removePresence(p.presence_id)).data),
      },
    ]);

  if (creating) {
    return <CreateStudio onDone={(s) => (setCreating(false), s && setSelected(s))} />;
  }

  return (
    <FormScreen title={t('account.myStudios')}>
      {presences.isLoading ? (
        <StateMessage loading />
      ) : (presences.data?.data ?? []).length === 0 ? (
        <Notice>{t('account.noPresences')}</Notice>
      ) : (
        presences.data!.data.map((p) => (
          <View key={p.presence_id} style={styles.row}>
            <View style={{ flex: 1 }}>
              <Text style={styles.name}>{p.studio.name}</Text>
              <Text style={p.type === 'guest' ? styles.guest : styles.meta}>
                {p.type === 'guest'
                  ? `${t('artist.guest')} ${formatPresenceDates(p, i18n.language) ?? ''}`
                  : t('artist.resident')}
              </Text>
            </View>
            <Pressable onPress={() => remove(p)} accessibilityRole="button">
              <Text style={styles.remove}>{t('account.remove')}</Text>
            </Pressable>
          </View>
        ))
      )}

      <Text style={styles.section}>{t('account.addStudio')}</Text>
      {selected ? (
        <View style={styles.card}>
          <Text style={styles.name}>{selected.name}</Text>
          <Text style={styles.meta}>{formatAddress(selected.address)}</Text>
          <View style={styles.chips}>
            <Chip
              label={t('artist.resident')}
              selected={type === 'resident'}
              onPress={() => setType('resident')}
            />
            <Chip
              label={t('account.guestSpot')}
              tone="guest"
              selected={type === 'guest'}
              onPress={() => setType('guest')}
            />
          </View>
          {type === 'guest' && (
            <>
              <DateField
                label={t('account.from')}
                value={start}
                onChange={setStart}
                minimumDate={new Date()}
              />
              <DateField
                label={t('account.to')}
                value={end}
                onChange={setEnd}
                minimumDate={start ?? new Date()}
              />
            </>
          )}
          {error && <Notice tone="error">{error}</Notice>}
          <Button
            label={t('account.save')}
            onPress={save}
            busy={busy}
            disabled={type === 'guest' && !start}
          />
          <Button
            label={t('account.cancel')}
            variant="secondary"
            onPress={() => setSelected(null)}
          />
        </View>
      ) : (
        <>
          <Field
            label={t('account.searchStudio')}
            value={q}
            onChangeText={setQ}
            placeholder={t('account.searchPlaceholder')}
          />
          {results.data?.data.map((s) => (
            <Pressable
              key={s.id}
              onPress={() => setSelected(s)}
              style={styles.result}
              accessibilityRole="button"
            >
              <Text style={styles.name}>{s.name}</Text>
              <Text style={styles.meta}>{formatAddress(s.address)}</Text>
            </Pressable>
          ))}
          {debounced.length >= 2 && results.data?.data.length === 0 && (
            <Text style={styles.meta}>{t('account.noStudioFound')}</Text>
          )}
          <Button
            label={t('account.newStudio')}
            variant="secondary"
            onPress={() => setCreating(true)}
          />
        </>
      )}
    </FormScreen>
  );
}

function CreateStudio({ onDone }: { onDone: (s: StudioSummary | null) => void }) {
  const { t } = useTranslation();
  const [name, setName] = useState('');
  const [street, setStreet] = useState('');
  const [number, setNumber] = useState('');
  const [city, setCity] = useState('Bari');
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [error, setError] = useState<string | null>(null);

  const submit = async () => {
    setBusy(true);
    setErrors({});
    setError(null);
    try {
      const res = await createStudio({
        name: name.trim(),
        street: street.trim(),
        housenumber: number.trim(),
        city: city.trim(),
      });
      onDone(res.data);
    } catch (e) {
      setErrors(fieldErrors(e));
      setError(errorMessage(e, t('account.networkError')));
    } finally {
      setBusy(false);
    }
  };

  return (
    <FormScreen title={t('account.newStudio')}>
      <Notice>{t('account.newStudioHint')}</Notice>
      {error && <Notice tone="error">{error}</Notice>}
      <Field
        label={t('account.studioName')}
        value={name}
        onChangeText={setName}
        error={errors.name}
      />
      <Field
        label={t('account.street')}
        value={street}
        onChangeText={setStreet}
        error={errors.street}
      />
      <Field label={t('account.housenumber')} value={number} onChangeText={setNumber} />
      <Field label={t('account.city')} value={city} onChangeText={setCity} error={errors.city} />
      <Button label={t('account.save')} onPress={submit} busy={busy} />
      <Button label={t('account.cancel')} variant="secondary" onPress={() => onDone(null)} />
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
  name: { fontSize: 16, fontWeight: '600', color: colors.ink },
  meta: { fontSize: 13, color: colors.muted },
  guest: { fontSize: 13, color: colors.guest, fontWeight: '600' },
  remove: { color: colors.danger, fontWeight: '600' },
  section: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.muted,
    textTransform: 'uppercase',
    marginTop: spacing.md,
  },
  card: {
    gap: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: 10,
    padding: spacing.md,
  },
  chips: { flexDirection: 'row', gap: 8 },
  result: {
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
});
