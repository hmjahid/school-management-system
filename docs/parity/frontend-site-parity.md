# Frontend Site Parity — app vs php vs theme (read-only audit)

Status: **AUDIT ONLY — no modifications made.** Recorded 2026-09-19.

Scope: the **public (marketing/school) frontend** of all three products — pages,
header/footer, styles, layouts, page contents/sections, interactive functionality,
responsive behavior. The `/dashboard/` admin surface is **out of scope** (see
`product-parity.md`).

Legend for the matrices: **✔** present/parity equivalent · **✘** absent · **≈** present
but functionally/structurally different · **?** not verified.

---

## 1. Architecture & runtime

| Aspect | eskoofy-app (Laravel 12) | eskoofy-php (raw PHP) | eskoofy-theme (WordPress) |
|---|---|---|---|
| Runtime | Laravel (Blade native) | in-house `App\Core\Blade.php` (compiler+runtime) + `Router` | WP template hierarchy + plugin layer (`inc/`) |
| Public layout | single `layouts/app.blade.php` (all pages + auth views extend it) | **byte-identical copy** | per-template hierarchy (header.php / footer.php / template-*.php) |
| Public views | `resources/views/{home,site/**}` | **byte-identical copy** (46 files verified) | root templates + `template-*.php` (20) + CPT templates |
| Content model | CMS/DB: `WebsiteContent::getContent('<page>')` + `site_ui()` fallbacks; every section gated by `$siteSettings->section_visibility` | same (same views) | `esk_website_contents`/`esk_page_rows()` + options (`esk_{page}_page_content`) + `esk_section_visibility` theme-mod |
| Theming | runtime CSS-variable overrides (`--theme-*`) from `WebsiteSetting` + 4 theme presets + header/button/spacing presets + template hero designs 1–6 | same (same views/JS) | `esk_theme_primary()/secondary()` + customizer CSS vars (`:root`); hero designs 1–6 |
| i18n | `lang/{en,bn}/site_frontend.php` (+ bytes) + `site_ui()` helper | **byte-identical lang** | `esk_site_ui()` dot-notation; `languages/{bn_BD,en_GB,en_US}/site_ui.php` + per-key override options |
| Routes | dedicated web controllers (`HomeController`, `SitePageController`, `SiteSearchController`, `AdmissionWebController`, `PaymentsWebController`, `PortalController`, `SiteResultController`, …) | single `SiteController` (50 public methods) + `HomeController`; mirror of app routes via `config/routes.php` | front-dashboard routes + frontend `home_url()` links + CPT archives; public REST `esk/v1` |
| CSS | Tailwind v4 (`resources/css/app.css` `@theme`; Tokens: Inter + Noto Sans Bengali, brand blue 250 / accent orange 55) | **source-identical**; built bundle **stale** (older toolchain) | hand-written `style.css` (2,686 L) + compiled Tailwind utilities `assets/app-public.css` + `esk_customizer_css()` |
| JS | `resources/js/app.js` (871 L) + inline head/nav scripts | **source-identical**; built bundle **stale** | `assets/js/main.js` (1,182 L, vanilla) + inline scripts |
| PWA | `public/{sw.js,offline.html}` (cache v1, network-first for /api/ + /dashboard/) | same | `pwa/{sw.js,offline.html}` (v2), served via `template_redirect` with explicit 200 |

---

## 2. Page chrome — header / topbar / footer

### Header (app `partials/site/nav.blade.php` vs theme `header.php`)

| Element | app | php | theme |
|---|---|---|---|
| Top info bar (phone / email / address w/ tooltips) | ✔ | ✔ (identical view) | ✔ utility bar (phone/email svg) |
| Language toggle EN/Bn | ✔ segmented (or select if >2 locales) | ✔ | ✔ switch links preserving query args |
| Social links row in topbar | ✔ (`partials/site/social-links`) | ✔ | ✔ (via `esk_social_profiles()` in footer only — topbar shows divider) |
| Admissions-open CTA bar (`:year`) | ✔ | ✔ | ✔ (`admissions_bar.title`) |
| Announcement ticker (marquee, animate-marquee) | ✔ (5 latest header announcements) | ✔ | ✔ (notice ticker) |
| Sticky blurred header w/ search overlay | ✔ | ✔ | ✔ sticky nav + search toggle + shadow state |
| Desktop dropdown nav @`min-[1367px]` | ✔ | ✔ | ✔ (`min-[1367px]:flex`) |
| Mobile full-screen drawer + accordions | ✔ | ✔ | ✔ slide-in panel + hamburger |
| Mobile bottom action bar (login/portal/logout) | ✔ | ✔ | ≈ (dashboard shell uses it; public mobile menu focuses nav links) |
| Login/Portal buttons in nav (guests vs logged-in) | ✔ | ✔ | ✔ (Dashboard/Portal preceding Logout) |
| Skip link | ✔ (to `#esk-main-content`) | ✔ | ⚠️ target `<main id="esk-main-content">` **never rendered** on public pages (dead link); plus stray `</main>` in footer.php:45 + missing `<main>` opener (front-page.php:837 emits lone `</main>`) |

