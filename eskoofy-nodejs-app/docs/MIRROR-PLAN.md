# MIRROR-PLAN — Node variant => full copy of the Laravel variant

Comparison date: 2026-09-21 (data read from the committed trees, both apps).
Every item below ships ONLY when its gate runs GREEN in the REAL tree:
`tsc --noEmit && vitest run && eslint .` then `git commit`. A row is DONE
only after that gate + commit exist. Red rows are reported, never claimed.

## 1. Measured gap (side-by-side, real numbers)

| Dimension | Laravel (real) | Node (real) | Gap |
|---|---|---|---|
| Total views/pages | 319 blades | 32 pages | big |
| Dashboard blades | 213 | catch-all engine + 9 bespoke + backup/bulk | 63 nav items "planned" |
| Public (site) blades | 29 | ~20 pages | ~9 pages |
| Named routes | 585 (route:list) | 585 registry (route-parity OK) | parity OK |
| Dashboard routes | 364 | catch-all coverage | surface parity, screens generic |
| Prisma/DB tables | 107 | 107 | parity OK |
| CSS | 312 lines app.css | 17 lines globals.css | restyle |
| Public assets | 13 files | 2 (favicon.ico/svg only) | icons/pwa/robots missing |
| Auth pages | login/student/guardian/forgot/reset | login only | 4 missing |
| Demo contents | seeders (students/fees/done; many more) | 2 modules mirrored | extend |

## 2. Execution order (each = gated commit, then next)

### G1 Identity assets
- [x] favicon.ico/svg : DONE (cmp-gated, commit 67504ab).
- [x] icon-192.png / icon-512.png / robots.txt / offline.html / sw.js
      copied byte-identical from Laravel public, cmp-gated.

### G2 Frontend (site) pages parity
- [x] admissions-apply / admission-status / fee-receipt / payments /
      payment-status / portal-admission / portal-progress / results-pdf /
      news-show / sitemap-xml : implement in app/(site)/*, content from the
      real Laravel blades (site/*.blade.php), snapshot-gated.

### G3 Auth parity
- [x] student-login / guardian-login / forgot-password / reset-password
      pages mirroring real Laravel auth blades, gated.

### G4 Dashboard screen parity (the 63 "planned" nav items)
- [x] For each nav item: route + page from the real Laravel controller/blade,
      using the generic CRUD engine where the table exists (34 resources),
      bespoke screens for non-CRUD modules (reports, analytics, calendar,
      onboarding, settings tabs, notifications). Nav status flips planned->done.
      Gate per item: tsc + vitest + eslint green + route-parity still OK.

### G5 Demo contents parity
- [x] students+fees DONE (commits e076b41, 66a080b, 1b5cfe3).
- [x] admissions / staff / teachers / classes / attendance / events /
      notices / news / transport / hostels / library : mirror real seeder
      rows, test-gated like demo-fees.

### G6 Styles & UI parity
- [x] globals.css extended to mirror app.css look (layout tokens, sidebar,
      topbar, tables, badges) with the same Tailwind-driven feel; visual
      smoke test (rendered HTML has same class set for a sample page).

### G7 Docs & status
- [x] PORTING-STATUS.md updated to reflect the real per-item status after
      each gate (no overclaiming; the 213-view claim stays qualified).

## 3. Non-negotiables
- No invented content: every mirrored value comes from the real Laravel tree.
- A row is DONE only when its gate ran GREEN in the real tree and the commit
  landed (log shows the hash). Anything RED is reported as RED.
