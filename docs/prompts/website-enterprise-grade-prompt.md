# Eskoofy Website — Enterprise-Grade Upgrade Prompt

Hello! Thank you for helping us make the Eskoofy marketing website and license
server feel truly enterprise-grade. Please work through the tasks below one
at a time, from top to bottom, and continue until every task is finished.

Some gentle notes before you start:

- **Be kind to non-technical readers.** Every public word on the site should
  be clear and welcoming for a school head or an office manager — not just for
  developers. Avoid jargon and always explain benefits, not just features.
- **Keep it consistent.** Match the existing code style, the current Tailwind
  design, and the golden rules in `eskoofy-website/AGENTS.md`. The site is the
  `int` variant, always in English, with a বাংলা (`bn`) language switcher.
- **Zero new runtime dependencies.** No Composer packages at runtime — the site
  must keep working on plain shared hosting. Everything we build must use the
  existing zero-dependency mini framework in `eskoofy-website/app/Core/`.
- **Keep tests green.** Run `composer test` from inside `eskoofy-website/`
  (`./vendor/bin/pint --test` too if you touch PHP style) and make sure nothing
  breaks before you finish.
- **Always test your work.** Start the dev server (`php -S 127.0.0.1:8011 -t public`
  inside `eskoofy-website/`) and open the pages you changed to confirm they look
  and work correctly.

Here is everything we would love you to build.

---

## 1. Our Own Unique Logo and Brand Marks

**Goal:** Give Eskoofy a distinctive, memorable brand mark so the site no longer
looks like a generic starter template.

- Design an original logo mark for Eskoofy (something simple, modern, and
  school-related — for example an open book, a graduation cap, or a shield with
  an "E"). One clean SVG is enough; derive everything else from it.
- Replace the current placeholder logo (the blue "E" box) in the public header,
  footer, and admin sidebar, and the FAQ/product pages that use it.
- Update `public/favicon.svg` with the new mark.
- Update the PWA icons. Use `public/icons/generate.sh` (ImageMagick) if it is
  available; if not, you may edit the two or three PNGs by hand.
- Update `manifest.json` colors (name, icons, theme color) to match the new
  brand colors, and the `theme-color` meta tags in both layouts.

## 2. Enterprise Admin Dashboard with Charts

**Goal:** The admin backend dashboard (`/admin`) should look like a serious
operations console.

- Add a 12-month revenue trend chart (amount paid per month, last 12 months).
- Add a licenses-per-status breakdown (active / expired / revoked / paused)
  and a customers-growth or sales-per-product summary.
- Use a lightweight approach with **no external libraries** if possible — a
  clean SVG bar/line chart drawn in a small reusable helper is perfect (split
  axes, gridlines, values). If you prefer a tiny inline chart, that is fine too.
- Keep the existing KPI stat cards, but make them richer: trend vs previous
  month, subtle icons, and a clear revenue total for the year.
- Add a small "quick actions" row (Create license, Add plan, View messages,
  Email settings) that links to existing admin pages.

## 3. Enterprise Customer (Account) Dashboard

**Goal:** Customers should be able to manage their school account professionally.

- Add a small chart of their monthly spend (or activations over time), reusing
  the same lightweight chart helper from task 2.
- Add an "API key" card that shows their current customer token with a
  **Regenerate** button (new random token, updated in the customers table, kept
  hidden after regeneration as today).
- Add a **Download payments CSV** button on the payments area that streams a
  simple CSV of their payments.
- Keep the existing license list and stats; make the cards feel more refined.

## 4. Enterprise Pricing Page

**Goal:** The `/pricing` page should feel like a polished SaaS pricing section.

- Reuse the existing per-product plans from the database with a monthly /
  yearly toggle. When yearly is shown, show the yearly price with the
  "2 months free" savings note.
- Three product sections (School App, WP Theme, Raw PHP) as three columns on
  large screens, stacked on small screens.
- Mark the Raw PHP "Monthly" plan (or whichever plan you think deserves it) as
  "Most Popular".
- Keep the FAQ and the compare CTA that already exist, styled to match.

## 5. Settings + SMTP in the Admin Dashboard

**Goal:** Let the site owner configure the site and email from the browser.

- Add a `settings` key/value table to `database/schema.sql` and apply the same
  change to the live database.
- Create a `Settings` model that reads/writes key/value pairs (with typed
  getters for strings, booleans, JSON).
- Add an admin page `/admin/settings` (in the sidebar) with sections:
  - **Site** — site name, tagline, contact email, currency label.
  - **Appearance** — brand color (hex) and a light/dark default, applied on
    the marketing site.
  - **Email / SMTP** — driver (sendmail or smtp), host, port, username,
    password, encryption, from-address, from-name.
- Persist everything to the `settings` table; never log or echo the SMTP
  password.

## 6. Transactional Emails

**Goal:** The site should send real, useful emails.

- Build a small self-contained `Mailer` service in `app/Services/` that:
  - reads the email settings from the Settings model,
  - sends via PHPMailer-style SMTP **using raw PHP `mail()`**, `sendmail`,
    or native `stream_socket_client` SMTP — no Composer package,
  - falls back gracefully to `mail()` when SMTP is not configured,
  - and uses a shared HTML email layout in `views/emails/layout.php`.
- Create friendly email templates in `views/emails/`:
  - `welcome` — sent after customer registration,
  - `payment-received` — sent after a payment is marked paid,
  - `license-issued` — sent when a license key is created for a customer,
  - `license-expiring` — sent when a license expires within 14 days,
  - `contact-message` — sent to the site owner when the contact form is used.
- Wire the emails in:
  - `RegisterController::store` (welcome),
  - wherever payments become paid (payment-received + license-issued),
  - the webhook/payment-status handlers where a license gets created,
  - `HomeController::storeMessage` (notify the owner),
  - and add an "expiring soon" check that runs from the admin dashboard page
    (best-effort, non-blocking).

## 7. Responsive Polish and Dark Mode

**Goal:** The public site should look great on every screen and feel modern.

- Rework the public navigation into a proper slide-in drawer on mobile (with a
  hamburger button) instead of the current `<details>` accordion.
- Add an optional dark-mode toggle (sun/moon) in the public header that flips a
  `dark` class and persists the choice in `localStorage`. Dark styles should be
  tasteful and consistent with the new brand colors.
- Make the footer responsive and add the logo to it.
- Add a subtle sticky "Get a Demo / Subscribe" call-to-action bar on the
  homepage for mobile users.

## 8. Non-Technical Copy Pass

**Goal:** Every visitor should instantly understand what Eskoofy is and what it
does for their school.

- Rewrite the homepage hero and section intros so a school principal instantly
  knows what problem Eskoofy solves ("run your whole school from one place").
- Make the three product cards explain outcomes ("Move your school online",
  "Use your WordPress site as your school portal", "Run on cheap hosting with
  no locked-in platform") instead of technical descriptions.
- Make the pricing, compare, features and about sections friendly and
  benefit-first.
- Add small reassuring touches: "no student or staff limits", "your data stays
  yours", "cancel anytime", "human support".
- Update both `lang/en.php` and `lang/bn.php` so the বাংলা side matches the new
  English copy where sensible.

---

When you reach the end, please:

1. Apply any schema changes to the live database, re-run the full test suite,
   and boot-check the touched pages.
2. Summarize, in plain words, exactly what you changed, file by file.

Thank you again — we really appreciate your care and attention. Happy building!