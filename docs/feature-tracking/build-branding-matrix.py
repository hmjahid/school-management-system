#!/usr/bin/env python3
"""Generate docs/feature-tracking/branding-website-feature-matrix.xlsx.

Tracks features of the eskoofy-branding-website marketing site + license server.
Statuses are derived from repo evidence: routes/web.php + api.php source,
app/Gateways/*, app/Core/Middleware/*, main.php layout, tests/ (103 Unit tests),
config/gateways.php, and .github/workflows/ci.yml (branding tests + export gates).

Run from the monorepo root:
    python3 docs/feature-tracking/build-branding-matrix.py
"""
import os
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.utils import get_column_letter

ROOT = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".."))
OUT_PATH = os.path.join(ROOT, "docs", "feature-tracking", "branding-website-feature-matrix.xlsx")

IMPL = {"Yes": "green", "Partial": "amber", "No": "red"}
WORK = {"Yes": "green", "No": "red", "Not tested": "grey", "N/A": "grey"}
FILL = {
    "green": PatternFill("solid", fgColor="C6EFCE"),
    "red": PatternFill("solid", fgColor="FFC7CE"),
    "amber": PatternFill("solid", fgColor="FFEB9C"),
    "grey": PatternFill("solid", fgColor="D9D9D9"),
    "header": PatternFill("solid", fgColor="1F4E78"),
    "area": PatternFill("solid", fgColor="DDEBF7"),
}
FONT_HEADER = Font(color="FFFFFF", bold=True, size=11)


def S(impl, working=None):
    if working is None:
        working = "N/A" if impl == "No" else "Yes"
    return (impl, working)


