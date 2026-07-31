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

cerate a file named features-implementation.md adn add all feature swe have added, partially added and not added yet to track the full feature development

implement certificate, routine, assignment, admit card, student id card, all pertially implementd to fully implemetnted. also ensure that all features whose need ui and public page , contents and sections are fully implemented

why certificate admit card student id side link not showing?

admit card and student id  card two separate thing

is notification and payemt system integrated and functional now?



add a claender page in the dashboard for showing school activities, academic and govt holidays, upcoming events and more adn make them avalable to all dashboard users. 

add fetaure for activating or non activationg for controlling show/hide of every front/public site sections

when clicking on website cms items like gally or all pages, the page is jumping. fix the issue.

setup sms notification system

add students filter based on class, batch in the dashboard students page

make subgroup of academic sidebar items and make the sidebar items more nicer and organised

add a doc/help sidebar item and add all docmuntaion to us and guideling of this managemnt application an d the public webiste

add option for adding pricipal image and show that image in the homepage priciple message section. aslo make the priciple message text updating option both in bn and en

add language chnage option for the dashboard language also so that users can easily understand the dashboar itesm and functionalities.

make the homepage hero section more attractive so that visiotrs can get a clear view of the school from the hero section. add option for adding the scholl name in big fon size with nice design and chnaging design option from dashboard. aslo add a notice section at right side of the hero section. and add a marquee in the header for showing any urgent notice. ensure there is notice page in dashboard for shoing noraml and urgent notices

though unchecking accepting applicattions, showing the application status in the public website header

bulk attendence not working. fix it 

dashboard header serach button not woking. aslo ensure taht search box wokrs by selecting the secah input filed not ctrl+k press.

move all unnecessary files in a folder named unnecessary-files

make the public website resposive nav menu more arranged and attractive

ensure that all features and functionlaities are working perfectly.





add gurdians and students login and their own profile system. add demo student and gurdians login credentials in the demo-credentials.md file 

ensure that same user type cannot use same mobile number or email 2 times but if 

add a internal messeging sytem to message each other. like teachers to principle, students to teachers, gurdians to teacher inside the system. but the admin will have log and can see all messages all usres activities.

make the backend dashboard all pages ui more modern. elegent. attractive, enterprise grade and professional.

add hostel managemnt options also in the dashboard.

make ensure that all public website contents are chnageable from the dashboard cms.

add testimonial page and functionalities for ithe students like certificates in the dashboard

ensure that school logo and name, address, established year all here reflected in the admit cards, student id cards, certificates, testimonial. btter make admit cards, student id cards, certificates, testimonial fully editable for the admin users.

make the session year based. like 2026, 2027, 2028 not 2025-2026, 2026-2027 like this

public site link not showing in dashboard. add a linke dtext named website which will open in new tab to show the website

ensure that all contents styles are chnaging after chnaing theme in dashboard

ensure that dashboard menu names, sidebar items language is chnaing after cahning the dasshboard language. keep default language

ensure that dashboard has notice page and all normal notice s and urgent notice swill be synced from here.

the hero schol nma ein big size font will be like shadow/overlay an dcenter aligned.

make sure that not homepage section will be controlable for show/hide but all pages contnet will be contrlable. justa dd a check box in tier own edit page beside the sections. no need seperate pages

move admission just belwo the academic in dasboard sidebar

use ligher color in homepage hero background. aslo ensure that if we use background image that will be overlay


create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-2.md file. then implement the tasks using the prompt



from dashboard, after chaging language, theme aslo changing automatically. fix the isuuse. and after manually chnaging theme, the user setting, other header links not working

ensure that dashborad and backend all pages contents colors are chnaging based on theme change

make website language chnage and dashboard language chnage two seperate system

use checkbox for show/hide instead of remove button for page section hide/show feature

add a notice in the website and add that under the news menu item as dropdown. also add a all notice link text in hero notice section/box bottom

make homepage teachers section a nice slider. aslo chnage faculty to teachers