### Footer (app `partials/site/footer.blade.php` vs theme `footer.php`)

| Element | app | php | theme |
|---|---|---|---|
| Dark 4-column grid (slate-900) | ✔ | ✔ | ✔ (`bg-slate-900`, grid-cols-1 sm:2 lg:4) |
| Col 1 — brand + tagline + social icons (`esk_social_profiles()`) | ✔ | ✔ | ✔ (ring hover `bg-brand-600`) |
| Col 2 — Quick Links | ✔ | ✔ | ✔ (hardcoded `$links` map, `footer.link_*` labels) |
| Col 3 — Important/ministry links (incl. transport link) | ✔ (BD fallbacks) | ✔ | ✔ (`footer.ministry_links` parsed `Title|URL`, moedu.gov.bd fallback) |
| Col 4 — contact rows + newsletter form | ✔ (throttled POST) | ≈ (view identical; **no rate limit**) | ✔ (`esk_newsletter` POST) |
| Bottom bar — copyright + privacy/terms/sitemap | ✔ | ✔ | ✔ (`© YYYY`, privacy/terms/sitemap links) |

---

## 3. Styles & theming comparison

| Topic | app | php | theme |
|---|---|---|---|
| Framework | Tailwind v4 (Vite build, `@theme` tokens) | **source-identical** `resources/css/app.css`; built css/js **divergent hashes** (stale php bundle from vite 7.1.9/tailwind 4.1.14 vs app 7.3.6/4.3.3) | vanilla `style.css` (2,686 L, scoped reset via `:where(body:not(.esk-admin-shell)) *`) + compiled Tailwind `assets/app-public.css` (1,459 L) |
| Brand palette | oklch tokens in `app.css`; runtime `--theme-primary/accent` overrides (color-mix) | same | `esk_theme_primary()` default `#2563eb`; `esk_theme_secondary()` default `#f97316`; customizer defaults |
| Dark mode | ✔ class-based, `localStorage 'school-dark-mode'`, pre-flash script + `[data-dark-toggle]` | ✔ | ✘ no public toggle (dark mode only in admin shell) |
| Hero designs | 6 partials `hero/design-{1..6}.blade.php` (design 6 crossfade) | ✔ identical | ✔ designs 1–6 incl. crossfade (`hero6-fade` keyframes) |
| Section styling | Tailwind utilities + theme presets (compact/spacious, button presets) | same | `esk-section-*`/`esk-btn-*` hand-written classes; mixed `eskoofy-*` legacy classes remain |
| Fonts | Inter + Noto Sans Bengali | same | Google Fonts (functions.php) + `font-bengali` body class when bn |
| Customizer/theme-settings | CMS `WebsiteSetting` fields (theme, header, footer, buttons, spacing) | same | `inc/customizer.php` (school info, colors, footer socials, homepage hero, section-visibility textarea) |

---

## 4. Per-page / per-section matrix

Columns: **app** route · **php** parity · **theme** template + coverage notes.