# (area, feature, description, (impl, working), notes)
FEATURES = [
    # ── Public site ─────────────────────────────────────────────────────────
    ("Public site", "Home page (hero/slider/CMS content)",
     "Landing page with hero and CMS-driven sections.",
     S("Yes"), "GET / HomeController::index (routes/web.php:28)."),
    ("Public site", "Product pages (/products/{slug})",
     "Dedicated page per product (app / raw-PHP / WP theme / Node variant).",
     S("Yes"), "GET /products/{slug} (web.php:29)."),
    ("Public site", "Pricing page",
     "Plans + pricing overview.",
     S("Yes"), "GET /pricing (web.php:30); plans from DB via Plan model."),
    ("Public site", "Features page",
     "Feature highlights page.",
     S("Yes"), "GET /features (web.php:31)."),
    ("Public site", "Compare page",
     "Side-by-side product comparison.",
     S("Yes"), "GET /compare (web.php:32)."),
    ("Public site", "About page",
     "Company/about content.",
     S("Yes"), "GET /about (web.php:33)."),
    ("Public site", "Legal pages (refund policy / terms / privacy)",
     "Static legal/legalese pages.",
     S("Yes"), "LegalController routes (web.php:34-36)."),
    ("Public site", "Contact page + message form",
     "Contact form stores messages to DB.",
     S("Yes"), "GET/POST /contact (web.php:37-38); inbox in admin."),
    ("Public site", "robots.txt + sitemap.xml",
     "SEO outputs in public/.",
     S("Yes"), "public/robots.txt + sitemap.xml present."),

    # ── Blog ────────────────────────────────────────────────────────────────
    ("Blog", "Blog index (listing + pagination)",
     "Public article list.",
     S("Yes"), "GET /blog SitePostController::index (web.php:41)."),
    ("Blog", "Blog category pages",
     "Articles by category.",
     S("Yes"), "GET /blog/category/{slug} (web.php:42)."),
    ("Blog", "Blog post detail",
     "Article view.",
     S("Yes"), "GET /blog/{slug} (web.php:43)."),
    ("Blog", "Posts admin CRUD",
     "Manage posts (create/edit/delete/publish).",
     S("Yes"), "admin /posts* routes (web.php:110-117)."),
    ("Blog", "Post categories admin CRUD",
     "Manage categories.",
     S("Yes"), "admin /post-categories* routes (web.php:119-126)."),

    # ── i18n & locality ─────────────────────────────────────────────────────
    ("i18n & locality", "en/bn string coverage",
     "Two locale files (lang/en.php, lang/bn.php).",
     S("Yes"), "lang/{en,bn}.php; I18nTest (8 tests)."),
    ("i18n & locality", "Manual locale switch (/language/{locale})",
     "Explicit language selector, wins over geo for the session.",
     S("Yes"), "GET /language/{locale} (web.php:47); manual switch clears geo_locale."),
    ("i18n & locality", "Geo-detected locale (server + client tz backstop)",
     "Auto locale via geo-IP plus /language/geo?tz= client backstop (runs once).",
     S("Yes"), "GET /language/geo (web.php:46); GeoLocaleTest (15) + I18nGeoTest (8)."),
    ("i18n & locality", "BD-vs-int locale/currency behaviour",
     "BD visitors get BDT + bKash/Rocket/Nagad; others USD + Stripe/PayPal/Paddle.",
     S("Yes"), "CheckoutController derives currency: BD→BDT else USD; gateway list geo-driven."),

    # ── Checkout & payments ─────────────────────────────────────────────────
    ("Checkout & payments", "Checkout page",
     "Renders cart + plan selection with local currency.",
     S("Yes"), "GET /checkout CheckoutController::index (web.php:50)."),
    ("Checkout & payments", "Checkout processing",
     "POST /checkout creates order + routes to chosen gateway.",
     S("Yes"), "POST /checkout (web.php:51)."),
    ("Checkout & payments", "Payment status page",
     "Public status lookup by reference.",
     S("Yes"), "GET /checkout/status/{reference} (web.php:52)."),
    ("Checkout & payments", "Manual (offline) gateway driver",
     "Manual payment + admin approval.",
     S("Yes"), "app/Gateways/ManualGateway.php; ManualGatewayTest (2); admin approveManual (web.php:104)."),
    ("Checkout & payments", "Stripe gateway driver",
     "Credit-card checkout via Stripe.",
     S("Yes", "Not tested"), "app/Gateways/StripeGateway.php; live calls need credentials."),
    ("Checkout & payments", "PayPal gateway driver",
     "PayPal checkout.",
     S("Yes", "Not tested"), "app/Gateways/PaypalGateway.php; live calls need credentials."),
    ("Checkout & payments", "Paddle gateway driver",
     "Paddle checkout.",
     S("Yes", "Not tested"), "app/Gateways/PaddleGateway.php; live calls need credentials."),
    ("Checkout & payments", "bKash gateway driver (BD)",
     "bKash 2-step (create-payment + execute-payment).",
     S("Yes", "Not tested"), "app/Gateways/BkashGateway.php; live calls need credentials."),
    ("Checkout & payments", "Rocket gateway driver (BD)",
     "Rocket payment flow.",
     S("Yes", "Not tested"), "app/Gateways/RocketGateway.php; live calls need credentials."),
    ("Checkout & payments", "Nagad gateway driver (BD)",
     "Nagad payment flow.",
     S("Yes", "Not tested"), "app/Gateways/NagadGateway.php; live calls need credentials."),
    ("Checkout & payments", "Gateway factory (env-driven selection)",
     "Picks active driver from config/gateways.php + env.",
     S("Yes"), "app/Gateways/GatewayFactory.php; GatewayFactoryTest (11)."),
    ("Checkout & payments", "USD→BDT rate for BD pricing",
     "GATEWAY_BDT_RATE env derives BDT amounts for BD customers.",
     S("Yes"), "config/gateways.php:10 (default 110).env-driven."),
    ("Checkout & payments", "Gateway webhooks (public, signature-verified)",
     "POST /webhooks/{gateway}, CSRF-exempt via bootstrap, signature checks.",
     S("Yes", "Not tested"), "web.php:173; live verification needs real gateway sandbox."),
    ("Checkout & payments", "Manual payment approval flow (admin)",
     "Approve/reject offline payments and update order status.",
     S("Yes"), "admin POST /payments/{id}/approve (web.php:104)."),
    ("Checkout & payments", "Payments CSV export (admin + account)",
     "Export payments to CSV.",
     S("Yes"), "admin /payments/export (web.php:102); account /payments/export (web.php:65)."),

    # ── License API (license server) ────────────────────────────────────────
    ("License API", "GET /api/v1/ping",
     "Heartbeat for license servers.",
     S("Yes"), "routes/api.php:6; ForceJson enforced."),
    ("License API", "POST /api/v1/licenses/activate",
     "Activate a license for a product installation.",
     S("Yes"), "api.php:7; Throttle:10,1."),
    ("License API", "POST /api/v1/licenses/validate",
     "Validate a deployed license.",
     S("Yes"), "api.php:8; Throttle:30,1."),
    ("License API", "POST /api/v1/licenses/deactivate",
     "Deactivate an installation.",
     S("Yes"), "api.php:9; Throttle:10,1."),
    ("License API", "GET /api/v1/licenses/status",
     "Query license status.",
     S("Yes"), "api.php:10; Throttle:30,1."),
    ("License API", "X-Product-Secret signature auth",
     "License-mutation requests must match X-Product-Secret.",
     S("Yes"), "LicenseApiController.php:29; LicenseApiSecurityTest (3)."),
    ("License API", "Endpoint rate limiting",
     "Throttle middleware on all license endpoints.",
     S("Yes"), "Throttle:<n>,<m> per route; ThrottleMiddleware core."),
    ("License API", "JSON-enforced responses",
     "ForceJsonMiddleware on /api/v1 (no HTML leakage).",
     S("Yes"), "api.php group middleware (ForceJsonMiddleware)."),
    ("License API", "Activity log on license operations",
     "Mutations recorded for admin audit view.",
     S("Yes"), "LicenseManager + admin /activities screen."),

    # ── Customer portal ─────────────────────────────────────────────────────
    ("Customer portal", "Account dashboard (overview + notifications)",
     "Authenticated overview with unread notifications.",
     S("Yes"), "AccountDashboard (web.php:63); mark-read route web.php:78."),
    ("Customer portal", "API token view/regenerate",
     "Regenerate the account API token.",
     S("Yes"), "POST /api-token/regenerate (web.php:64)."),
    ("Customer portal", "Payments list + CSV export",
     "Own payments + export.",
     S("Yes"), "account /payments (web.php:70) + export (web.php:65)."),
    ("Customer portal", "Licenses list + detail",
     "Owned licenses overview and per-license page.",
     S("Yes"), "AccountLicense (web.php:66-67)."),
    ("Customer portal", "License renewal request",
     "Request license renewal.",
     S("Yes"), "POST /licenses/{id}/renew (web.php:68)."),
    ("Customer portal", "Activation revoke",
     "Revoke a device activation.",
     S("Yes"), "POST /activations/revoke (web.php:70)."),
    ("Customer portal", "Downloads (packages + client documents)",
     "Download purchased packages/docs.",
     S("Yes"), "DownloadController (web.php:75-77)."),
    ("Customer portal", "Settings (profile + preferences)",
     "Update account settings.",
     S("Yes"), "Settings routes (web.php:71-73)."),

    # ── Admin backend ───────────────────────────────────────────────────────
    ("Admin backend", "Admin dashboard",
     "Admin overview.",
     S("Yes"), "AdminDashboard (web.php:83-84)."),
    ("Admin backend", "Customers management",
     "List + edit customers.",
     S("Yes"), "CustomerController (web.php:86-88)."),
    ("Admin backend", "Plans CRUD",
     "Create/edit/license plans.",
     S("Yes"), "PlanController (web.php:90-94); PlanTest (2)."),
    ("Admin backend", "Licenses management (status + extend)",
     "Create/license list, update status, extend.",
     S("Yes"), "AdminLicense (web.php:96-100)."),
    ("Admin backend", "Payments admin + approve + export",
     "List, approve manual, export CSV.",
     S("Yes"), "AdminPayment (web.php:102-104)."),
    ("Admin backend", "Subscriptions overview",
     "Recurring subscriptions list.",
     S("Yes"), "SubscriptionController (web.php:105)."),
    ("Admin backend", "Messages inbox",
     "Contact-form messages + mark read.",
     S("Yes"), "MessageController (web.php:128-129)."),
    ("Admin backend", "Activity log screen",
     "Audit trail of admin/customer actions.",
     S("Yes"), "ActivityController (web.php:131)."),
    ("Admin backend", "Visitor log screen",
     "Visitor paths/locations.",
     S("Yes"), "VisitorController (web.php:133); VisitorLogTest (8)."),
    ("Admin backend", "Settings screen",
     "Site settings + support-widget toggle + contact details.",
     S("Yes"), "SettingsController (web.php:135-136); Settings model defaults."),
    ("Admin backend", "Account (profile + password)",
     "Admin profile maintenance.",
     S("Yes"), "AccountController (web.php:138-140)."),
    ("Admin backend", "Services config (per-product features)",
     "Enable/disable features per product for marketing display.",
     S("Yes"), "ServiceController (web.php:142-143)."),
    ("Admin backend", "Packages management (send/download/toggle)",
     "Release packages, toggle visibility, send + download.",
     S("Yes"), "PackageController (web.php:145-149)."),
    ("Admin backend", "Client documents (send/download)",
     "Deliver docs to customers.",
     S("Yes"), "ClientDocumentController (web.php:151-154)."),
    ("Admin backend", "Email templates editor",
     "Edit transactional email templates.",
     S("Yes"), "EmailTemplateController (web.php:156-157)."),
    ("Admin backend", "Push notifications admin + send",
     "Create + send push notifications.",
     S("Yes"), "PushNotificationController (web.php:159-161)."),
    ("Admin backend", "Gateway config screen",
     "Toggle/configure payment gateways.",
     S("Yes"), "GatewayController (web.php:163-164)."),
    ("Admin backend", "Cache management/clear",
     "Cache admin + clear action.",
     S("Yes"), "CacheController (web.php:166-167)."),
    ("Admin backend", "Backup (create/download/delete)",
     "DB backup management.",
     S("Yes"), "BackupController (web.php:169-171)."),

    # ── Support widget ──────────────────────────────────────────────────────
    ("Support widget", "Support widget on public site",
     "Widget partial in main layout.",
     S("Yes"), "views/layouts/main.php:246 support_widget partial; enabled by default."),
    ("Support widget", "Widget toggle via settings",
     "Enable/disable from admin settings.",
     S("Yes"), "Settings key support.widget_enabled (default 1); settings.php:110."),

    # ── PWA ────────────────────────────────────────────────────────────────
    ("PWA", "manifest.json",
     "Web-app manifest (public/manifest.json).",
     S("Yes"), "public/manifest.json present."),
    ("PWA", "Service worker (sw.js + register-sw.js)",
     "Registration script in layout; sw.js cached in public/.",
     S("Yes"), "main.php:264 register-sw.js; public/sw.js."),
    ("PWA", "Offline page (offline.html)",
     "Fallback shell shown when offline.",
     S("Yes"), "public/offline.html present."),
    ("PWA", "App icons",
     "Install icons.",
     S("Yes"), "public/icons/ + apple-touch-icon."),
    ("PWA", "PWA test coverage",
     "PwaTest mocks SW/manifest wiring.",
     S("Yes"), "tests/Unit/PwaTest.php (6 tests)."),

    # ── Security ────────────────────────────────────────────────────────────
    ("Security", "Admin/auth middleware",
     "Admin routes gated; account routes auth-gated.",
     S("Yes"), "AdminMiddleware + AuthMiddleware (app/Core/Middleware)."),
    ("Security", "Security headers middleware",
     "Outbound security headers.",
     S("Yes"), "SecurityHeadersMiddleware."),
    ("Security", "CORS middleware",
     "CORS policy for the license API.",
     S("Yes"), "CorsMiddleware."),
    ("Security", "CSRF protection",
     "CSRF token issue/validate in bootstrap + core forms.",
     S("Yes"), "app/Core/bootstrap.php CSRF session handling."),
    ("Security", "Throttle middleware",
     "Route rate limiting (login, license API, contact).",
     S("Yes"), "ThrottleMiddleware; applied via route options."),
    ("Security", "Locale middleware",
     "Request-locale resolution before controllers.",
     S("Yes"), "LocaleMiddleware."),
    ("Security", "XSS hardening in views",
     "Escaped output via plain PHP e()/esc helpers.",
     S("Yes"), "Views escape dynamic values."),

    # ── Testing & delivery ──────────────────────────────────────────────────
    ("Testing & delivery", "Unit test suite (103 tests / 12 files)",
     "Core, gateways, models, services, security, PWA, geo, i18n.",
     S("Yes"), "tests/Unit/** — 103 function test across 12 files."),
    ("Testing & delivery", "Route-registration consistency test",
     "RouterRegistrationTest.",
     S("Yes"), "RouterRegistrationTest (15)."),
    ("Testing & delivery", "Fake DB for tests",
     "Isolated test backend.",
     S("Yes"), "tests/FakeDatabase.php."),
    ("Testing & delivery", "CI: branding tests + export gate",
     "Workflow runs the branding suite and checks int zip + bn leak.",
     S("Yes"), ".github/workflows/ci.yml 'eskoofy-branding-website Tests' job + artifact checks."),
]

