import { useQuery } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import {
  Linking,
  Pressable,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  useWindowDimensions,
  View,
} from 'react-native';

import { fetchArtist } from '@/api/endpoints';
import { Chip } from '@/components/Chip';
import { LinkButton, Section } from '@/components/Section';
import { StateMessage } from '@/components/StateMessage';
import { formatAddress, formatPresenceDates, priceLabel } from '@/lib/format';
import { colors, spacing } from '@/theme';

export default function ArtistScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const { t, i18n } = useTranslation();
  const { width } = useWindowDimensions();
  const query = useQuery({ queryKey: ['artist', slug], queryFn: () => fetchArtist(slug) });
  const artist = query.data?.data;

  if (!artist) {
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

  const tile = (width - spacing.lg * 2 - spacing.sm * 2) / 3;
  const facts = [
    artist.accepts_walk_in ? t('artist.walkIn') : null,
    priceLabel(artist.price_range),
    artist.languages.length ? artist.languages.map((l) => l.toUpperCase()).join(' · ') : null,
  ].filter(Boolean);

  return (
    <>
      <Stack.Screen options={{ title: artist.name }} />
      <ScrollView style={styles.page} contentContainerStyle={{ paddingBottom: spacing.xl }}>
        <View style={styles.header}>
          <Text style={styles.name}>
            {artist.name}
            {artist.is_verified ? ' ✓' : ''}
          </Text>
          {facts.length > 0 && <Text style={styles.meta}>{facts.join('  ·  ')}</Text>}
          <View style={styles.chips}>
            {artist.styles?.map((s) => (
              <Chip key={s.slug} label={s.name} />
            ))}
          </View>
        </View>

        {artist.bio && (
          <Section title={t('artist.about')}>
            <Text style={styles.body}>{artist.bio}</Text>
          </Section>
        )}

        <Section title={t('artist.portfolio')}>
          {artist.works && artist.works.length > 0 ? (
            <View style={styles.grid}>
              {artist.works.map((w) => (
                <Image
                  key={w.id}
                  source={{ uri: w.thumbnail_url }}
                  style={{
                    width: tile,
                    height: tile,
                    borderRadius: 6,
                    backgroundColor: colors.surface,
                  }}
                  contentFit="cover"
                  transition={150}
                  accessibilityLabel={w.description ?? undefined}
                />
              ))}
            </View>
          ) : (
            <Text style={styles.meta}>{t('artist.noWorks')}</Text>
          )}
        </Section>

        <Section title={t('artist.whereToFind')}>
          {artist.studios?.map((s) => (
            <Pressable
              key={`${s.id}-${s.presence.type}-${s.presence.starts_at}`}
              onPress={() => router.push({ pathname: '/studio/[id]', params: { id: s.id } })}
              style={styles.studio}
              accessibilityRole="button"
            >
              <View style={{ flex: 1 }}>
                <Text style={styles.studioName}>{s.name}</Text>
                <Text style={styles.meta}>{formatAddress(s.address)}</Text>
              </View>
              <Text style={s.presence.type === 'guest' ? styles.guest : styles.meta}>
                {s.presence.type === 'guest'
                  ? `${t('artist.guest')} ${formatPresenceDates(s.presence, i18n.language) ?? ''}`
                  : t('artist.resident')}
              </Text>
            </Pressable>
          ))}
        </Section>

        <Section title={t('artist.links')}>
          {artist.instagram_url && (
            <LinkButton label="Instagram" onPress={() => Linking.openURL(artist.instagram_url!)} />
          )}
          {artist.website && (
            <LinkButton
              label={t('studio.website')}
              onPress={() => Linking.openURL(artist.website!)}
            />
          )}
          <LinkButton
            label={t('artist.share')}
            onPress={() =>
              Share.share({ message: `${artist.name} su Tatlas: ${artist.public_url}` })
            }
          />
        </Section>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.paper },
  header: { padding: spacing.lg, gap: spacing.sm },
  name: { fontSize: 26, fontWeight: '800', color: colors.ink },
  meta: { fontSize: 14, color: colors.muted },
  body: { fontSize: 15, color: colors.ink, lineHeight: 21 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  studio: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingVertical: spacing.sm,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
  studioName: { fontSize: 16, fontWeight: '600', color: colors.ink },
  guest: { fontSize: 13, color: colors.guest, fontWeight: '600' },
});
