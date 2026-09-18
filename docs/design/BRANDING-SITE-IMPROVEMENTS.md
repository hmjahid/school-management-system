# Branding-Site Improvements — International SMS showcase patterns (2026)

> Research date: September 2026
> Target: `eskoofy-website/` (the Eskoofy marketing + license site)
> Companion doc: `docs/design/COMPETITIVE-ANALYSIS.md` (8-company profile inventory).
> This file focuses on **what to implement** and **where** in the existing site,
> based on how international school-management software vendors showcase
> product, pricing, social proof and conversion in 2026.

---

## 1. Fresh 2026 benchmark (what the leaders are doing now)

| Company | Positioning | Pricing model | Product showcase | Conversion |
|---|---|---|---|---|
| **openSIS** | "Everything Your School Needs Built Into One Smarter SIS" + AI | **Public staff-based** ($4–14/staff/mo; annual/monthly toggle w/ savings) | Modular product cards (SIS, Scheduling, LMS, AI, Grades); "Built for every learning environment" K-12/Higher-Ed/Trade/Virtual | **Free Trial** + Schedule Demo; ISO 27001/FERPA/GDPR badges; WhatsApp button; "Switch from another SIS" |
| **Fedena** | "All-In-One School Management Software" | **Public flat-yearly** ($999–1,699/yr, unlimited users; Most-Popular badge) | Module grid (100+ modules), testimonial carousel, board support (CBSE/ICSE/IB) | **14-day free trial** + Calendly live demo; "101 reasons" page |
| **Veracross** | International/private-school SIS | Quote-based | "Veracross 360" unified-ecosystem narrative | Demo-led |
| **PowerSchool** | Platform/OS vision | Enterprise/RFP quote | Product configurator ("Build your K-12 OS") | Tour + expert call |
| **Alma** | Built by educators | Quote | **Role-based** feature presentation (Leadership/Teachers/Admins) + explicit **school-size segments** + distinctive illustration style | Schedule demo |
| **Gradelink** | "Simplify and Succeed" | Custom **quote generator** | Benefit-first minimal pages | Watch demo + brochure; **review badge wall** (Capterra/G2/GetApp) |
| **KaroManage** | Modular ERP (South-Asia/global) | Freemium + plans | Per-module landing cards (attendance, communication, timetables, RBAC) | Sign up + demo; WhatsApp |
| **OpenEduCat** | Open-source Odoo-based ERP | Free + $69–1,119/yr; **per-student cost comparisons** | Feature/competitor comparison pages ("OpenEduCat vs Fedena") | Start Free + Talk to Advisor |

**Patterns worth stealing (ranked):**
1. **Public, transparent pricing with a billing-period toggle** (openSIS, Fedena) — biggest trust lever for a low-cost product like Eskoofy.
2. **Dual CTA on every page: self-serve (free trial/signup) + human (demo/contact)** (Fedena Calendly, openSIS free trial).
3. **Module/role-organized feature showcase** — cards per module + "who is it for" role tabs (Alma, KaroManage).
4. **Third-party trust badges + compliance badges** (ISO/FERPA/GDPR — openSIS; review badges — Gradelink).
5. **Competitor comparison / "switch from X" pages** (OpenEduCat, openSIS migration messaging).
6. **Named testimonials with school + role + stats counters** (Fedena, openSIS).
7. **Most-Popular highlight on the mid tier** + guaranteed inclusions ("unlimited users, updates, onboarding") (Fedena).

---

## 2. Gap analysis — current eskoofy-website vs these patterns

Current pages: `/` (home), `/features`, `/pricing`, `/products/{app|php|theme}`, `/about`,
`/contact`, `/blog`, `/checkout`, auth + account/license admin.

| # | Current state | Gap vs 2026 benchmark |
|---|---|---|
| H1 | Home has hero + 3 product cards + 6 feature cards + pricing preview + 3 testimonials + blog + CTA | No billing-period toggle; pricing preview shows only top tier per product; no role-based feature tabs; no stats counter bar; no trust/compliance badge strip |
| H2 | No product demo/tour | No interactive tour, screenshot gallery, or "watch demo" (all majors now offer one of these) |
| F1 | `/features` = flat 6-card grid | Not organized by module or role; no screenshots; no "see all modules" expander |
| P1 | `/pricing` + `/checkout` show DB-backed plans | No Most-Popular badge logic, no annual/monthly toggle, no "what's included" checklist parity, no per-student/staff comparison, no FAQ |
| PR1 | Product pages (`/products/app|php|theme`) show 4 bullets each | No screenshot, no full module list, no "who it's for" role tabs, no demo CTA per product |
| S1 | Home testimonials (3) with names only | No school/role/title, no stats counters (50+ modules / 3-in-1 / 24/7 are hardcoded strings — could be counters), no client-style logo strip |
| C1 | Conversion CTAs exist (register/contact/pricing) | No persistent header CTA, no demo option, no FAQ block, no WhatsApp/chat, no "switch from X" |

---

## 3. Recommended implementation (by page, in priority order)

### 3.1 Global (highest ROI, lowest effort)
- **G1 — Persistent header CTA.** Add a `Sign up free` / `Get a demo` button in the header nav (today header links to pricing/register only). Mirror openSIS/Fedena dual-CTA habit.
- **G2 — Trust strip.** A reusable partial under the hero on home + product pages: "ISO-aligned security · FERPA/GDPR-ready · 3 deployment models · 1 license, 3 products". Reference the real compliance posture from `docs/operations/PAYMENT-DEPLOYMENT.md`/app docs so copy is accurate.
- **G3 — Review/comparison badges.** A "compare" nav or footer block linking to a new `/compare` page (see 3.5) and any review-listing presence.

