import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError, apiJson } from './client';

describe('apiJson', () => {
  beforeEach(() => {
    window.pictomancerData = { restUrl: 'http://wp.test/wp-json/pictomancer/v1/', nonce: 'abc123' };
  });

  afterEach(() => {
    vi.restoreAllMocks();
    window.pictomancerData = undefined;
  });

  it('sends the REST nonce and resolves JSON', async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ files: 3 }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    );
    vi.stubGlobal('fetch', fetchMock);

    const result = await apiJson<{ files: number }>('/stats');

    expect(result).toEqual({ files: 3 });
    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe('http://wp.test/wp-json/pictomancer/v1/stats');
    expect((init.headers as Record<string, string>)['X-WP-Nonce']).toBe('abc123');
  });

  it('throws ApiError with the server message on a non-2xx response', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: 'nope' }), {
          status: 403,
          headers: { 'Content-Type': 'application/json' },
        }),
      ),
    );

    await expect(apiJson('/settings')).rejects.toMatchObject({
      name: 'ApiError',
      status: 403,
      message: 'nope',
    });
    await expect(apiJson('/settings')).rejects.toBeInstanceOf(ApiError);
  });
});
