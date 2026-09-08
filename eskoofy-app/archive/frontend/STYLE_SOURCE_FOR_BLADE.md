# Public UI style reference (archived React)

The **active** implementation of these styles is **Laravel Blade** under `backend/resources/views/` (see `layouts/app.blade.php`, `partials/site/nav.blade.php`, `partials/site/footer.blade.php`, `home.blade.php`).

Original React sources (this archive) for comparison:

| Area | File |
|------|------|
| Top bar + main nav | `src/components/website/Header.jsx` |
| Footer | `src/components/website/Footer.jsx` |
| Home sections | `src/pages/HomePage.jsx` |
| Inner page hero pattern | `src/pages/AboutPage.jsx` (dark hero + `max-w-7xl` content) |
| Dashboard shell | `src/components/layout/Layout.jsx`, `Sidebar.jsx`, `Header.jsx` |
| Tailwind entry | `src/index.css`, `tailwind.config.js` |

**Design tokens used in Blade:** `blue-600`–`blue-900`, `orange-400`–`orange-600` accents, `gray-50`–`gray-900` neutrals, white cards with `shadow-md`, orange underline rule on section titles (`h-2 w-20 bg-orange-500`).