PRODUCT = "eskoofy-branding-website"


def header_row(ws, headers):
    ws.append(headers)
    for c in range(1, len(headers) + 1):
        cell = ws.cell(row=ws.max_row, column=c)
        cell.fill = FILL["header"]
        cell.font = FONT_HEADER
        cell.alignment = Alignment(vertical="center", horizontal="center", wrap_text=True)
        cell.border = Border(*[Side(style="thin", color="BFBFBF")] * 4)


wb = Workbook()
ws = wb.active
ws.title = "Feature Matrix"
HEADERS = ["ID", "Area", "Feature", "Description", "Implemented", "Working", "Notes"]
for i, w in enumerate([6, 18, 46, 42, 12, 12, 62], start=1):
    ws.column_dimensions[get_column_letter(i)].width = w
header_row(ws, HEADERS)
ws.freeze_panes = "A2"
ws.auto_filter.ref = "A1:G1"

for idx, (area, feature, desc, state, notes) in enumerate(FEATURES, start=1):
    impl, work = state
    ws.append([idx, area, feature, desc, impl, work, notes])
    r = ws.max_row
    for c in range(1, 8):
        cell = ws.cell(row=r, column=c)
        cell.border = Border(*[Side(style="thin", color="BFBFBF")] * 4)
        cell.alignment = Alignment(vertical="top", wrap_text=True)
        cell.font = Font(size=9)
    ws.cell(row=r, column=5).fill = FILL[IMPL[impl]]
    ws.cell(row=r, column=5).font = Font(size=10, bold=(impl == "Yes"))
    ws.cell(row=r, column=6).fill = FILL[WORK[work]]
    ws.cell(row=r, column=6).font = Font(size=9)
    area_cell = ws.cell(row=r, column=2)
    area_cell.fill = FILL["area"]
    area_cell.font = Font(bold=True, size=10)
    ws.cell(row=r, column=1).font = Font(size=9, color="808080")

