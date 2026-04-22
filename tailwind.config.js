/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.{vue,js}',
  ],
  theme: {
    extend: {
      colors: {
        ink: {
          50:  '#f6f7fb',
          100: '#eceef6',
          200: '#d4d8e8',
          300: '#aab1cc',
          400: '#7d86a8',
          500: '#5b6586',
          600: '#454e6b',
          700: '#363d54',
          800: '#272d3e',
          900: '#171a26',
          950: '#0c0e16',
        },
        sky2: {
          50:  '#eef6ff',
          100: '#d9eaff',
          200: '#bcd9ff',
          300: '#8ec0ff',
          400: '#5b9eff',
          500: '#357cff',
          600: '#1f5cf5',
          700: '#1a48d6',
          800: '#1a3da8',
          900: '#1c3884',
        },
        plum: {
          50:  '#faf5ff',
          100: '#f3e8ff',
          200: '#e9d5ff',
          300: '#d8b4fe',
          400: '#c084fc',
          500: '#a855f7',
          600: '#9333ea',
          700: '#7e22ce',
          800: '#6b21a8',
          900: '#581c87',
        },
        sun: {
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
        },
      },
      fontFamily: {
        display: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        glow: '0 18px 40px -12px rgba(31, 92, 245, 0.45)',
        ring: '0 0 0 4px rgba(168, 85, 247, 0.18)',
        soft: '0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px -12px rgba(15, 23, 42, 0.16)',
      },
      backgroundImage: {
        'mesh': 'radial-gradient(at 0% 0%, rgba(31,92,245,0.18) 0px, transparent 50%), radial-gradient(at 100% 0%, rgba(168,85,247,0.18) 0px, transparent 50%), radial-gradient(at 50% 100%, rgba(56,189,248,0.18) 0px, transparent 50%)',
        'aurora': 'linear-gradient(135deg, #1f5cf5 0%, #6b21a8 55%, #0ea5e9 100%)',
      },
      borderRadius: {
        '4xl': '2rem',
      },
    },
  },
  plugins: [],
}
