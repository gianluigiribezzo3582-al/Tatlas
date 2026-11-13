import Constants from 'expo-constants';

type Extra = { apiUrl: string; publicUrl: string };

const extra = (Constants.expoConfig?.extra ?? {}) as Partial<Extra>;

export const env: Extra = {
  apiUrl: extra.apiUrl ?? 'http://10.0.2.2:8000/api/v1',
  publicUrl: extra.publicUrl ?? 'https://tatlas.app',
};