# ── Summary ──────────────────────────────────────────────────────────────────
s = wb.create_sheet("Summary")
s["A1"] = "eskoofy-branding-website — feature tracking summary"
s["A1"].font = Font(bold=True, size=13)

impl_totals = {"Yes": 0, "Partial": 0, "No": 0}
work_totals = {"Yes": 0, "Not tested": 0, "No": 0, "N/A": 0}
for (_, _, _, (impl, work), _) in FEATURES:
    impl_totals[impl] += 1
    work_totals[work] += 1
total = len(FEATURES)

s.append([])
s.append(["Implemented"])
s.cell(s.max_row, 1).font = Font(bold=True)
header_row(s, ["Status", "Count", "%"])
for k, v in impl_totals.items():
    s.append([k, v, round(v / total * 100, 1)])
    for c in range(1, 4):
        cell = s.cell(row=s.max_row, column=c)
        cell.border = Border(*[Side(style="thin", color="BFBFBF")] * 4)
        if k in FILL:
            cell.fill = FILL[k]
s.append(["Total", total, 100.0])
for c in range(1, 4):
    s.cell(row=s.max_row, column=c).font = Font(bold=True)

s.append([])
s.append(["Working"])
s.cell(s.max_row, 1).font = Font(bold=True)
header_row(s, ["Status", "Count", "%"])
for k, v in work_totals.items():
    s.append([k, v, round(v / total * 100, 1)])
    for c in range(1, 4):
        cell = s.cell(row=s.max_row, column=c)
        cell.border = Border(*[Side(style="thin", color="BFBFBF")] * 4)
        if k in FILL:
            cell.fill = FILL[k]

