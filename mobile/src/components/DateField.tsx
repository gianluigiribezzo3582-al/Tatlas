import DateTimePicker, { DateTimePickerAndroid } from '@react-native-community/datetimepicker';
import { useTranslation } from 'react-i18next';
import { Platform, Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, spacing } from '@/theme';

type Props = { label: string; value: Date | null; onChange: (d: Date) => void; minimumDate?: Date };

export const toIsoDate = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

export function DateField({ label, value, onChange, minimumDate }: Props) {
  const { t, i18n } = useTranslation();

  if (Platform.OS === 'ios') {
    return (
      <View style={styles.row}>
        <Text style={styles.label}>{label}</Text>
        <DateTimePicker
          value={value ?? minimumDate ?? new Date()}
          mode="date"
          minimumDate={minimumDate}
          onChange={(_, d) => d && onChange(d)}
        />
      </View>
    );
  }

  return (
    <View style={{ gap: 4 }}>
      <Text style={styles.label}>{label}</Text>
      <Pressable
        style={styles.input}
        accessibilityRole="button"
        onPress={() =>
          DateTimePickerAndroid.open({
            value: value ?? minimumDate ?? new Date(),
            mode: 'date',
            minimumDate,
            onChange: (event, d) => event.type === 'set' && d && onChange(d),
          })
        }
      >
        <Text style={{ color: value ? colors.ink : colors.muted }}>
          {value ? value.toLocaleDateString(i18n.language) : t('account.pickDate')}
        </Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  label: { fontSize: 13, fontWeight: '600', color: colors.ink },
  input: { borderWidth: 1, borderColor: colors.line, borderRadius: 8, padding: spacing.md },
});
