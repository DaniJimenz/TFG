/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './templates/**/*.{html,twig}',
    './assets/**/*.{js,jsx,ts,tsx,vue}',
  ],
  theme: {
    extend: {
      colors: {
        'primary': 'var(--bg-primary)',
        'card': 'var(--bg-card)',
        'accent': 'var(--accent)',
        'text-main': 'var(--text-main)',
        'text-muted': 'var(--text-muted)',
        'error': 'var(--error)',
      },
      backgroundColor: {
        'primary': 'var(--bg-primary)',
        'card': 'var(--bg-card)',
      },
      textColor: {
        'text-main': 'var(--text-main)',
        'text-muted': 'var(--text-muted)',
        'accent': 'var(--accent)',
      },
      borderColor: {
        'card': 'var(--bg-card)',
      },
      fontFamily: {
        'main': 'var(--font-main)',
      },
    },
  },
  plugins: [],
};