s.append([])
s.append(["Not-yet-live integrations (Not tested — need live credentials) — by area"])
s.cell(s.max_row, 1).font = Font(bold=True)
header_row(s, ["ID", "Feature", "Notes"])
for idx, (area, feature, desc, (impl, work), notes) in enumerate(FEATURES, start=1):
    if work == "Not tested":
        s.append([idx, feature, notes])
        for c in range(1, 4):
            cell = s.cell(row=s.max_row, column=c)
            cell.border = Border(*[Side(style="thin", color="BFBFBF")] * 4)
            cell.font = Font(size=9)
            cell.alignment = Alignment(vertical="top", wrap_text=True)
for col, w in zip("ABCDEFG", [6, 46, 62, 42, 12, 12, 62]):
    s.column_dimensions[col].width = w

# ── Legend ───────────────────────────────────────────────────────────────────
lg = wb.create_sheet("Legend")
lg["A1"] = "eskoofy-branding-website — feature matrix legend"
lg["A1"].font = Font(bold=True, size=13)


def add_key(text, mapping):
    lg.append([text])
    lg.cell(lg.max_row, 1).font = Font(bold=True)
    header_row(lg, ["Value", "Meaning"])
    for k, v in mapping.items():
        lg.append([k, v])
        lg.cell(lg.max_row, 1).fill = FILL[k if k in FILL else ("amber" if k == "Partial" else "grey")]
        lg.cell(lg.max_row, 1).font = Font(size=10, bold=True)
        lg.cell(lg.max_row, 2).font = Font(size=10)
        lg.cell(lg.max_row, 2).alignment = Alignment(wrap_text=True)
    lg.append([])


