import { render, screen } from '@testing-library/react-native';

import '@/i18n';
import HomeScreen from '../app/index';

jest.mock('expo-localization', () => ({
  getLocales: () => [{ languageCode: 'it' }],
}));

it('renders the app name and Italian tagline for an Italian device', async () => {
  await render(<HomeScreen />);
  expect(screen.getByText('Tatlas')).toBeTruthy();
  expect(screen.getByText('Trova il tatuatore giusto vicino a te')).toBeTruthy();
});
