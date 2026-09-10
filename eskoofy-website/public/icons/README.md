# App/PWA icons

The PNG icons in this folder are committed so the PWA has real assets with no
build step at deploy time.

| File | Size | Purpose |
|------|------|---------|
| `icon-192.png` | 192×192 | Web app manifest `any` — used by Chrome/Android install |
| `icon-512.png` | 512×512 | Web app manifest `any` — install + high-DPI |
| `maskable-512.png` | 512×512 | Web app manifest `maskable` — safe-zone aware |
| `apple-touch-icon.png` | 180×180 | iOS home screen (`<link rel="apple-touch-icon">`) |

Design: slate-900 rounded square, blue-600 circle, white bold "E" (matches the
site header monogram). The maskable icon uses a full-bleed background and keeps
all content inside the inner 80% safe circle.

**Regenerate:** `./generate.sh [font.ttf]` — requires ImageMagick
(`magick` or `convert`) and a bold sans-serif TTF (defaults to DejaVu Sans
Condensed Bold on Fedora). Any changes must be committed as PNGs.

`favicon.svg` lives at `public/favicon.svg` (used as `<link rel="icon">`).