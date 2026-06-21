import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import css from './index.css?inline';

// Mount inside a Shadow DOM: wp-admin styles cannot reach us and our Tailwind
// preflight cannot reach wp-admin. The compiled CSS is injected into the root.
const host = document.getElementById('pictomancer-admin');
if (host && !host.shadowRoot) {
  const shadow = host.attachShadow({ mode: 'open' });

  const style = document.createElement('style');
  style.textContent = css;
  shadow.appendChild(style);

  const mount = document.createElement('div');
  mount.className = 'pic-root';
  shadow.appendChild(mount);

  createRoot(mount).render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}
