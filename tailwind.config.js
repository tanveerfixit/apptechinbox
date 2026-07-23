/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./*.php",
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          red: '#f25022',
          green: '#7fba00',
          blue: '#00a4ef',
          yellow: '#ffb900',
          teal: '#008272',
        },
        fluent: {
          bg: '#f3f3f3',
          card: '#ffffff',
          border: '#e0e0e0',
          text: '#242424',
          muted: '#5c5c5c',
        }
      },
      fontFamily: {
        sans: ['Outfit', 'Segoe UI', 'system-ui', '-apple-system', 'sans-serif'],
      },
      boxShadow: {
        fluent: '0 1.6px 3.6px 0 rgba(0, 0, 0, 0.11), 0 0.3px 0.9px 0 rgba(0, 0, 0, 0.08)',
        'fluent-hover': '0 3.2px 7.2px 0 rgba(0, 0, 0, 0.13), 0 0.6px 1.8px 0 rgba(0, 0, 0, 0.11)',
      }
    },
  },
  plugins: [],
}
