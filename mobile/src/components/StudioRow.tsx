import { useTranslation } from 'react-i18next';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import type { StudioSummary } from '@/api/types';
import { formatAddress, formatDistance } from '@/lib/format';
import { colors, spacing } from '@/theme';

type Props = { studio: StudioSummary; selected?: boolean; onPress: () => void };

export function StudioRow({ studio, selected, onPress }: Props) {
  const { t } = useTranslation();
  const distance = formatDistance(studio.distance_m);
  const address = formatAddress(studio.address);

  return (
    <Pressable
      onPress={onPress}
      style={[styles.row, selected && styles.selected]}
      accessibilityRole="button"
      testID={`studio-row-${studio.id}`}
    >
      <View style={styles.main}>
        <Text style={styles.name} numberOfLines={1}>
          {studio.name}
        </Text>
        {address !== '' && (
          <Text style={styles.meta} numberOfLines={1}>
            {address}
          </Text>
        )}
        <View style={styles.badges}>
          {(studio.artists_count ?? 0) > 0 && (
            <Text style={styles.meta}>
              {t('studio.artistsCount', { count: studio.artists_count })}
            </Text>
          )}
          {studio.has_upcoming_guest && <Text style={styles.guest}>{t('studio.guestSoon')}</Text>}
        </View>
      </View>
      {distance && <Text style={styles.distance}>{distance}</Text>}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
  selected: { backgroundColor: colors.surface },
  main: { flex: 1, gap: 2 },
  name: { fontSize: 16, fontWeight: '600', color: colors.ink },
  meta: { fontSize: 13, color: colors.muted },
  badges: { flexDirection: 'row', gap: spacing.sm },
  guest: { fontSize: 13, color: colors.guest, fontWeight: '600' },
  distance: { fontSize: 13, color: colors.ink, marginLeft: spacing.sm },
});
