import { useEffect, useState } from 'react';
import { type ApiError, getSettings, saveSettings } from '../api/client';
import type { PluginSettings } from '../types';

const EMPTY: PluginSettings = {
  api_url: '',
  api_key: '',
  quality: '',
  optimize_thumbnails: true,
  debug_mode: false,
};

const inputClass =
  'w-full rounded-lg bg-black/30 border border-surface-border px-3 py-2 text-sm text-white placeholder-white/30 focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/30 disabled:opacity-50';

export default function Settings() {
  const [form, setForm] = useState<PluginSettings>(EMPTY);
  const [locked, setLocked] = useState({ api_key: false, api_url: false });
  const [status, setStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [error, setError] = useState('');

  useEffect(() => {
    getSettings()
      .then((res) => {
        setForm({ ...EMPTY, ...res.settings });
        setLocked(res.overrides);
      })
      .catch((e: ApiError) => setError(e.message));
  }, []);

  function update<K extends keyof PluginSettings>(key: K, value: PluginSettings[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function handleSave() {
    setStatus('saving');
    setError('');
    const payload: Partial<PluginSettings> = {
      quality: form.quality,
      optimize_thumbnails: form.optimize_thumbnails,
      debug_mode: form.debug_mode,
    };
    if (!locked.api_url) payload.api_url = form.api_url;
    if (!locked.api_key) payload.api_key = form.api_key;

    saveSettings(payload)
      .then(() => setStatus('saved'))
      .catch((e: ApiError) => {
        setError(e.message);
        setStatus('error');
      });
  }

  return (
    <section className="max-w-2xl">
      <h1 className="text-2xl font-semibold tracking-tight">Settings</h1>

      <div className="glass mt-6 rounded-xl p-6 space-y-5">
        <div>
          <label htmlFor="api_url" className="block text-sm font-medium text-white/70 mb-1">
            API URL
          </label>
          <input
            id="api_url"
            type="text"
            className={inputClass}
            placeholder="https://api.pictomancer.ai"
            value={locked.api_url ? '' : form.api_url}
            disabled={locked.api_url}
            onChange={(e) => update('api_url', e.target.value)}
          />
          <p className="mt-1 text-xs text-white/40">
            {locked.api_url
              ? 'Defined by the PICTOMANCER_API_URL constant (read-only).'
              : 'Host root. Leave empty for https://api.pictomancer.ai. Do not append /v1.'}
          </p>
        </div>

        <div>
          <label htmlFor="api_key" className="block text-sm font-medium text-white/70 mb-1">
            API Key
          </label>
          <input
            id="api_key"
            type="password"
            autoComplete="off"
            className={inputClass}
            placeholder={locked.api_key ? 'Set via PICTOMANCER_API_KEY' : ''}
            value={locked.api_key ? '' : form.api_key}
            disabled={locked.api_key}
            onChange={(e) => update('api_key', e.target.value)}
          />
          <p className="mt-1 text-xs text-white/40">
            {locked.api_key
              ? 'Defined by the PICTOMANCER_API_KEY constant (read-only, never stored in the database).'
              : 'Bearer key from your dashboard. Leave empty to use the free tier.'}
          </p>
        </div>

        <div>
          <label htmlFor="quality" className="block text-sm font-medium text-white/70 mb-1">
            Quality
          </label>
          <input
            id="quality"
            type="number"
            min={0}
            max={100}
            className={`${inputClass} max-w-32`}
            value={form.quality}
            onChange={(e) => update('quality', e.target.value)}
          />
          <p className="mt-1 text-xs text-white/40">1-100. Leave empty for the API default.</p>
        </div>

        <label className="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            className="mt-0.5 h-4 w-4 accent-accent"
            checked={form.optimize_thumbnails}
            onChange={(e) => update('optimize_thumbnails', e.target.checked)}
          />
          <span className="text-sm text-white/80">
            Optimize every generated thumbnail size, not just the original.
            <span className="block text-xs text-white/40">Each size is billed as one request.</span>
          </span>
        </label>

        <label className="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            className="mt-0.5 h-4 w-4 accent-accent"
            checked={form.debug_mode}
            onChange={(e) => update('debug_mode', e.target.checked)}
          />
          <span className="text-sm text-white/80">
            Enable debug logging
            <span className="block text-xs text-white/40">
              Detailed optimization and API traces.
            </span>
          </span>
        </label>

        <div className="flex items-center gap-3 pt-2">
          <button
            type="button"
            onClick={handleSave}
            disabled={status === 'saving'}
            className="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white shadow-glow transition hover:bg-accent-bright disabled:opacity-50"
          >
            {status === 'saving' ? 'Saving…' : 'Save Settings'}
          </button>
          {status === 'saved' && <span className="text-sm text-lime">Saved.</span>}
          {error && <span className="text-sm text-red-400">{error}</span>}
        </div>
      </div>
    </section>
  );
}
