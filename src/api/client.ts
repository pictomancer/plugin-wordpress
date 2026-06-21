import type { PluginSettings, SettingsResponse, Stats } from '../types';

export class ApiError extends Error {
  status: number;

  constructor(status: number, message: string) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

function config(): PictomancerData {
  return window.pictomancerData ?? { restUrl: '/wp-json/pictomancer/v1/', nonce: '' };
}

export async function apiJson<T>(path: string, init: RequestInit = {}): Promise<T> {
  const { restUrl, nonce } = config();
  const base = restUrl.replace(/\/$/, '');

  const res = await fetch(`${base}${path}`, {
    credentials: 'same-origin',
    ...init,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...(init.headers ?? {}),
    },
  });

  if (!res.ok) {
    const message = await res
      .json()
      .then((d: { message?: string }) => d.message)
      .catch(() => undefined);
    throw new ApiError(res.status, message ?? `Request failed: ${res.status}`);
  }

  return (await res.json()) as T;
}

export const getStats = () => apiJson<Stats>('/stats');

export const getSettings = () => apiJson<SettingsResponse>('/settings');

export const saveSettings = (settings: Partial<PluginSettings>) =>
  apiJson<{ success: boolean }>('/settings', {
    method: 'POST',
    body: JSON.stringify(settings),
  });
