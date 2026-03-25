# Implementation Plan — School Website (Full)

## Implementation status legend
- **✅ Implemented**: exists in code + reachable + works end-to-end for the described capability
- **🟡 Partial**: exists but missing key parts, admin tooling, or full workflow
- **❌ Not implemented**: planned but no real implementation found yet

This plan turns `website-plan.md` into a fully implemented, production-ready website inside this repo.

## Scope checklist (from `website-plan.md`)

### Public pages
- **Home** ✅: hero/CMS blocks, highlights stats, upcoming events, latest news, testimonials, CTAs
- **About** ✅: served via CMS (`site.page`) using `WebsiteContent`
- **Academics** ✅: served via CMS (`site.page`) using `WebsiteContent`
- **Admissions** ✅: admissions landing via CMS + apply/status routes exist
- **Students** ✅: served via CMS (`site.page`) using `WebsiteContent`
- **Faculty** 🟡: page works + staff directory exists; advanced sections depend on CMS content completeness
- **News & Events** 🟡: news + events listing exist; admin News CRUD implemented; magazine/newsletter archive/press releases not fully modeled
- **Gallery** 🟡: gallery listing exists; admin Gallery CRUD implemented; video gallery/virtual tour content depends on CMS/data conventions
- **Contact** ✅: page renders + contact/feedback/complaint/newsletter submissions persist
- **Portal (login)** ✅: `/portal` exists (auth protected), role-based redirect to dashboard for staff

### Special features
- **Online admissions system** 🟡: apply + document upload + status check implemented; test scheduling implemented; payment integration not confirmed
- **Fee payment portal** 🟡: `/payments` page exists + gateways listed + payment history shown; receipt print/download implemented; full pay flow + payment plans not confirmed
- **Student/Parent portal** 🟡: portal dashboard exists (attendance/results/fees/assignments); progress summary + announcements implemented; full term-wise progress reports + messaging still incomplete

### Cross-cutting requirements
- **Frontend** 🟡: responsive + Blade/Tailwind in place; full WCAG + perf budgets not verified; multilingual exists but coverage incomplete
- **Backend** 🟡: auth + RBAC + CMS pages editor exist; file management for admissions docs exists; email notifications not confirmed end-to-end
- **Integrations** 🟡: Google Maps embed setting + GA ID hook exist; payments backend has callback/webhook controller but public UX is partial; SMS/email/calendar not confirmed
- **Maintenance** ❌: backup/monitoring/perf monitoring/feedback loops not implemented as a system feature set
- **Analytics** 🟡: GA hook exists; conversion tracking/reporting dashboards not implemented

---

## Current repo assumptions
- Laravel backend exists under `backend/`.
- Public site routes live in `backend/routes/web.php`.
- Pages are rendered with Blade under `backend/resources/views/site/`.
- Dashboard and portal exist and will be extended to match the plan.

If any of the above differs, adjust the file paths accordingly.

---

## Milestone 0 — Baseline & conventions (1–2 days)

### Deliverables
- **Design system** 🟡: Tailwind-based UI exists; formalized tokens/components not fully standardized
- **Layout** ✅: `layouts.app` and `layouts.dashboard` exist and are used
- **Global view data** ✅: `siteSettings` is shared to views
- **Localization** 🟡: locale switch exists + many strings use `__()`; full key coverage not verified

### Implementation tasks
- **Theme tokens** 🟡: Tailwind is used; consistent token layer not fully defined
- **SEO defaults** 🟡: `<title>` + meta description exist; canonical/OG defaults implemented; schema.org not implemented
- **Accessibility** 🟡: forms have labels; full WCAG checklist not completed

### Test checklist
- All public pages render with no PHP warnings.
- Lighthouse (or basic manual) checks: mobile layout, focus visible, color contrast.

---

## Phased roadmap to finish 🟡/❌ items

This section groups the remaining **🟡 Partial** and **❌ Not implemented** work into practical phases. Each phase can be delivered independently and moves the project toward the acceptance criteria at the bottom.

### Phase A — SEO + trust basics (2–4 days)
- **Goals**
  - Ship the missing SEO primitives required by the plan.
- **Deliverables**
  - **✅ `sitemap.xml`**: generate and serve a sitemap containing public routes + dynamic news slugs.
  - **✅ `robots.txt`**: serve a robots file pointing to the sitemap.
  - **❌ schema.org**: add Organization + Article structured data (home + news detail).
  - **✅ OG/canonical defaults**: add canonical + OpenGraph fallbacks site-wide.
