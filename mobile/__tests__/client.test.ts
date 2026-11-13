import { ApiError, apiGet, buildUrl } from '@/api/client';

describe('buildUrl', () => {
  it('joins base and path and skips empty query values', () => {
    const url = buildUrl(
      '/studios',
      { bbox: '1,2,3,4', style: undefined, q: '' },
      'http://x.test/api/v1/',
    );
    expect(url).toBe('http://x.test/api/v1/studios?bbox=1%2C2%2C3%2C4');
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
