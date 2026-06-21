interface StatCardProps {
  label: string;
  value: string;
  hint: string;
  tone?: 'default' | 'good' | 'bad';
}

export default function StatCard({ label, value, hint, tone = 'default' }: StatCardProps) {
  const valueColor =
    tone === 'good' ? 'text-lime' : tone === 'bad' ? 'text-red-400' : 'text-white';

  return (
    <div className="glass glass-hover rounded-xl p-5">
      <div className="text-xs font-medium uppercase tracking-wide text-white/50">{label}</div>
      <div className={`mt-2 text-2xl font-semibold ${valueColor}`}>{value}</div>
      <div className="mt-1 text-xs text-white/40">{hint}</div>
    </div>
  );
}
