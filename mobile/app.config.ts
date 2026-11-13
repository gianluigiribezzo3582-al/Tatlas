import type { ExpoConfig } from 'expo/config';

const apiUrl = process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8000/api/v1';
// Test builds talk to a backend on the LAN over plain HTTP; production builds must not.
const allowCleartext = process.env.TATLAS_ALLOW_CLEARTEXT === '1';
const publicUrl = process.env.EXPO_PUBLIC_WEB_URL ?? 'https://tatlas.app';

const config: ExpoConfig = {
  name: 'Tatlas',
  slug: 'tatlas',
  scheme: 'tatlas',
  version: '0.1.0',
  orientation: 'portrait',
  icon: './assets/icon.png',
  userInterfaceStyle: 'light',
  ios: {
    supportsTablet: false,
    bundleIdentifier: 'app.tatlas',
  },
  android: {
    package: 'app.tatlas',
    adaptiveIcon: {
      backgroundColor: '#111111',
      foregroundImage: './assets/android-icon-foreground.png',
      backgroundImage: './assets/android-icon-background.png',
      monochromeImage: './assets/android-icon-monochrome.png',
    },
    predictiveBackGestureEnabled: false,
    permissions: ['ACCESS_COARSE_LOCATION', 'ACCESS_FINE_LOCATION'],
    blockedPermissions: ['ACCESS_BACKGROUND_LOCATION'],
  },
  web: {
    favicon: './assets/favicon.png',
  },
  plugins: [
    'expo-router',
    'expo-localization',
    '@maplibre/maplibre-react-native',
    [
      'expo-location',
      {
        locationWhenInUsePermission:
          "Tatlas usa la tua posizione solo mentre usi l'app, per mostrarti gli studi vicini.",
        isAndroidBackgroundLocationEnabled: false,
      },
    ],
    ['expo-build-properties', { android: { usesCleartextTraffic: allowCleartext } }],
  ],
  experiments: {
    typedRoutes: true,
  },
  extra: {
    apiUrl,
    publicUrl,
  },
};

export default config;
