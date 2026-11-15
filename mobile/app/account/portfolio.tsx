import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import { manipulateAsync, SaveFormat } from 'expo-image-manipulator';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Pressable, StyleSheet, Text, useWindowDimensions, View } from 'react-native';

import { deleteWork, myWorks, uploadWork } from '@/api/account';
import { errorMessage } from '@/api/client';
import { Button, Field, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';
import { StateMessage } from '@/components/StateMessage';
import { StylePicker } from '@/components/StylePicker';
import { imageSource } from '@/demo/images';
import { colors, spacing } from '@/theme';

const LIMIT = 12;
const MAX_SIDE = 2048;

/** Downscale and re-encode on the phone: smaller uploads, no EXIF/GPS leaves the device. */
async function compress(asset: ImagePicker.ImagePickerAsset) {
  const longest = Math.max(asset.width, asset.height);
  const resize =
    longest > MAX_SIDE
      ? [{ resize: asset.width >= asset.height ? { width: MAX_SIDE } : { height: MAX_SIDE } }]
      : [];
  return manipulateAsync(asset.uri, resize, { compress: 0.8, format: SaveFormat.JPEG });
}

export default function PortfolioScreen() {
  const { t } = useTranslation();
  const { width } = useWindowDimensions();
  const queryClient = useQueryClient();
  const works = useQuery({ queryKey: ['me', 'works'], queryFn: myWorks });
  const [pending, setPending] = useState<{ uri: string } | null>(null);
  const [description, setDescription] = useState('');
  const [styles_, setStyles] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const count = works.data?.data.length ?? 0;
  const tile = (width - spacing.lg * 2 - spacing.sm * 2) / 3;

  const pick = async (source: 'library' | 'camera') => {
    setError(null);
    const perm =
      source === 'camera'
        ? await ImagePicker.requestCameraPermissionsAsync()
        : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      setError(t('account.photoPermission'));
      return;
    }
    const options: ImagePicker.ImagePickerOptions = {
      mediaTypes: ['images'],
      quality: 1,
      exif: false,
    };
    const result =
      source === 'camera'
        ? await ImagePicker.launchCameraAsync(options)
        : await ImagePicker.launchImageLibraryAsync(options);
    if (result.canceled || !result.assets[0]) return;
    setPending(await compress(result.assets[0]));
  };

  const upload = async () => {
    if (!pending) return;
    setBusy(true);
    setError(null);
    try {
      await uploadWork({ uri: pending.uri, name: 'work.jpg' }, description.trim(), styles_);
      setPending(null);
      setDescription('');
      setStyles([]);
      await queryClient.invalidateQueries({ queryKey: ['me'] });
    } catch (e) {
      setError(errorMessage(e, t('account.uploadError')));
    } finally {
      setBusy(false);
    }
  };

  const remove = (id: number) =>
    Alert.alert(t('account.deleteWork'), undefined, [
      { text: t('account.cancel'), style: 'cancel' },
      {
        text: t('account.remove'),
        style: 'destructive',
        onPress: async () => {
          await deleteWork(id);
          await queryClient.invalidateQueries({ queryKey: ['me'] });
        },
      },
    ]);

  return (
    <FormScreen title={t('account.portfolio', { limit: LIMIT })}>
      {error && <Notice tone="error">{error}</Notice>}

      {pending ? (
        <View style={{ gap: spacing.md }}>
          <Image source={{ uri: pending.uri }} style={styles.preview} contentFit="contain" />
          <Field
            label={t('account.workDescription')}
            value={description}
            onChangeText={setDescription}
          />
          <Text style={styles.label}>{t('account.workStyles')}</Text>
          <StylePicker value={styles_} onChange={setStyles} max={3} />
          <Button label={t('account.publish')} onPress={upload} busy={busy} />
          <Button
            label={t('account.cancel')}
            variant="secondary"
            onPress={() => setPending(null)}
          />
        </View>
      ) : (
        <>
          <Text style={styles.meta}>{t('account.worksCount', { count, max: LIMIT })}</Text>
          <View style={styles.actions}>
            <View style={{ flex: 1 }}>
              <Button
                label={t('account.fromGallery')}
                onPress={() => pick('library')}
                disabled={count >= LIMIT}
              />
            </View>
            <View style={{ flex: 1 }}>
              <Button
                label={t('account.fromCamera')}
                variant="secondary"
                onPress={() => pick('camera')}
                disabled={count >= LIMIT}
              />
            </View>
          </View>
          {works.isLoading ? (
            <StateMessage loading />
          ) : (
            <View style={styles.grid}>
              {works.data?.data.map((w) => (
                <Pressable
                  key={w.id}
                  onLongPress={() => remove(w.id)}
                  accessibilityHint={t('account.longPressDelete')}
                >
                  <Image
                    source={imageSource(w.thumbnail_url)}
                    style={{
                      width: tile,
                      height: tile,
                      borderRadius: 6,
                      backgroundColor: colors.surface,
                    }}
                    contentFit="cover"
                  />
                </Pressable>
              ))}
            </View>
          )}
          {count > 0 && <Text style={styles.meta}>{t('account.longPressDelete')}</Text>}
        </>
      )}
    </FormScreen>
  );
}

const styles = StyleSheet.create({
  preview: { width: '100%', height: 280, backgroundColor: colors.surface, borderRadius: 8 },
  label: { fontSize: 13, fontWeight: '600', color: colors.ink },
  meta: { fontSize: 13, color: colors.muted },
  actions: { flexDirection: 'row', gap: spacing.sm },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
});