- **Done when**
  - `GET /sitemap.xml` and `GET /robots.txt` return 200 and validate.
  - News detail pages include OG tags + JSON-LD.

### Phase B — CMS “completion” (admin CRUD) (1–2 weeks)
- **Goals**
  - Move beyond “CMS page JSON” to full content operations per the plan.
- **Deliverables**
  - **✅ News CRUD**: create/edit/publish/unpublish, slug validation, image upload.
  - **✅ Gallery CRUD**: categories, media upload, publish controls.
  - **✅ Newsletter system (export)**: export subscribers from stored submissions (CSV via dashboard).
  - **✅ Form submissions viewer**: keep and enhance (filters, statuses, export).
  - **✅ Document management (basic)**: dashboard CRUD + upload + listing.
- **Done when**
  - Admin can manage news/gallery entirely from dashboard without DB edits.
  - Public pages reflect published content immediately.

### Phase C — Admissions “full plan” (5–8 days)
- **Goals**
  - Finish the admission workflow items not confirmed yet.
- **Deliverables**
  - **✅ Test scheduling**: an `admission_tests` model/table + assign schedule to an application.
  - **✅ Notifications (email)**: on submission + dashboard status changes + test scheduling.
  - **✅ Logged-in applicant view (basic)**: `/portal/admission` shows application + docs + latest test for logged-in user (email match).
  - **🟡 Payment hook**: optional application fee invoice/payment link (if required).
- **Done when**
  - Admin can schedule tests and applicants can see schedule/status.
  - Submission/status updates trigger notifications.

### Phase D — Payments portal end-to-end + receipts (1–2 weeks)
- **Goals**
  - Make `/payments` a complete user flow, not just a listing page.
- **Deliverables**
  - **✅ Selection UX (basic)**: choose student + fee item + gateway + amount from `/payments`.
  - **🟡 Gateway flow**: initiate payment → callback/webhook updates → final status page (status page implemented).
  - **✅ Receipts**: generate and download/print receipts (HTML print view).
  - **❌ Payment plans**: basic installment schedule model + UI (optional if required).
  - **🟡 Webhook hardening**: idempotency + audit trail + optional HMAC signature check (when configured).
- **Done when**
  - A student/parent can complete a payment and download a receipt.
  - Webhooks are safe to retry and don’t double-apply payments.

### Phase E — Portal depth (progress + communication) (1 week)
- **Goals**
  - Complete the portal features listed in the plan.
- **Deliverables**
  - **✅ Progress reports (basic)**: `/portal/progress` with academic session + exam type filters and per-exam summaries.
  - **✅ Communication/announcements**: announcements feed implemented (messaging still optional/missing).
  - **🟡 Portal UX**: filters, pagination, better empty states.
- **Done when**
  - Portal includes progress reports and a real announcements feed.

### Phase F — Quality, maintenance, and operations (1–2 weeks)
- **Goals**
  - Meet the plan’s “launch-ready” requirements.
- **Deliverables**
  - **❌ Automated tests**: feature + policy coverage for admissions/payments/portal.
  - **🟡 Performance**: caching strategy + image optimization + lazy-loading gallery.
  - **❌ Observability**: error monitoring (Sentry), webhook logs, audit trail review.
  - **❌ Backups**: scheduled DB backup + upload storage backup approach documented and runnable.
  - **🟡 Accessibility**: run a WCAG checklist pass + fix critical issues.
  - **🟡 Multilingual**: ensure all public/portal strings and key CMS content strategies support bn/en.
- **Done when**
  - A deploy checklist can be followed end-to-end with monitoring + backups in place.

---

## Milestone 1 — Public pages (Phase 1 + Phase 2) (2–3 weeks)

### 1) Home (`/`)
**Data sources**
- News: `news` table/model (or CMS content)
- Events: `events` table/model
- Testimonials: CMS content block
- Highlights: computed counts (students, teachers) or configured numbers

**Implementation**
- ✅ Controller: `Web/HomeController@index` composes:
  - featured hero content
  - latest news (limit 3–5)
  - upcoming events (limit 3–5)
  - highlight stats (cached)
  - testimonials (CMS)
- ✅ View: `resources/views/home.blade.php`

### 2) About (`/about`)
**Implementation**
- ✅ Uses CMS page content blocks (structured JSON) for each section:
  - history, mission, vision, values, principal message, admin list, facilities, achievements, anthem/emblem
