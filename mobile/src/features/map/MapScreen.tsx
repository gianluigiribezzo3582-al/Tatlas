import {
  Camera,
  type CameraRef,
  GeoJSONSource,
  type GeoJSONSourceRef,
  Layer,
  Map,
  NativeUserLocation,
} from '@maplibre/maplibre-react-native';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FlatList, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { type Bounds, fetchStudios, fetchStyles, roundBounds } from '@/api/endpoints';
import { BottomSheet } from '@/components/BottomSheet';
import { Chip } from '@/components/Chip';
import { StateMessage } from '@/components/StateMessage';
import { StudioRow } from '@/components/StudioRow';
import { useUserLocation } from '@/features/location/useUserLocation';
import { useDebounced } from '@/lib/useDebounced';
import { DEFAULT_CENTER, DEFAULT_ZOOM, tileSource } from '@/map/tiles';
import { colors, spacing } from '@/theme';

import { studiosToGeoJSON } from './geojson';

export function MapScreen() {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();
  const camera = useRef<CameraRef>(null);
  const source = useRef<GeoJSONSourceRef>(null);
  const location = useUserLocation();
  const centeredOnUser = useRef(false);

  const [bounds, setBounds] = useState<Bounds | null>(null);
  const [style, setStyle] = useState<string | null>(null);
  const [guestUpcoming, setGuestUpcoming] = useState(false);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const debouncedBounds = useDebounced(bounds, 350);

  const styles_ = useQuery({ queryKey: ['styles'], queryFn: fetchStyles, staleTime: 3_600_000 });

  const studios = useQuery({
    queryKey: [
      'studios',
      debouncedBounds && roundBounds(debouncedBounds),
      style,
      guestUpcoming,
      location.coords,
    ],
    queryFn: ({ signal }) =>
      fetchStudios(debouncedBounds!, { style, guestUpcoming }, location.coords, signal),
    enabled: debouncedBounds !== null,
    placeholderData: keepPreviousData,
  });

  const list = useMemo(() => studios.data?.data ?? [], [studios.data]);
  const geojson = useMemo(() => studiosToGeoJSON(list), [list]);

  // Centre on the user once, the first time a position becomes available.
  useEffect(() => {
    if (location.coords && !centeredOnUser.current) {
      centeredOnUser.current = true;
      camera.current?.easeTo({
        center: [location.coords.lng, location.coords.lat],
        zoom: 13,
        duration: 600,
      });
    }
  }, [location.coords]);

  const onSourcePress = async (feature: GeoJSON.Feature | undefined) => {
    if (!feature || feature.geometry.type !== 'Point') return;
    const [lng, lat] = feature.geometry.coordinates;
    const props = feature.properties ?? {};
    if (props.cluster) {
      const zoom = await source.current?.getClusterExpansionZoom(props.cluster_id as number);
      camera.current?.easeTo({ center: [lng, lat], zoom: zoom ?? 14, duration: 400 });
      return;
    }
    setSelectedId(Number(props.id));
  };

  const goToMe = () => {
    if (location.status === 'granted' && location.coords) {
      camera.current?.easeTo({
        center: [location.coords.lng, location.coords.lat],
        zoom: 14,
        duration: 500,
      });
      location.refresh();
    } else {
      location.request();
    }
  };

  const ordered = useMemo(() => {
    if (selectedId === null) return list;
    const sel = list.find((s) => s.id === selectedId);
    return sel ? [sel, ...list.filter((s) => s.id !== selectedId)] : list;
  }, [list, selectedId]);

  return (
    <View style={styles.container}>
      <Map
        style={StyleSheet.absoluteFill}
        mapStyle={tileSource.styleUrl}
        attribution
        attributionPosition={{ top: insets.top + 100, right: 8 }}
        logo={false}
        compass={false}
        onRegionDidChange={(e) => setBounds(e.nativeEvent.bounds)}
        onPress={() => setSelectedId(null)}
      >
        <Camera ref={camera} initialViewState={{ center: DEFAULT_CENTER, zoom: DEFAULT_ZOOM }} />
        {location.status === 'granted' && <NativeUserLocation />}
        <GeoJSONSource
          id="studios"
          ref={source}
          data={geojson}
          cluster
          clusterRadius={45}
          clusterMaxZoom={15}
          hitbox={{ top: 12, right: 12, bottom: 12, left: 12 }}
          onPress={(e) => onSourcePress(e.nativeEvent.features[0])}
        >
          <Layer
            type="circle"
            id="studio-clusters"
            filter={['has', 'point_count']}
            paint={{
              'circle-color': colors.ink,
              'circle-opacity': 0.85,
              'circle-radius': ['step', ['get', 'point_count'], 16, 10, 20, 50, 26],
              'circle-stroke-color': colors.paper,
              'circle-stroke-width': 2,
            }}
          />
          <Layer
            type="symbol"
            id="studio-cluster-count"
            filter={['has', 'point_count']}
            layout={{
              'text-field': ['get', 'point_count_abbreviated'],
              'text-font': ['Noto Sans Bold'],
              'text-size': 13,
              'text-allow-overlap': true,
            }}
            paint={{ 'text-color': colors.paper }}
          />
          <Layer
            type="circle"
            id="studio-points"
            filter={['!', ['has', 'point_count']]}
            paint={{
              'circle-color': ['case', ['get', 'guest'], colors.guest, colors.accent],
              'circle-radius': ['case', ['==', ['get', 'id'], selectedId ?? -1], 11, 8],
              'circle-stroke-color': colors.paper,
              'circle-stroke-width': 2,
            }}
          />
        </GeoJSONSource>
      </Map>

      <View style={[styles.top, { paddingTop: insets.top + spacing.sm }]} pointerEvents="box-none">
        <View style={styles.titleRow}>
          <Text style={styles.brand}>Tatlas</Text>
          <Pressable
            onPress={() => router.push('/settings')}
            style={styles.iconButton}
            accessibilityLabel={t('settings.title')}
            testID="open-settings"
          >
            <Text style={styles.iconText}>⚙︎</Text>
          </Pressable>
        </View>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.chips}
        >
          <Chip
            label={t('filters.guestUpcoming')}
            tone="guest"
            selected={guestUpcoming}
            onPress={() => setGuestUpcoming((v) => !v)}
          />
          {(styles_.data?.data ?? []).map((s) => (
            <Chip
              key={s.slug}
              label={s.name}
              selected={style === s.slug}
              onPress={() => setStyle((cur) => (cur === s.slug ? null : s.slug))}
            />
          ))}
        </ScrollView>
        {location.status === 'undetermined' || location.status === 'denied' ? (
          <Pressable onPress={location.request} style={styles.banner} accessibilityRole="button">
            <Text style={styles.bannerText}>
              {location.status === 'denied' && !location.canAskAgain
                ? t('location.openSettings')
                : t('location.ask')}
            </Text>
          </Pressable>
        ) : null}
      </View>

      <Pressable
        onPress={goToMe}
        style={[styles.fab, { bottom: '32%' }]}
        accessibilityLabel={t('location.me')}
        testID="locate-me"
      >
        <Text style={styles.iconText}>◎</Text>
      </Pressable>

      <BottomSheet
        header={
          <View style={styles.sheetHeader}>
            <Text style={styles.sheetTitle}>
              {studios.isError
                ? t('map.error')
                : t('map.studiosInArea', { count: studios.data?.meta?.count ?? list.length })}
            </Text>
            {studios.isFetching && <Text style={styles.sheetMeta}>{t('common.loading')}</Text>}
          </View>
        }
      >
        {studios.isError ? (
          <StateMessage
            message={t('map.errorHint')}
            actionLabel={t('settings.title')}
            onAction={() => router.push('/settings')}
          />
        ) : (
          <FlatList
            data={ordered}
            keyExtractor={(s) => String(s.id)}
            renderItem={({ item }) => (
              <StudioRow
                studio={item}
                selected={item.id === selectedId}
                onPress={() => router.push({ pathname: '/studio/[id]', params: { id: item.id } })}
              />
            )}
            ListEmptyComponent={
              studios.isLoading ? (
                <StateMessage loading />
              ) : (
                <StateMessage message={t('map.empty')} />
              )
            }
            ListFooterComponent={<Text style={styles.attribution}>{t('map.attribution')}</Text>}
          />
        )}
      </BottomSheet>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.surface },
  top: { position: 'absolute', left: 0, right: 0, top: 0, gap: spacing.sm },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
  },
  brand: {
    fontSize: 22,
    fontWeight: '800',
    color: colors.ink,
    backgroundColor: 'rgba(255,255,255,0.85)',
    paddingHorizontal: 10,
    borderRadius: 8,
    overflow: 'hidden',
  },
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 4,
  },
  iconText: { fontSize: 20, color: colors.ink },
  chips: { gap: spacing.sm, paddingHorizontal: spacing.lg },
  banner: {
    marginHorizontal: spacing.lg,
    backgroundColor: colors.ink,
    borderRadius: 10,
    padding: spacing.md,
  },
  bannerText: { color: colors.paper, fontSize: 14 },
  fab: {
    position: 'absolute',
    right: spacing.lg,
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.paper,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 6,
  },
  sheetHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'baseline',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
  },
  sheetTitle: { fontSize: 17, fontWeight: '700', color: colors.ink },
  sheetMeta: { fontSize: 12, color: colors.muted },
  attribution: { fontSize: 11, color: colors.muted, padding: spacing.lg, textAlign: 'center' },
});
