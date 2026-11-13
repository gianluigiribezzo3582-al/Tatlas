import { fireEvent, render, screen } from '@testing-library/react-native';

import { StudioRow } from '@/components/StudioRow';
import '@/i18n';

it('shows name, address, artists, guest badge and distance', async () => {
  const onPress = jest.fn();
  await render(
    <StudioRow
      onPress={onPress}
      studio={{
        id: 1,
        slug: 'x',
        name: 'Inchiostro',
        location: { lat: 41.1, lng: 16.8 },
        address: {
          street: 'Via Roma',
          housenumber: '1',
          postcode: '70121',
          city: 'Bari',
          country_code: 'IT',
        },
        distance_m: 1500,
        artists_count: 3,
        has_upcoming_guest: true,
        source: 'osm',
      }}
    />,
  );

  expect(screen.getByText('Inchiostro')).toBeTruthy();
  expect(screen.getByText('Via Roma 1, 70121 Bari')).toBeTruthy();
  expect(screen.getByText('3 artisti')).toBeTruthy();
  expect(screen.getByText('Guest in arrivo')).toBeTruthy();
  expect(screen.getByText('1,5 km')).toBeTruthy();

  await fireEvent.press(screen.getByTestId('studio-row-1'));
  expect(onPress).toHaveBeenCalled();
});
