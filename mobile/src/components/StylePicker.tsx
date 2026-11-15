import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { StyleSheet, Text, View } from 'react-native';

import { fetchStyles } from '@/api/source';
import { colors } from '@/theme';

import { Chip } from './Chip';

type Props = { value: string[]; onChange: (slugs: string[]) => void; max: number };

/** Multi-select over the controlled taxonomy (roots and sub-styles). */
export function StylePicker({ value, onChange, max }: Props) {
  const { t } = useTranslation();
  const styles_ = useQuery({ queryKey: ['styles'], queryFn: fetchStyles, staleTime: 3_600_000 });

  const toggle = (slug: string) => {
    if (value.includes(slug)) onChange(value.filter((s) => s !== slug));
    else if (value.length < max) onChange([...value, slug]);
  };

  return (
    <View style={{ gap: 10 }}>
      <Text style={styles.hint}>{t('account.stylesHint', { count: value.length, max })}</Text>
      {(styles_.data?.data ?? []).map((root) => (
        <View key={root.slug} style={styles.group}>
          <Chip
            label={root.name}
            selected={value.includes(root.slug)}
            onPress={() => toggle(root.slug)}
          />
          {root.children?.map((c) => (
            <Chip
              key={c.slug}
              label={c.name}
              selected={value.includes(c.slug)}
              onPress={() => toggle(c.slug)}
            />
          ))}
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  hint: { fontSize: 12, color: colors.muted },
  group: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
});
