/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        ink: { DEFAULT: '#0D0D0D', 50: '#F5F5F5', 100: '#E8E8E8', 200: '#C8C8C8', 300: '#A0A0A0', 400: '#707070', 500: '#484848', 600: '#2E2E2E', 700: '#1A1A1A', 800: '#111111', 900: '#0D0D0D' },
        gold: { DEFAULT: '#C9A84C', 50: '#FDF8EC', 100: '#F8ECC8', 300: '#E8C35A', 400: '#C9A84C', 500: '#A88A38', 700: '#5E4D1B' },
        cream: { DEFAULT: '#F5F0E8', light: '#FAF8F4', dark: '#E8E0D0' },
      },
      fontFamily: {
        display: ['Playfair Display', 'Georgia', 'serif'],
        sans: ['DM Sans', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
