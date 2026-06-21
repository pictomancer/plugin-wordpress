import { useEffect, useState } from 'react';
import { type ApiError, getStats } from '../api/client';
import StatCard from '../components/StatCard';
import type { Stats } from '../types';

function humanizeBytes(bytes: number): string {
  if (!bytes || bytes < 1024) return `${bytes || 0} B`;
  const units = ['KB', 'MB', 'GB', 'TB'];
  let value = bytes / 1024;
  let unit = 0;
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit += 1;
  }
  return `${value.toFixed(1)} ${units[unit]}`;
}

export default function Dashboard() {
  const [stats, setStats] = useState<Stats | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    getStats()
      .then(setStats)
      .catch((e: ApiError) => setError(e.message));
  }, []);

  return (
    <section>
      <h1 className="text-2xl font-semibold tracking-tight">Overview</h1>

      {error && <p className="mt-4 text-sm text-red-400">{error}</p>}
      {!stats && !error && <p className="mt-4 text-sm text-white/50">Loading…</p>}

      {stats && (
        <>
          <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
              label="Media Saved"
              value={humanizeBytes(stats.bytes_saved)}
              hint="Total bytes saved across optimized files"
              tone="good"
            />
            <StatCard
              label="Reduction"
              value={`${stats.reduction_pct}%`}
              hint="Average size reduction"
            />
            <StatCard
              label="Files Optimized"
              value={String(stats.files)}
              hint="Images and thumbnails optimized"
            />
            <StatCard
              label="API Health"
              value={stats.api.ok ? 'Operational' : 'Unavailable'}
              hint={stats.api.detail}
              tone={stats.api.ok ? 'good' : 'bad'}
            />
          </div>

          {stats.files === 0 && (
            <p className="mt-6 text-sm text-white/50">
              No images optimized yet. Upload an image to the Media Library to see your savings
              here.
            </p>
          )}
        </>
      )}
    </section>
  );
}
