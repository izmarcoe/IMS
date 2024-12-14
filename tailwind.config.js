/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.{html,php,js}",
    "./dashboards/*.{html,php,js}", 
    "./features/*.{html,php,js}", 
    "./js/*.{html,php,js}",
    "./features/**/*.{php,js}",
    "./features-AI/**/*.{php,js}",
  ],
  theme: {
    extend: {
      colors: {
        'custom-green': '#0F7505',
      },
    },
  },
  plugins: [],
}
