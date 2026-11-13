import { getApiUrl, loadApiUrl, normalizeApiUrl, saveApiUrl } from '@/config/server';

describe('normalizeApiUrl', () => {
  it.each([
    ['192.168.1.20:8000', 'http://192.168.1.20:8000/api/v1'],
    ['http://192.168.1.20:8000/', 'http://192.168.1.20:8000/api/v1'],
    ['https://api.tatlas.app/api/v1', 'https://api.tatlas.app/api/v1'],
  ])('%s → %s', (input, expected) => {
    expect(normalizeApiUrl(input)).toBe(expected);
  });

  it('rejects empty input', () => {
    expect(normalizeApiUrl('   ')).toBeNull();
  });
});

it('persists the chosen server and restores the default on reset', async () => {
  await saveApiUrl('http://10.0.0.5:8000/api/v1');
  expect(getApiUrl()).toBe('http://10.0.0.5:8000/api/v1');
  expect(await loadApiUrl()).toBe('http://10.0.0.5:8000/api/v1');

  await saveApiUrl(null);
  expect(getApiUrl()).toBe('http://10.0.2.2:8000/api/v1');
});
