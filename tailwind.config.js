/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{js,jsx,ts,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Outfit"', 'system-ui', 'sans-serif'],
      },
      colors: {
        surface: {
          DEFAULT: 'rgba(255,255,255,0.04)',
          card: 'rgba(255,255,255,0.06)',
          hover: 'rgba(255,255,255,0.07)',
          active: 'rgba(255,255,255,0.10)',
          border: 'rgba(255,255,255,0.08)',
          'border-hover': 'rgba(255,255,255,0.14)',
        },
        accent: {
          DEFAULT: '#a855f7',
          dim: '#7c3aed',
          bright: '#c084fc',
          glow: 'rgba(168,85,247,0.15)',
        },
        lime: '#C8FF00',
      },
      backdropBlur: {
        glass: '20px',
      },
      boxShadow: {
        glass: '0 8px 32px rgba(0,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.05)',
        'glass-sm': '0 4px 16px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.04)',
        glow: '0 0 24px rgba(168,85,247,0.2)',
      },
    },
  },
  plugins: [],
};
