read the current codebase carefully and ensure that all features are implemented successfully and functional 



cmd --resume 7afd87e1-6cb4-4f7c-848c-7940df3d01c9


cmd --resume 7afd87e1-6cb4-4f7c-848c-7940df3d01c9


ensure that the application is fully developed and ready to depoly 

is all feature sare designed and implemented? 

write a commit message for the changes after the last commit

ensure that all frontend pages contnets are chnaged to bengali when language is switche dto bengali

make th elogin page preofessional. add padding in top and bottom in the page body

every page will have fixed sections. the scetions contents can be chnaged uisng cm. remove json ediotr from the cms. make the cms more user friendly and useful to a non tech person. add option in cms page to slect deafult lanugae bn or en. the front/public site will load in the default language.

some pages have add section feature remove that. no need this feataure now. 

remove theme chnaging feature from the application

make the dropdown/resposive menu professional. show the resposive header from 1366px to below

why desktop showing hamburger? show from 1366px to below. 1367px to up show dekstop header 

arrange header menu items in category. like relate items put in one item and show them in dropdown.

chnage BN button text to বাংলা. also chnage BN text to বাংলা in backend where neede

in school setting page, make shool name, tagline both for benglai nad english input

make the socila iocns show+hide option in cms.like if user wnat fb link to show, then only the fb icon willl show.

footer follow us text not chnaing with the language chnage

remove newsletter section from footer.

add important links section in footer such as education ministry, primary education ministry, nation info        
  center, secondary and higher secondary department etc 

there will be on/off for admission option from backend unser admission backend. if admission of is selecetd, th eforntend admission paege will show, admission not avavlable now.

make the backeend like enterpris egrade application

the admisnitrator box hiidning under the dashboard boxes. fix it

add features like https://app.bd/ and [webbazaarbd.com](https://webbazarbd.com/) school management software

*****

## Feature Parity Prompt — app.bd & webbazarbd.com