- ✅ View: `site.page` renderer (`SitePageController@about`)

### 3) Academics (`/academics`)
**Implementation**
- ✅ CMS blocks supported via `WebsiteContent`
- ❌ Academic calendar model/component not confirmed (needs explicit implementation)

### 4) Admissions (`/admissions`)
**Implementation**
- ✅ CMS blocks supported via `WebsiteContent`
- ✅ Routes exist for `/admissions/apply` and `/admissions/status`

### 5) Students (`/students`)
**Implementation**
- ✅ CMS blocks supported via `WebsiteContent`
- 🟡 Calendar/exam schedule content is CMS-dependent; no dedicated scheduling UI confirmed

### 6) Faculty (`/faculty`)
**Implementation**
- ✅ Staff directory: DB-driven (`teachers` + `users`)
- 🟡 Additional sections via CMS blocks depend on content population

### 7) News & Events (`/news`)
**Implementation**
- ✅ Index page exists: latest news + upcoming/past events
- 🟡 Detail page exists; OG meta tags/related items not confirmed
- ❌ Categories/tags not confirmed

### 8) Gallery (`/gallery`)
**Implementation**
- ✅ Gallery listing exists (grouped by category)
- ❌ Album + media item model not confirmed (current model appears to be flat gallery items)
- 🟡 Video gallery / virtual tour possible via CMS/content conventions; not explicitly implemented

### 9) Contact (`/contact`)
**Implementation**
- ✅ `ContactSubmission` model + handlers exist
- ✅ Honeypot field exists; ✅ rate limiting middleware exists; ❌ CAPTCHA not implemented
- 🟡 Map embed supports a config setting + fallback link

### SEO strategy tasks (parallel)
- ✅ `sitemap.xml` generation route implemented (`/sitemap.xml`)
- ✅ `robots.txt` implemented (public file + sitemap link)
- ❌ schema.org markup not confirmed
- ✅ Titles/meta descriptions are editable in CMS (`WebsiteContent`)

---

## Milestone 2 — CMS completion (1–2 weeks)

### CMS requirements (from plan)
- **News & events management** ✅: admin CRUD implemented (dashboard)
- **Gallery management** ✅: admin CRUD implemented (dashboard)
- **Document management** 🟡: admissions document upload exists; general document manager not confirmed
- **Form submissions viewer** ✅: dashboard route exists for contact submissions
- **Newsletter system** 🟡: newsletter submissions stored; mailing/archive features not confirmed
- **User management (roles + permissions)** 🟡: RBAC exists; full admin UI not confirmed

### Implementation tasks
- Admin dashboard module pages:
  - ✅ CMS pages editor (for the public pages’ content blocks)
  - ✅ News CRUD
  - ✅ Gallery CRUD + media upload
  - ✅ Contact submissions list/detail with status labels
- File storage:
  - ✅ Local dev: `storage/app/public`
  - 🟡 Production: possible via `FILESYSTEM_DISK`, but deployment configuration not included here
- Security:
  - ✅ Upload validation exists for admissions docs (mime/size)
  - 🟡 Authorization policies exist in places; full CMS policy coverage not confirmed

---

## Milestone 3 — Online admission system (2 weeks)

### Routes / pages
- ✅ `/admissions/apply`: application form + document upload
- 🟡 `/admissions/status`: status lookup implemented; logged-in applicant dashboard not confirmed

### Data model
- ✅ `admissions` table exists
- ✅ `admission_documents` table exists
- ✅ `admission_tests` scheduling implemented

### Workflow
- ✅ Apply → create admission + documents
- ❌ Confirmation email/SMS not confirmed
- 🟡 Admin review/status update workflow exists partially; notifications not confirmed
- Optional payment:
  - application fee invoice/payment record linked to admission

### Test plan
- Apply with/without docs
- Invalid file types rejected
- Status updates trigger notifications
- Access control: only admin/staff can review

---

## Milestone 4 — Payments portal + integration (2 weeks)

### Public portal `/payments`
- ✅ Show active fees + gateways + payment history (for authenticated student/parent)
- ❌ Selecting student/admission from UI not confirmed
- ❌ Choosing fee items or plan not confirmed
- 🟡 Paying via gateway: backend controller exists; public initiation UX not confirmed
- ✅ Downloading/printing receipts implemented (receipt page)

### Backend
- 🟡 Payment gateway abstraction exists (controllers/services present), needs end-to-end verification
- 🟡 Flows exist in DB/models; not fully verified from public portal
- 🟡 Webhook endpoint exists; signed verification/idempotency not confirmed