add profile page for all users to chnage password, name and other informations


create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-5.md file. then implement the tasks using the prompt



the dashboard user link header somtimes working and sometimes not working. fix the isseu.

in homepage teachers sections lsider, show 3 teachers in desktop, 2 in tab and 1 in mobile. aslo show 6 tecahers in tecahers page intiallly. others will be shown by clicking a see more button, and will agin hide the hidden tecahers by clciking see less button. 

make help and documentation also bangla version

make help and documentation page more informative, intercative an dguided.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-6.md file. then implement the tasks using the prompt


announcements page showing error

help and documentation page showing error. fix the issue.

why vistor log not logging. fix the issue

ledger page showing issue. fix the issue.

move bulk sms just below the message item

website serach, dahboard sidebar serach and the header serach not working. fix the issues.

use checkbox for show/hide instead of remove button for page section hide/show feature

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-7.md file. then implement the tasks using the prompt


where announcements are showing? 

what the difference between nitice an dannouncement?

will it show in dashboard notification page?

suggest me where can show the announcements

arenot we alseary using notice for maximum of these tasks?

cannot we show the announcement in the header where urgent notices are showing. no need showing urgent notices there. but the annoucements will have option wher to show them. website header or dahboard notification. if dahboard notification, it will show in students, gurdians, tecaher notification page in dashboard/backend. aslo remove urhent option from notice page.

add option for showing in both places

make both bangl and enshlish input fileds for the amnnouncements

add audince option like the notice 

the audience selection not saving and not showing in the dahboard notification

fix in the notice also

but are they working properly?

the annoucement text in website hader showuld be translated wehn language chnaged

the afeatured section award text not transating also in homepage

add toggle vsibility for all pages section and make their div accordion

the setting tabs no need show hide checkbox. only the website page sections need

add others pages sections hide/show like homepage sections

when logged in, showing profile link in webiste headre. remove it.


add show hide for all individual sections, not whole page content

theme color chnaging not working. fix the issue.

ensure that the application is a pwa (progressive web app)

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-8.md file. then implement the tasks using the prompt


afer contact page form submission successfully, show a nice popup with thank message.

not showing any option for installing the app as pwa. add a option in dashboard for insatlling the applicationa s pwa in any devices.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-9.md file. then implement the tasks using the prompt

add a input filed in backend admission page for customising admission notice.

the admission form not working. fix it

headre top announce marquee is too slow. make it bit faster.

make the marquee infinite loop as it not showing full screen

is web hosting enough for running this application?

add privacy policy an terms and conditions page and contents. add the pages in the footer, beside sitemap

move install app in a suitable place rather than header. make privacy policy page contnets also bangla translated. add gap between footer left side logo below text and the social links

add gap between the footer socila links themeselves like the header social linksin dashboard sidebar, there are two group named system. fix the issue. after language chnage in dashboard, theme change donot work. fix the issue also. add image upload feature for users in the my profile page. add demo logos and links in the homepage our partners and affiliation section.

why homepage all section contnets not managle from cms?

I have uploaded log. but it not showing. fix the issue. aslo ensure that logo will replace txet when logo is present in both webiste and the dasboard. aslo add seperat eoption for uploading footer logo as one should dark logo and one should ight logo.

make install app text and footer sitemap text also languaes based chnageable

hero notice box not showing

insatll app bangla not showing when langiage chnaging.

add admin panel and its bnagla when language chnaged just below logo in dahboard

homepage why chosose our school section, our techers title and desc,  what parents and students say title, remarkable students title and desc, reday to join our community  title and desc, our partners and affilaitions title not manageable from cms. make them all manageable from cms.

the home page cms editor page pricipal message section input fileds are mixed an dmesssy. mak eit clear like others section.

why all sections title, desc and others are in one section named Section titles & descriptions? mak eseperate sections foe seperate scetions in homepage and donot cretae dupliacte one whose alreday have like hero section, priciple section, partners section etc.

the install app app name not syncing with the updated school name. fix the issue.

