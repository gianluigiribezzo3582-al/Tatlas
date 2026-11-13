import { useQuery } from '@tanstack/react-query';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { Linking, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { fetchStudio } from '@/api/endpoints';
import { Chip } from '@/components/Chip';
import { LinkButton, Section } from '@/components/Section';
import { StateMessage } from '@/components/StateMessage';
import { formatAddress, formatPresenceDates } from '@/lib/format';
import { colors, spacing } from '@/theme';

export default function StudioScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { t, i18n } = useTranslation();
  const query = useQuery({ queryKey: ['studio', id], queryFn: () => fetchStudio(id) });
  const studio = query.data?.data;

  if (!studio) {
    return (
      <>
        <Stack.Screen options={{ title: '' }} />
        {query.isError ? (
          <StateMessage
            message={t('common.loadError')}
            actionLabel={t('common.retry')}
            onAction={() => query.refetch()}
          />
        ) : (
          <StateMessage loading />
        )}
      </>
    );
  }

  const address = formatAddress(studio.address);
  const { lat, lng } = studio.location;
  const directions = `geo:${lat},${lng}?q=${lat},${lng}(${encodeURIComponent(studio.name)})`;
  const residents = studio.artists.filter((a) => a.presence?.type === 'resident');
  const guests = studio.artists.filter((a) => a.presence?.type === 'guest');

  return (
    <>
      <Stack.Screen options={{ title: studio.name }} />
      <ScrollView style={styles.page} contentContainerStyle={{ paddingBottom: spacing.xl }}>
        <View style={styles.header}>
          <Text style={styles.name}>{studio.name}</Text>
          {address !== '' && <Text style={styles.meta}>{address}</Text>}
        </View>

        <Section title={t('studio.contacts')}>
          <LinkButton label={t('studio.directions')} onPress={() => Linking.openURL(directions)} />
          {studio.phone && (
            <LinkButton
              label={`${t('studio.call')} ${studio.phone}`}
              onPress={() => Linking.openURL(`tel:${studio.phone!.replace(/\s/g, '')}`)}
            />
          )}
          {studio.website && (
            <LinkButton
              label={t('studio.website')}
              onPress={() => Linking.openURL(studio.website!)}
            />
          )}
          {studio.instagram_url && (
            <LinkButton label="Instagram" onPress={() => Linking.openURL(studio.instagram_url!)} />
          )}
          {studio.email && (
            <LinkButton
              label={studio.email}
              onPress={() => Linking.openURL(`mailto:${studio.email}`)}
            />
          )}
        </Section>

        {studio.opening_hours && (
          <Section title={t('studio.hours')}>
            <Text style={styles.body}>{studio.opening_hours}</Text>
          </Section>
        )}

        <Section title={t('studio.residents')}>
          {residents.length === 0 ? (
            <Text style={styles.meta}>{t('studio.noArtists')}</Text>
          ) : (
            residents.map((a) => (
              <ArtistLine
                key={`r-${a.id}`}
                name={a.name}
                styles={a.styles?.map((s) => s.name)}
                slug={a.slug}
              />
            ))
          )}
        </Section>

        {guests.length > 0 && (
          <Section title={t('studio.guests')}>
            {guests.map((a) => (
              <ArtistLine
                key={`g-${a.id}-${a.presence?.starts_at}`}
                name={a.name}
                slug={a.slug}
                styles={a.styles?.map((s) => s.name)}
                dates={a.presence ? formatPresenceDates(a.presence, i18n.language) : null}
              />
            ))}
          </Section>
        )}

        <Section title={t('studio.claimTitle')}>
          <Text style={styles.meta}>{t('studio.claimHint')}</Text>
        </Section>

        {studio.attribution && (
          <Text
            style={styles.attribution}
            onPress={() => Linking.openURL(studio.attribution!.osm_url)}
          >
            {studio.attribution.text}
          </Text>
        )}
      </ScrollView>
    </>
  );
}

function ArtistLine({
  name,
  slug,
  styles: styleNames,
  dates,
}: {
  name: string;
  slug: string;
  styles?: string[];
  dates?: string | null;
}) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/artist/[slug]', params: { slug } })}
      style={styles.artist}
      accessibilityRole="button"
    >
      <View style={{ flex: 1, gap: 4 }}>
        <Text style={styles.artistName}>{name}</Text>
        <View style={styles.chips}>
          {styleNames?.slice(0, 3).map((s) => (
            <Chip key={s} label={s} />
          ))}
        </View>
      </View>
      {dates && <Text style={styles.dates}>{dates}</Text>}
      <Text style={styles.chevron}>›</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.paper },
  header: { padding: spacing.lg, gap: 4 },
  name: { fontSize: 26, fontWeight: '800', color: colors.ink },
  meta: { fontSize: 14, color: colors.muted },
  body: { fontSize: 15, color: colors.ink },
  artist: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
  artistName: { fontSize: 16, fontWeight: '600', color: colors.ink },
  chips: { flexDirection: 'row', gap: 6, flexWrap: 'wrap' },
  dates: { fontSize: 13, color: colors.guest, fontWeight: '600' },
  chevron: { fontSize: 22, color: colors.muted },
  attribution: { fontSize: 11, color: colors.muted, textAlign: 'center', padding: spacing.lg },
});