add_key("Implemented", IMPL)
add_key("Working", WORK)
lg.append(["How this sheet was built"])
lg.cell(lg.max_row, 1).font = Font(bold=True)
lg.append(["• Evidence: routes/web.php + routes/api.php, app/Gateways/*, app/Core/Middleware/*,"])
lg.append(["  views/layouts/main.php, tests/Unit/** (103 tests), config/gateways.php, .github/workflows/ci.yml."])
lg.append(["• 'Not tested' = implemented and unit-covered, but the live/external call (gateway, webhook,"])
lg.append(["  push delivery) has not been verified with real credentials."])
lg.append(["• This site is the marketing + license server for the 4 Eskoofy products and ships int-profile only."])
lg.append(["• Regenerate with: python3 docs/feature-tracking/build-branding-matrix.py"])
for c in "AB":
    lg.column_dimensions[c].width = 16
lg.column_dimensions["B"].width = 120

os.makedirs(os.path.dirname(OUT_PATH), exist_ok=True)
wb.save(OUT_PATH)
print("wrote", OUT_PATH)
print("feature rows:", len(FEATURES))

from openpyxl import load_workbook
wb2 = load_workbook(OUT_PATH)
print("sheets:", wb2.sheetnames)
print("matrix rows:", wb2["Feature Matrix"].max_row - 1)