**Objective:** Audit the current school management system and implement missing features inspired by [app.bd](https://app.bd/) and [webbazaarbd.com](https://webbazaarbd.com/) to reach feature parity and make the application enterprise-ready.

### 1. Admission & Online Application
- Public-facing **online admission form** with multi-step flow (name, mobile, institution, role, division → district → upazila cascading, student count category).
- **One-time fee model** (no monthly/recurring charges) — surface this on marketing pages.
- **Admission on/off toggle** in backend (Settings/Admission). When OFF, public admission page shows "Admission not available right now."
- **Post-submission flow:**
  - Auto-generate downloadable **payment receipt** with fee amount + payment number/biller code.
  - **Application status page** (searchable by applicant ID/mobile) showing one of: `Pending Payment`, `Payment Submitted`, `Approved`, `Rejected`.
  - Allow applicant to **submit payment transaction ID** on the status page.
  - On successful verification, generate downloadable **admission confirmation/approval letter**.
  - Paid applicants can re-download confirmation letter anytime.
- Display unpaid applications in admin with pending payment indicator.

### 2. Student Lifecycle & Academic Management
- Student registration, profile, academic history, promotion to next class.
- Class, section, subject, syllabus management.
- **Time-table management** (class + exam routines).
- **Assignment management** (homework/task creation, submission tracking).
- **Examination & result processing:** class/section/subject-wise marks, GPA configuration, class test/semester/grand final exam support, auto-publish to website, SMS push.
- **Public result page** — filterable by Class + Roll + Year, printable marksheet.
- Auto-generate **ID cards, admit cards, seat plans, TC, testimonials, progress reports, invoices** with selectable templates; printable + downloadable.

### 3. Attendance (Digital + Biometric-ready)
- Student & staff attendance (biometric integration ready).
- Real-time server sync.
- Auto-SMS to guardians on absence.
- Daily dashboard widget showing present/absent counts.

### 4. Finance & Accounting (Automated)
- **Fee management:** class/category-based fee structure, pay-slip generation, online collection (gateway integration: bKash/Nagad/bank), column-wise fund distribution.
- Auto **money receipt, bank reconciliation, due reminder SMS**.
- **Expense management:** budget tracking, expense categories.
- **Chart of accounts & ledger setup:** payment/receipt, contra, journal voucher, auto debit/credit, cashbook, bankbook, ledgerbook, cash flow, income statement, balance sheet.
- **Payroll & HR:** staff salary structure, payslip generation, leave management (application + approval workflow).

### 5. Staff & HR
- Staff directory, roles, responsibilities.
- Staff attendance + leave requests with approval chain.
- Payroll slips, increments, deductions.

### 6. Communication & Notifications
- **Bulk SMS** (class/shift/section-targeted) for notices, meetings, urgent alerts.
- In-app **announcements/noticeboard**.
- Per-student messaging; one-click guardian broadcast.
- Push notifications for attendance, result, fee due, events.

### 7. Library, Transport & Hostel
- **Library management:** book catalog, issue/return, fines.
- **Transport module:** bus/route assignment, student pickup points, tracking-ready.
- **Hostel management:** room allocation, resident register.

### 8. Public-Facing School Website (CMS-driven)
- Per-institution **public website** auto-generated (home, about, notice, gallery, result, teacher list, contact).
- **CMS-editable pages** with fixed sections (Hero, About, Notices, Results, Gallery, Stats, Testimonials, FAQ, CTA, Footer).
- **No JSON editor** — form-based editing only (text, image upload, toggle, list).
- **Default language toggle** (BN/EN) per institution; public site loads in that default.
- **Brand color customization:** primary, secondary, accent colors via CMS.
- **Social icons show/hide** — admin enters URL only if they want that icon to appear.
- **Important links section** in footer (Education Ministry, Primary Education, National Info Center, Secondary & Higher Secondary Directorate, etc.) — editable in CMS.
- **Remove newsletter section** from footer.
- **Gallery management** (photos, events).
- Multi-language content (BN/EN) for every public-facing string.

### 9. UI/UX & Layout
- **Header:** category-grouped menu items (Academics ▾, Administration ▾, etc.) with dropdowns.
- **Responsive breakpoint fix:** desktop nav from `≥1367px`, hamburger/mobile nav from `≤1366px`.
- **Login page:** professional spacing, top/bottom padding, centered card, no clutter.
- **BN language label** = "বাংলা" everywhere (header switcher, CMS, settings).
- **Footer "Follow Us"** label must be translatable with language switch.
- Remove theme-changer / dark-mode toggle from app (per earlier decision).

### 10. Dashboard & Admin Experience
- **Enterprise-grade admin shell:** persistent left sidebar, top bar with global search, breadcrumbs, quick actions, role-based widgets.
- Fix z-index / overflow bug where the Administrator tile hides behind Dashboard tiles.
- Role-based login: **Admin, Principal, Teacher** (no student/guardian login in v1); remove Register button from public header.
- Analytics dashboard: student count, today's attendance, fee collection MTD, pending dues, recent notices, upcoming exams.

### 11. Settings
- **School settings:** school name + tagline (both BN and EN fields).
- **Admission settings:** on/off + session dates + fee amount.
- **Language settings:** default BN or EN.
- **Brand color:** primary, secondary, accent pickers.
- **Social links:** each icon has its own show/hide + URL field.
- **Footer links:** editable important links block.

### 12. Non-Functional Requirements
- Mobile-responsive (admin + public).
- Bengali-first UX; full RTL-safe layout.
- SEO-friendly public site (meta, sitemap, schema).
- Audit log for critical actions (admission approval, payment verification, role change).
- Backup & restore (DB + uploaded media).

---

**Deliverable:** A feature-complete, enterprise-grade school management platform with public website, online admission → payment → confirmation flow, full academic + finance + HR + communication suite, and a CMS that a non-technical user can operate.

*****


we wnat admin, principal and teacher login only in the fisrt vesrion of this application now.studnets/gurdians login will add later. remove register button from header now. keep only login

after successfuly applying for aadmission, a recipt will be given to download with fees amount, payment number, and showing that admission is pending for payment. the status page will show admission status. after succesful payemnt, aapproved letter will be given to downlaod. the stus page willl show whom payent not paid and option for submitting payemnt transation id. paid apllications will show padi and option for again downloading the confirmation letter. 


add a result page where all result will be shown based on class, roll, year selection


aslo add a optiont to add/chage brand color. the brand color will be main color. add aslo primary color and secondary color and make them useable to specific contents and sections. 


ensure that all backend admin features are fully functional and aligned with frontend. 

ensure that this applicaion now a fully functional school management system.

make this application a enterprise grade school management system, enhance public site design, features and ui ux. also ensure that all backend fetaures and functionlaities are aligend with forntned and functional. use as much as demo contnets and data so that i can test, check and verfity all feature s an dfunctionalities properly.

now write a commit message for the chnage safter the last commit