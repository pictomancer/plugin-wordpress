import { HashRouter, NavLink, Route, Routes } from 'react-router-dom';
import Logo from './components/Logo';
import Dashboard from './pages/Dashboard';
import Settings from './pages/Settings';

const navClass = ({ isActive }: { isActive: boolean }) =>
  [
    'px-3 py-1.5 rounded-md text-sm transition-colors',
    isActive ? 'bg-white/10 text-white' : 'text-white/60 hover:text-white hover:bg-white/5',
  ].join(' ');

export default function App() {
  return (
    <HashRouter>
      <header className="glass-sm rounded-xl px-5 py-3 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Logo />
          <span className="text-sm font-semibold tracking-tight text-white/90">Pictomancer</span>
        </div>
        <nav className="flex items-center gap-1">
          <NavLink to="/" end className={navClass}>
            Overview
          </NavLink>
          <NavLink to="/settings" className={navClass}>
            Settings
          </NavLink>
        </nav>
      </header>

      <main className="mt-6">
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/settings" element={<Settings />} />
        </Routes>
      </main>
    </HashRouter>
  );
}
