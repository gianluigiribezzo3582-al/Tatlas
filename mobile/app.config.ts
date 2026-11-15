import type { ExpoConfig } from 'expo/config';

const apiUrl = process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8000/api/v1';
const publicUrl = process.env.EXPO_PUBLIC_WEB_URL ?? 'https://tatlas.app';
const publicHost = new URL(publicUrl).host;
// Test builds talk to a backend on the LAN over plain HTTP; production builds must not.
const allowCleartext = process.env.TATLAS_ALLOW_CLEARTEXT === '1';

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
    associatedDomains: [`applinks:${publicHost}`],
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
    // Android App Links: public web pages open in the app once
    // https://<host>/.well-known/assetlinks.json lists this app's signing key.
    intentFilters: [
      {
        action: 'VIEW',
        autoVerify: true,
        data: [
          { scheme: 'https', host: publicHost, pathPrefix: '/artisti/' },
          { scheme: 'https', host: publicHost, pathPrefix: '/studi/' },
        ],
        category: ['BROWSABLE', 'DEFAULT'],
      },
    ],
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
    [
      'expo-image-picker',
      {
        photosPermission: 'Tatlas accede alle foto solo per caricare i lavori del tuo portfolio.',
        cameraPermission:
          'Tatlas usa la fotocamera solo per fotografare i lavori del tuo portfolio.',
        microphonePermission: false,
      },
    ],
    'expo-secure-store',
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