### Security / compliance
- Never trust client payment status
- Signed webhook verification
- Audit log for payment state changes

---

## Milestone 5 — Student/Parent portal (3 weeks)

### `/portal` behavior
- Student/parent portal dashboard:
  - ✅ attendance list + summary
  - ✅ exam results (published)
  - ✅ fee status + payments
  - ✅ assignments/homework
  - 🟡 progress reports (term-wise) not implemented; summary exists
  - ✅ communication/announcements (announcements feed implemented; direct messaging not implemented)

### Data access rules
- ✅ Student: own records
- ✅ Parent: linked children
- ✅ Staff roles route to dashboard

### Implementation tasks
- Ensure relationships exist and are eager-loaded:
  - Guardian → students
  - Student → attendance/results/fees/assignments
- Add portal-friendly views and pagination.
- Add notification preferences + announcements feed.

---

## Milestone 6 — Testing, hardening, and launch (2 weeks)

### Automated tests
- ❌ Feature tests for these flows not confirmed
- ❌ Policy test coverage not confirmed

### Performance
- 🟡 Some queries are limited; caching strategy not implemented systematically
- 🟡 Lazy-load/optimization not confirmed

### Observability
- ❌ Error monitoring integration not implemented
- 🟡 Webhook logging exists in backend controller
- ❌ Backup strategy not implemented

### Launch checklist
- 🟡 App supports these settings; deployment checklist is not automated
- 🟡 sitemap + robots implemented; schema.org still not implemented

---

## File/area map (where changes typically go)
- **Routes**: `backend/routes/web.php`
- **Public controllers**: `backend/app/Http/Controllers/Web/*`
- **Dashboard controllers**: `backend/app/Http/Controllers/Web/Dashboard*`
- **Models**: `backend/app/Models/*`
- **Policies**: `backend/app/Policies/*`
- **Views (public)**: `backend/resources/views/site/*`
- **Views (dashboard)**: `backend/resources/views/dashboard/*`
- **Migrations**: `backend/database/migrations/*`
- **Seeders**: `backend/database/seeders/*`
- **Localization**: `backend/resources/lang/*`

---

## Acceptance criteria (definition of “fully implemented”)
- Every route in the navigation works and matches its section list in `website-plan.md`.
- CMS can update all public page sections without code changes.
- Admissions apply + document upload + status check fully functional.
- Payments: at least one gateway integration works end-to-end (including webhook) and receipts can be downloaded.
- Portal: student/parent sees correct data; staff roles redirect to dashboard; authorization enforced.
- SEO basics: titles/meta/OG, sitemap, robots, clean URLs.
- Accessibility: forms labeled, keyboard nav works, reasonable contrast.
- Multilingual: at least English + one local language across public + portal UI.

### Current acceptance status
- **Routes** ✅
- **CMS pages** ✅ (JSON-based); **full CMS suite** 🟡 (newsletter sending, document manager)
- **Admissions** ✅ (apply + documents + status)
- **Payments end-to-end + receipts** 🟡 (receipts done; end-to-end initiation/status UI still partial)
- **Portal depth (progress + messaging)** 🟡 (announcements done; term-wise progress + messaging still pending)
- **SEO (sitemap/robots/OG)** 🟡 (sitemap/robots/OG/canonical done; schema.org pending)
- **Accessibility** 🟡
- **Multilingual** 🟡










What’s still not fully done (from the plan)
❌ Not implemented

Maintenance/operations as a full system (monitoring + scheduled backups + ops checklist)
Observability (e.g., Sentry/error monitoring)
Payment plans (installments) (optional “if required” in the plan)
Academic calendar (explicit model/component)
CAPTCHA on contact form
News categories/tags
Gallery albums/media model (currently “flat” items)
Some tests/policy coverage (plan still marks as ❌)
🟡 Partial

Payments end-to-end gateway flow verification (initiate → callback/webhook → final paid + receipt)
Portal UX (pagination/empty states polish) and messaging (announcements done; inbox-style messaging not)
Accessibility (WCAG pass not completed)
Multilingual coverage (bn/en coverage not complete)
Analytics (GA hook exists; no reporting dashboard)
A few content-dependent items (Faculty/News/Gallery “advanced sections” rely on CMS content conventions)
If you want, I can now update implementaion-plan.md to reflect the new Phase F progress we actually implemented (tests baseline + backup command + a11y/perf tweaks), so the tracker matches the current code.