make the homepage fetaures section 4 cards content managle form the homepage cms ediotr page also.

the home page cms editor page pricipal message section input fileds are mixed an dmesssy. mak eit clear like others section.
Principal name (Bengali) (EN)
Principal name (Bengali) (বাংলা)
Message (Bengali) (EN)
Message (Bengali) (বাংলা)
remove these 4 input fileds from priciple section



add user and roles managamnet for admin users

add library feature

add setting page and include fetaures in that page.

add timezone selection feature in setting page.

show selected time zones live time in dashboard hedare

add feature to downlaod markshwwt from result page and from students dashboard

add class specific home task assiging fetaure. add gurdians notes also in the home tasks. also gurdians will be able to sne dnotes using students account

add bkash marcent add feature in admission payment. cerate a clear ui in setting page for configuring th epayment details. we want to use same payemnt details in admission page, syudent salry payemnt purpose or other payments.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-10.md file. then implement the tasks using the prompt



the dashboard theme chmahing button and sidebar darkmoade button not working properly. fix it.

user roles an d permissions page not working fix it.

move school setting page to website group and move the general setting like settings to setting page.

make all dashboard page more arranged. like in students page, add class specific filter or selection

add target slecetion first and the singl euser slecetiion in the send message page.

make the bul sms receipnt filed mor econsice. seperate student and staff. all will ve individual and on eoption with mnmaed all to send all website users.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-11.md file. then implement the tasks using the prompt

move all general setting from school setting page to gneral setting page. cretae  ageneral setting page and add this page in the configuration group

now the general setting page settings move to school setting page and school setting page localization, paymeny, library, admisiiion mov eto general setting page

move general settting page homepage tab contents in a new page named cms setting page under website cms

no. the user managemnet permission page not working. showing error.

add global labels page as a individual page under the website cms like cms setting

add this page to the global labels button in the /dashboard/cms/pages page. move anything possible from old page to this new page. then remove the old page

create a file named wordpress-theme-conversion.md and add all gudies, prompts and essentails inside this file ofr cerating a wordpress theme version of this application




move user amangement to the administration group and help and dcumentation to he help group. cerate help group and administration group seperately first

remove admisiion from general setting page. add payemt configuartion page link to the dashboard payemnt page.

ensure that real activity logs are logging in the activity log.

the ledger page showing error. fix the issue

after cerating backups, the backup files are not showing in the backup page. fix it.

ensure that all media file upload field like photo uplaod in cms pages are directly can be uploaded and saved without relying on the document page. cerate a new page named media and arrange all upload files, documents insid ethis page with category, date filtering

gallery page tab filtering not working. fix it.

add a sldier section in the hompage for showing recent events or activities photos. mak ethis ection manageable from cms fully.

add option for 3 or 4 hero section designs. admin will choose any one dsign design from those and that design will show in the homepage.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-12.md file. then implement the tasks using the prompt



ledger and medi apage showing eroor. fix the issues

add demo conetnet in homepage slider section

i told to create help group an dadministration group like main, webiste etc not sub items group. them move user & roles to the administration group and help & documentation page to the help group. rename back to users and roles

when admisssion will be off, homepage hero admission button will be hidden and contact us button will be shown insated.
search for nay instructions from eduaction ministry for school website 

add a about page and add details of this software in that page.

make priciple message section more arranged. reduce the height of priciple image. ensure that uploaded priciple image will be rendered here.

create a proper prompt to implement the above tasks and add the prompt in features-impl-prompt-13.md file. then implement the tasks using the prompt





i want to make application eligible for multiple languages. what is the best way of doing this?

id the language swither button is more than two, the button will be a select dropdown.




check whole codes of this application for nay security issues.

now write a commit message for the chnages after the last commit


is it possible to make this softaware a fully functional custimizable wordpress theme? how? tell me only.

i want to name this software a nice name for branding.the dahboard sidebar top name will be nmaed after this name and the pwa appliction also willb enamed after this name. suggest me some best nmaes for this application/software.