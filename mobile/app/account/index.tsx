import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { Linking, Pressable, StyleSheet, Text, View } from 'react-native';

import { getMyArtist } from '@/api/account';
import { useAuth } from '@/auth/AuthProvider';
import { Button, Notice } from '@/components/Form';
import { FormScreen } from '@/components/Screen';
import { StateMessage } from '@/components/StateMessage';
import { isDemoMode } from '@/config/mode';
import { getWebUrl } from '@/config/server';
import { colors, spacing } from '@/theme';

export default function AccountScreen() {
  const { t } = useTranslation();
  const { user, ready, signOut } = useAuth();
  const demo = isDemoMode();
  const artist = useQuery({
    queryKey: ['me', 'artist', user?.id],
    queryFn: getMyArtist,
    enabled: !!user && !demo,
  });

  if (demo) {
    return (
      <FormScreen title={t('account.title')}>
        <Notice>{t('account.demoUnavailable')}</Notice>
        <Button
          label={t('settings.title')}
          variant="secondary"
          onPress={() => router.push('/settings')}
        />
      </FormScreen>
    );
  }

  if (!ready) return <StateMessage loading />;

  if (!user) {
    return (
      <FormScreen title={t('account.title')}>
        <Text style={styles.lead}>{t('account.guestLead')}</Text>
        <Button
          label={t('account.login')}
          onPress={() => router.push('/account/login')}
          testID="go-login"
        />
        <Button
          label={t('account.register')}
          variant="secondary"
          onPress={() => router.push('/account/register')}
        />
      </FormScreen>
    );
  }

  const profile = artist.data?.data;
  const meta = artist.data?.meta;

  return (
    <FormScreen title={t('account.title')}>
      <View>
        <Text style={styles.name}>{user.name}</Text>
        <Text style={styles.meta}>{user.email}</Text>
      </View>

      {artist.isLoading ? (
        <StateMessage loading />
      ) : profile ? (
        <>
          <Notice>
            {meta?.is_public ? t('account.profilePublic') : t('account.profilePending')}
          </Notice>
          <MenuItem
            label={t('account.editProfile')}
            onPress={() => router.push('/account/artist')}
          />
          <MenuItem
            label={t('account.myStudios')}
            onPress={() => router.push('/account/studios')}
          />
          <MenuItem
            label={t('account.portfolio', { limit: meta?.works_limit ?? 12 })}
            onPress={() => router.push('/account/portfolio')}
          />
          {meta?.is_public && (
            <MenuItem
              label={t('account.viewPublic')}
              onPress={() =>
                router.push({ pathname: '/artist/[slug]', params: { slug: profile.slug } })
              }
            />
          )}
        </>
      ) : (
        <>
          <Text style={styles.lead}>{t('account.becomeArtist')}</Text>
          <Button
            label={t('account.createProfile')}
            onPress={() => router.push('/account/artist')}
          />
        </>
      )}

      <Button label={t('account.logout')} variant="secondary" onPress={signOut} />
      <MenuItem
        label={t('account.privacyLink')}
        onPress={() => Linking.openURL(`${getWebUrl()}/privacy`)}
      />
      <MenuItem label={t('account.deleteTitle')} onPress={() => router.push('/account/delete')} />
    </FormScreen>
  );
}

function MenuItem({ label, onPress }: { label: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={styles.item} accessibilityRole="button">
      <Text style={styles.itemText}>{label}</Text>
      <Text style={styles.chevron}>›</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  name: { fontSize: 22, fontWeight: '800', color: colors.ink },
  meta: { fontSize: 14, color: colors.muted },
  lead: { fontSize: 15, color: colors.ink, lineHeight: 21 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.md,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.line,
  },
  itemText: { flex: 1, fontSize: 16, color: colors.ink },
  chevron: { fontSize: 22, color: colors.muted },
});
