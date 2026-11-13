import { ApiError, apiGet, buildUrl } from '@/api/client';
import { roundBounds } from '@/api/endpoints';

describe('buildUrl', () => {
  it('joins base and path, encodes booleans and skips empty values', () => {
    const url = buildUrl(
      '/studios',
      { bbox: '1,2,3,4', style: undefined, q: '', guest_upcoming: true, other: false },
      'http://x.test/api/v1/',
    );
    expect(url).toBe('http://x.test/api/v1/studios?bbox=1%2C2%2C3%2C4&guest_upcoming=1');
  });
});

describe('apiGet', () => {
  afterEach(() => jest.restoreAllMocks());

  it('throws ApiError on non-2xx responses', async () => {
    jest
      .spyOn(globalThis, 'fetch')
      .mockResolvedValue(new Response(JSON.stringify({ message: 'nope' }), { status: 422 }));
    await expect(apiGet('/studios')).rejects.toBeInstanceOf(ApiError);
  });
});

it('rounds map bounds to ~100 m to keep cache keys stable', () => {
  expect(roundBounds([16.87191, 41.11712, 16.92349, 41.15001])).toEqual([
    16.872, 41.117, 16.923, 41.15,
  ]);
});
