import { getLocales } from 'expo-localization';
import { createInstance } from 'i18next';
import { initReactI18next } from 'react-i18next';

import en from './locales/en.json';
import it from './locales/it.json';

export const supportedLanguages = ['it', 'en'] as const;

export function pickLanguage(deviceLanguage: string | null | undefined): 'it' | 'en' {
  return deviceLanguage === 'en' ? 'en' : 'it';
}

const i18n = createInstance();

i18n.use(initReactI18next).init({
  resources: { it: { translation: it }, en: { translation: en } },
  lng: pickLanguage(getLocales()[0]?.languageCode),
  fallbackLng: 'it',
  interpolation: { escapeValue: false },
});

export default i18n;