| Route (name) | app view (sections) | php | theme equivalent |
|---|---|---|---|
| `/` (home) | `home.blade.php`: hero (design 1–6), features, stats (count-up: student/teacher count + established_year), principal msg, teachers slider (8), committee slider (20), testimonials, remarkable students (8, `is_notable`), photo slider, events (6), news (6), highlights, CTA banner, partners strip — each gated by `section_visibility` | ✔ identical | `front-page.php` (840 L): **same 15 sections in same order**, incl. hero designs 1–6, teachers/committee sliders, remarkable via `[eskoofy_bright_students count=8]` |
| `/about`, `/academics`, `/students`, `/privacy`, `/terms`, `/transport` | `site/page.blade.php`: inner-hero + CMS **sections** partial (`page_hero` flag; 404 if inactive) | ✔ | `template-{about,academics,students,privacy,terms,transport}.php`: inner-hero + `page-sections` ($page content/option) |
| `/admissions` | `site/admissions.blade.php`: adm_hero (closed→slate variant / open→orange gradient+badge), sections, admission process 4 steps, **fee table (hardcoded ৳ BD)** | ✔ | `template-admission.php`: open/closed banner, fee table (from `esk_fees`⋈`esk_classes`), apply CTA, process steps |
| `/admissions/apply` | `site/admissions-apply.blade.php`: 6-step multistep (Personal/Contact/Academic/Guardian/Documents/Review) + honeypot; `not_configured` guard | ✔ | `template-admissions.php`: `[eskoofy_admission_form]` (closed → `admissions_closed_*` box) |
| `/admissions/status` | `site/admission-status.blade.php`: app# lookup, **4-step progress stepper**, payment submission states (unpaid/submitted/rejected), receipt + approval-letter links | ✔ | ✘ no standalone status page (only status text on `template-admission.php`; adjacency: REST `admissions` endpoints) |
| `/faculty` | `site/faculty.blade.php`: 80 active teachers, live search (`[data-faculty-search]`), initials avatars; 404 if CMS inactive | ✔ | `template-faculty.php`: `$wpdb` teachers query, `esk_initials()` avatars, empty state |
| `/news` + `/news/{slug}` | `site/news.blade.php` (magazine grid: featured + events column) + `news-show.blade.php` (JSON-LD Article, OG/meta, reading-minutes) | ✔ | `template-news.php` **and** `archive-esk_news.php` (featured card + grid + pagination; note CPTs are non-public but archive/single templates exist) + `single-esk_news.php` (hero overlay, share, reading-time, related, JSON-LD) |
| `/notices` | `site/notices.blade.php`: 15/page, pinned-first | ✔ | `template-notices.php` **and** `archive-esk_notices.php` (pinned-first) |
| `/events` | `site/events.blade.php`: upcoming50/past20, all/upcoming/past filter + view toggle | ✔ | `template-events.php` **and** `archive-esk_events.php` (upcoming/past, countdown badge, 12/10 per page) |
| `/gallery` | `site/gallery.blade.php`: category tabs, filter + lightbox | ✔ | `template-gallery.php` **and** `archive-esk_galleries.php` (`[data-filter-tabs]`) |
| `/committee` | `site/committee.blade.php`: `localizedPayload` intro/sections + members grid `sm:2 lg:3 xl:4` | ✔ | `template-committee.php` (bn name/designation fallback via `$t_loc()`) |
| `/contact` | `site/contact.blade.php`: info cards, emergency contacts, **form/feedback/complaint** (honeypot), flash toast, map | ✔ | `template-contact.php` (201 L): 4 info cards, **single contact form** (honeypot + `esk_csrf_field`), opening-hours table, Google Maps iframe, emergency contacts, FAQ `<details>` — feedback/complaint forms **?** |
| `/results` + `/results/download` | `site/results.blade.php`: class/session/**roll lookup (roll_no OR roll_number)**, published-to-public exams, donut % + grade, Print + DomPDF | ✔ | `template-results.php` + `[eskoofy_results_lookup]` — **lookup by `admission_number` + `exam_id`** (≈ different lookup fields) |
| `/routine` | `site/routines.blade.php`: class/section filter, day cards 7-col | ✔ | ✘ **no routine template** — nav links `home_url('/routine/')` (header.php:87) but no page exists (dead link); only `[eskoofy_class_schedule]` shortcode hook `?` |
| `/payments` | `site/payments.blade.php`: CMS sections + **auth** pay form, fee table, gateway list, history + receipt links | ≈ (view identical; **route not auth-gated**) | `template-fees.php` + `[eskoofy_fees_payment]` + page-sections 'fees' |
| `/payments/status/{payment}` , `/payments/receipts/{feePayment}` | `site/payment-status.blade.php` + `FeePaymentReceiptController@show` (auth) | ≈ (not auth-gated) | ≈ result of REST payment callback → redirect `/fees/?payment=success|failed` (no standalone status page) |
| `/portal` | `site/portal.blade.php` (auth): 4 quick-stats + **tabs: profile/attendance/exams/fees/routine/dues/calendar/message** + assignments + announcements | ≈ (`/portal/register` behaves differently) | `template-portal.php` (300 L): guardian-linked students, multi-student switcher, **tabs: profile/attendance/exams/fees/routine/announcements/events** (no dues/calendar/message tabs, no assignments) |
| `/portal/progress` | `site/portal-progress.blade.php` (exam progress) | ✔ | ✘ |
| `/search` | `site/search.blade.php`: filter pills by type, grouped results | ✔ | `template-search.php`: min-2-chars form + result list (no type filter pills) |
| `/sitemap.xml` | SiteMap controller (`sitemap-xml`) | ✔ | ✘ (footer links sitemap but no endpoint) |
| `/offline` | `public/offline.html` | ✔ | `pwa/offline.html` |
| `/login`, `/student/login`, `/guardian/login`, passwords | 5 auth views extending `layouts.app` | ✔ | `template-login.php` (system login → `/dashboard/`) only — **no separate student/guardian login** ✘ |
| Newsletter/contact/feedback/complaint/scholarship POSTs | ✔ all throttled `throttle:12,1`, honeypot | ≈ views identical but **throttle middleware never wired** | ✔ contact + newsletter (honeypot); scholarship/complaint/feedback **?** |

Additional pages found **only in php** (deliberate BD legacy kept alive, absent from app
`routes/web.php`): GET/POST `/admission` (legacy), `/students-life`, `/register`,
`/admission/pay`, `/admission/callback/{gateway}`, `/admission/webhook/{gateway}`,
`/student/dashboard`, `/guardian/dashboard`, `/guardian/assignments/{submission}/notes`,
`/profile`, site-scoped `/messages`, `/sanctum/csrf-cookie`, `/storage/{path}` stubs.

---

## 5. Frontend functionality (JS) matrix

| Feature | app (`resources/js/app.js` + inline) | php | theme (`assets/js/main.js`) |
|---|---|---|---|
| Toast + confirm modal (`showToast`, `confirmAction`/`data-confirm`) | ✔ | ✔ | ✔ (`window.eskToast`, `window.eskConfirm` + `data-esk-confirm-*` DOM) |
| Scroll-reveal / IntersectionObserver | ✔ | ✔ | ✔ |
| Stat count-up (`data-countup`) | ✔ | ✔ | ✔ |
| Scroll-to-top | ✔ (injected) | ✔ | ✔ |
| Photo slider / teachers / committee autoplay | ✔ | ✔ | ✔ (`data-*-track` arrows + swipe + pause hover) |
| Gallery filter tabs + full lightbox w/ focus trap | ✔ (`data-filter-tabs`) | ✔ | ✔ |
| Multistep form wizard (admission) | ✔ | ✔ | ✔ (admission shortcode) |
| Countdown | ✔ (events/admissions) | ✔ | ✔ |
| Live faculty search | ✔ (`[data-faculty-search]`) | ✔ | ✔ (faculty template implements own) ? |
| Command palette (Cmd/Ctrl+K) | ✔ | ✔ | ✔ (`.esk-command-*` UI) |
| Notifications dropdown + polling (30s/60s) | ✔ (portal) | ✔ | ✔ (dashboard; admin shell) |
| Favorites (dashboard) | ✔ | ✔ | ✔ (AJAX toggle) |
| Portal tabs + message/announcements | ✔ | ≈ | ✔ (`data-esk-portal-*`) |
| PWA install prompt | ✔ | ✔ | ✔ |
| Image previews (upload) | ✔ | ✔ | ✔ |
| Debounce / dirty-track / unsaved warning | ✔ | ✔ | ✔ |
| Dark-mode toggle (public) | ✔ | ✔ | ✘ (admin shell only) |
| AJAX shortcodes (results lookup, fee payment, contact, gallery, admission) | n/a (server routes) | n/a | 12 shortcodes (see §6) |

---

## 6. Theme-only surface (shortcodes / widgets / REST / ajax)

- **Shortcodes (12, `inc/shortcodes.php`)**: results_lookup, admission_form, fees_payment,
  student_profile, class_schedule, news_list, events_list, gallery, contact_form,
  payment_gateway, bright_students, login_form.
- **Widget areas (6, `inc/widgets.php`)**: `sidebar-1`, `footer-1/2/3`, `home-hero`,
  `home-features` (mostly unused by current templates).
- **REST `esk/v1` (14 routes, `inc/rest-api.php`)**: public GET classes/exams/results/
  notices/news/`results/lookup`; public POST admissions + payments/callback/:gateway;
  logged-in notifications (+ mark-all); everything else `manage_options`.
- **AJAX `wp_ajax_` (5, `inc/admin-ajax.php`)**: student search, mark attendance, save
  results, students-by-class, favorite toggle.

---

## 7. Responsive behavior

| Topic | app | php | theme |
|---|---|---|---|
| Breakpoints | Tailwind sm/md/lg/xl + `min-[1367px]` desktop nav, `max-w-7xl` container | same | `style.css` media queries: 40rem, 640px, 768px, 860px, 991px, 992px, 1280px + `esk-container` |
| Mobile nav | full-screen drawer + accordions + bottom action bar | same | slide-in panel (hamburger), sticky shadow state |
| Grids | e.g. committee `sm:2 lg:3 xl:4`; news/events 3-col collapse | same | identical visual patterns via hand-written grid rules |
| Reduced motion | `prefers-reduced-motion` | ✔ | ✔ (disables reveal/count-up/sliders + JS gating) |
| Sliders | autoplay-wraps + swipe hint | same | swipe hint (`home.swipe_hint`), pause on hover/focus |

---

## 8. app ↔ php byte-identical verification (this audit)

Confirmed with `diff -qr` + per-file md5:
- `resources/views/{layouts,partials,site}/` + root `home.blade.php`/`welcome.blade.php`: **46/46 identical** (only dashboard/ trees differ).
- `lang/{en,bn}/` (`dashboard.php`, `messages.php`, `site_frontend.php`) + `lang/bn.json`: **byte-identical**.
- `resources/css/app.css`, `resources/js/app.js`, `bootstrap.js`, `vite.config.js`: **byte-identical**.
- Built assets `public/build/assets/*`: **NOT identical** — php bundle is stale (toolchain drift: app vite 7.3.6 / tailwind 4.3.3 vs php 7.1.9 / 4.1.14). Rebuild php from identical sources to converge.
- `eskoofy-theme/assets/app-public.css` exists **only** in the theme (no app/php equivalent; theme's dashboard CSS derives from the app Tailwind build).

Functional deltas (despite identical views):
1. **Rate limiting**: app throttles all public form POSTs (`throttle:12,1`); php has `ThrottleMiddleware` but wires it nowhere.
2. **Auth on payments**: app auth-gates `/payments/initiate|status|receipts`; php registers them without auth.
3. **`/portal/register`**: app redirects to `admissions.apply`; php renders portal home with empty data.
4. **Search**: identical query/shape behavior (single `SiteController::search` vs app's `SiteSearchController`).

---

## 9. Known gaps / differences — theme vs app (recorded, **no action taken**)

**Missing theme pages**: `/routine` (nav link is dead), `/admissions/status` stepper page,
`/portal/progress`, `/sitemap.xml`, separate student/guardian login pages,
standalone `/payments/status` page.

**Behavioral differences**: results lookup key (`admission_number`+`exam_id` vs app
`roll_no`/`roll_number` w/ class+session); portal tabs (theme lacks dues/calendar/message/
assignments; app lacks theme's switcher granularity); contact page (single form vs
app's form+feedback+complaint); no public dark-mode toggle; single login type.

**Theme markup defects found**: stray `</main>` (footer.php:45), lone `</main>` close
without opener (front-page.php:837), dead skip-link target `#esk-main-content`,
customizer logo option `esk_school_logo` vs templates reading `esk_custom_logo`,
customizer accent default `#10b981` vs `esk_theme_secondary()` default `#f97316`,
mixed `eskoofy-*`/`esk-*` class systems, non-public CPTs with public archive/single
templates.

## 10. Suggested follow-ups (not executed)

1. Verify/drift-check: rebuild php assets to converge bundle hashes; wire throttle.
2. Theme: add `/routine`, admission-status stepper, `/portal/progress`, sitemap; fix
   `</main>` + skip-link; reconcile logo/accent defaults.
3. Theme: decide port of feedback/complaint/scholarship forms to match contact page.
4. Theme: results lookup field parity (roll number) alongside admission_number.
5. Keep this audit updated as merges land (mirror format of `product-parity.md`).