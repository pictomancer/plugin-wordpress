export interface Stats {
  files: number;
  original_bytes: number;
  optimized_bytes: number;
  bytes_saved: number;
  reduction_pct: number;
  enabled: boolean;
  api: { ok: boolean; detail: string };
}

export interface PluginSettings {
  enabled: boolean;
  api_url: string;
  api_key: string;
  quality: string;
  compression_mode: 'fixed' | 'quality_target';
  quality_target: string;
  optimize_thumbnails: boolean;
  debug_mode: boolean;
}

export interface SettingsResponse {
  settings: PluginSettings;
  // Keys defined by a wp-config constant; the matching field is read-only.
  overrides: { api_key: boolean; api_url: boolean };
}
