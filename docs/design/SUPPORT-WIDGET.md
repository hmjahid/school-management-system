# Customer Support Widget — approach & implementation

> Target: `eskoofy-branding-website/` (the marketing + license site) · Status: implemented
> Companion: `docs/design/BRANDING-SITE-IMPROVEMENTS.md` §3.8 (conversion & support).

## 1. Recommendation — what "professional" looks like

A support affordance on a B2B/SaaS marketing site should do three jobs and no more:
**answer fast**, **route to a human**, and **get out of the way**. The professional pattern
(the one openSIS, Fedena and most 2026-era SaaS sites use) is:

1. **One persistent launcher** (bottom-right) that opens an in-page panel — not an
   auto-opening modal, never a full-screen takeover.
2. **Self-serve first**: quick links to the answers that resolve most pre-sales questions
   (help centre / pricing / compare / book a demo).
3. **Human second**: real channels — support email, sales email, phone, WhatsApp — pulled
   from site settings so the owner never edits code to change a number.
4. **A single conversion CTA** ("Send us a message") deep-linking to `/contact`.
5. **Accessible + privacy-respecting**: keyboard operable, Esc to close, `aria-*` wired,
   no third-party tracker or chat vendor by default.

### Why not an embedded third-party chat (Intercom/Crisp/Tawk)?

- **Zero new runtime dependencies** is a hard rule for this site (`AGENTS.md`) — it must keep
  running on plain shared hosting.
- A third-party widget adds a script tag, a cookie and a **CSP** exception, and typically
  costs money before there is any traffic to justify it.
- The panel is the *front end*; when the site later adopts a live-chat vendor, only the
  "Send us a message" handler needs to change — nothing else moves.

### Escalation path (when chat is later wanted)

Keep this widget as the container and swap the CTA target: point it at a Crisp/Tawk/Intercom
snippet, or embed a chat iframe. That requires adding the vendor origin to the CSP
`script-src`/`frame-src` in `app/Core/Middleware/SecurityHeadersMiddleware.php` — the only
file that should ever change for that.

## 2. What was implemented

| Piece | File |
|---|---|
| Widget markup + inline JS | `eskoofy-branding-website/views/site/partials/support_widget.php` |
| Included on every public page | `eskoofy-branding-website/views/layouts/main.php` |
| Config (emails/phone/WhatsApp/hours/enable) | `App\Models\Settings` defaults + `views/admin/settings.php` |
| Copy (en + bn) | `lang/en.php` → `support.*`, `lang/bn.php` → `support.*` |

Behaviour:
- Floating launcher toggles a `role="dialog"` panel; `aria-expanded`/`aria-controls` wired,
  Esc closes, focus moves into the panel on open.
- A one-time greeting bubble appears after ~2.5s and remembers dismissal in
  `localStorage` (`esk-support-bubble-dismissed`).
- Channels render **only when configured**; blank settings simply hide that row.
- Hidden entirely when `support.widget_enabled` is off (Admin → Settings → Support).
- No new dependencies; styling uses the existing Tailwind CDN setup and brand tokens.

## 3. Configuration

Admin → Settings → **Support & contact**: support email, sales email, phone, WhatsApp,
office address, support hours, social profiles, and the widget on/off toggle. Everything is
stored in the `settings` key/value table — no code edits, no redeploy.

## 4. Deliberate non-goals

- **No live-chat agent console** in the admin — the panel routes to email/WhatsApp/contact
  rather than pretending to be an agent desk.
- **No third-party chatbot** — see above.
- **No auto-open modal** — hostile UX and a Core Web Vitals/accessibility risk.
