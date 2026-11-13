import { pickLanguage } from '@/i18n';

it('falls back to Italian for unsupported device languages', () => {
  expect(pickLanguage('en')).toBe('en');
  expect(pickLanguage('de')).toBe('it');
  expect(pickLanguage(undefined)).toBe('it');
});
