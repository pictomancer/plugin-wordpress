import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import type { Stats } from '../types';
import Dashboard from './Dashboard';

const stats: Stats = {
  files: 7,
  original_bytes: 1000,
  optimized_bytes: 250,
  bytes_saved: 750,
  reduction_pct: 75,
  api: { ok: true, detail: '1.0.0' },
};

vi.mock('../api/client', () => ({
  getStats: () => Promise.resolve(stats),
}));

describe('Dashboard', () => {
  it('renders real savings from the stats endpoint', async () => {
    render(<Dashboard />);

    expect(await screen.findByText('750 B')).toBeInTheDocument();
    expect(screen.getByText('75%')).toBeInTheDocument();
    expect(screen.getByText('7')).toBeInTheDocument();
    expect(screen.getByText('Operational')).toBeInTheDocument();
  });
});