### 3.2 Home (`views/site/home.php`)
- **H1 — Billing toggle on pricing preview.** For each product card add monthly/annual toggle (annual = ~2 months free) computed from the existing `$appPlans/$phpPlans/$themePlans` data; show the **Most-Popular** tier (not just the most expensive) with a "Most Popular" pill; add a "what's included" mini-checklist (unlimited users, updates, onboarding, training).
- **H2 — Stats counter bar.** Replace the hardcoded `50+ / 3-in-1 / 24/7` block with an animated counter strip: "50+ modules · 3 deployment models · 24/7 support · 1-time license" (JS count-up, matching app `assets/js/app.js` counter conventions).
- **H3 — Role-based "who it's for" tabs** on the features section (Admin · Teacher · Parent/Student), reusing the same content with role-flavored copy — Alma/KaroManage pattern.
- **H4 — Screenshot / product mockup** in the hero right column (today it's a feature list card) — a stylized dashboard mockup (SVG) raises perceived product maturity.
- **H5 — Trust badge strip** between hero and products (G2).

### 3.3 Features (`views/site/features.php`)
- **F1 — Reorganize into module groups** matching the app's actual sidebar: Main/Academic (People, Academics), Daily, Finance, HR, Documents, Library, System, Website CMS. Add a **group filter** (pills) or **expandable module list** ("Show all 50+ modules").
- **F2 — Add per-module screenshots/mockups** (static SVG placeholders first, real screenshots later).
- **F3 — Role tabs** (Admin/Teacher/Parent/Student) to satisfy both module- and role-based browsing (G3/pattern #3).

### 3.4 Pricing (`views/site/pricing.php`) + checkout (`views/site/checkout.php`)
- **P1 — Billing-period toggle** (monthly/annual) per plan row with "2 months free" badge on annual (mirrors openSIS `$4→$5` / Fedena yearly-only patterns).
- **P2 — Most-Popular highlight** on the recommended tier (border + badge + default-select in checkout), matching Fedena's "Most Popular" and home's existing recommended card.
- **P3 — "What's included" checklist parity** per tier: unlimited users, free cloud hosting, automatic updates, onboarding & data configuration, training, email+phone support (Fedena guarantees). Map each to the real plan rows in the DB (`plans` table) so checkout and pricing never drift.
- **P4 — Pricing FAQ** accordion (what's included, migration/switch, multi-product license, payment methods) — reduces pre-sales questions.
- **P5 — Dual CTA**: "Buy now" + "Book a demo / Contact sales" side-by-side.

### 3.5 New page: `/compare`
- **C1 — "Eskoofy vs [openSIS / Fedena / PowerSchool / custom SIS]"** comparison table (modules, deployment, pricing model, data ownership, license). Modeled on OpenEduCat's comparison pages. Uses **real** Eskoofy capability data (all products now carry: online payments, RBAC, multistep admissions, portal, ledger/budgets, transport/hostels, library fines, API, scheduler/push).
- **C2 — "Switch from another SIS"** section with migration/messaging (openSIS "Switchover assistance").
- Reuse the `comparison` layout in `views/site/products/*` and register the route in `routes/web.php`.

### 3.6 Product pages (`views/site/products/app.php|php.php|theme.php`)
- **PR1 — Replace 4-bullet list with the full module set** (grouped), pulling from the same keys used by `/features`.
- **PR2 — Add role tabs** (Admin/Teacher/Parent/Student) + a **demo/CTA** strip ("Book a demo · Start free").
- **PR3 — Screenshot/mockup hero** per product (parity with H4).
- **PR4 — "Who is it for" segments**: small school, mid-size, international, district/multi-campus (Alma segments) — keeps the three-product framing (Laravel / raw-PHP / WP theme).

### 3.7 Social proof
- **S1 — Enrich testimonials** with school name + role/title (+ optional logo placeholder) on home and add a testimonials strip to product pages.
- **S2 — Stats counters** across home/product pages (50+ modules, 3-in-1, 24/7; or real numbers once available).
- **S3 — Case-study / "how a school runs on Eskoofy"** blog posts (map to the existing `/blog` + `SitePostController`).

### 3.8 Conversion & support
- **C1 — Persistent demo CTA + FAQ** (P4) on every product + pricing page.
- **C2 — Contact/WhatsApp affordance** — at minimum a prominent contact block and a support email link in the footer (feasible without adding chat infra).
- **C3 — Migration messaging** ("Run it in a day; move your data from your current system") on `/compare` and product pages.

---

## 4. Implementation checklist (ordered for a single build pass)

1. **Global**: header CTA (G1) + trust strip partial (G2) — small, touches `partials/`.
2. **Pricing**: billing toggle + Most-Popular + included-checklist + FAQ (P1–P4) + checkout sync (P2).
3. **Home**: role tabs on features (H3) + stats counters (H2) + screenshot mockup (H4).
4. **Features**: module-grouped + role tabs + "show all modules" (F1–F3).
5. **Product pages**: full module list + role tabs + demo CTA + mockups (PR1–PR4).
6. **New `/compare`** page + route + nav/footer link (C1–C2).
7. **Testimonials/stats enrichment** (S1–S2).
8. **Copy/labels**: all new strings into `eskoofy-website/lang/en.php` + `bn.php`; keep `int` single-variant rule.

## 5. Where the research lives

- This file: `docs/design/BRANDING-SITE-IMPROVEMENTS.md`
- Competitor inventory (profiles + cross-analysis): `docs/design/COMPETITIVE-ANALYSIS.md`
- Re-audit/re-verify commands: `cd eskoofy-website && composer test` (79 tests) after any
  change; `php -l` each touched view.