import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// The admin app is a single self-contained IIFE bundle enqueued by WordPress.
// CSS is imported via `?inline` and injected into a Shadow DOM at runtime, so no
// stylesheet is emitted and nothing leaks in or out of the wp-admin chrome.
export default defineConfig({
  plugins: [react()],
  // Lib mode does not inline `process.env`; React/react-router read it at runtime,
  // so without this the bundle throws "process is not defined" in the browser.
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env': '{}',
  },
  build: {
    outDir: 'build',
    emptyOutDir: true,
    cssCodeSplit: false,
    lib: {
      entry: 'src/main.tsx',
      formats: ['iife'],
      name: 'PictomancerAdmin',
      fileName: () => 'pictomancer-admin.js',
    },
  },
});
