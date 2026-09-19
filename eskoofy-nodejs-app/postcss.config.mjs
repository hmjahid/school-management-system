/**
 * Tailwind CSS v4 is wired through the dedicated PostCSS plugin
 * (`@tailwindcss/postcss`); utility classes only, mirroring the Laravel
 * app's Tailwind v4 setup (`@tailwindcss/vite`).
 */
const config = {
  plugins: {
    "@tailwindcss/postcss": {},
  },
};

export default config;
