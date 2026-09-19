# Journal — Port dashboard CRUD to eskoofy-php-app

## Objective
Port 17 dashboard CRUD route handlers from eskoofy-laravel-app (Laravel 12, read-only) into the matching raw-PHP controllers under `eskoofy-php-app/app/Controllers/Dashboard/`, so those actions feed the SAME Blade views (`eskoofy-php-app/resources/views/`) the Laravel app feeds. Routes registered centrally later — do NOT touch `eskoofy-php-app/routes/web.php` or `routes/api.php`.

## Constraints
- Never modify `eskoofy-laravel-app/`.
- Never modify `eskoofy-php-app/resources/views/` (byte-identical views already exist).
- Never modify `eskoofy-php-app/routes/web.php` / `routes/api.php`.
- URL `{id}` param names don't matter (routes central). Use `int $id` or `string $page` args.
- Style: `declare(strict_types=1); namespace App\Controllers\Dashboard;` extends `App\Core\Controller`; `use App\Core\Auth; Database; DatabaseInterface; Session;`. Ctor: `$this->db = Database::getInstance();`. `Auth::requireAuth();` first line. Feed via `$this->view('dashboard.x.y', [...])`. Validate `$this->validate([...])`. Flash via `Session::getInstance()->flash('success', '...')`. Redirect `$this->redirect(...)` / `$this->back()`. Guard tables `\App\Core\Schema::hasTable(...)`. `Controller::dashboardShellData()` merges shared sidebar data Rese into every `dashboard.*` view; `paginateRows(array|Collection, $total, $perPage, $page, ?ModelClass)`.
- Two view roots: `resources/views/` Blade `.blade.php` (rendered by `App\Core\Blade`, `$viewRoot`/`$compilePath`); `views/` legacy plain-PHP fallback resolved by `App\Core\View::resolve` (with singular/plural segment variants). Feed the Blade name as the app does.
- Keep `bd` profile behavior today's; don't change unrelated controllers beyond adding methods/vars.

## Verification
- `php -l` every changed file.
- `cd /home/mdjahidhasan/Documents/GitHub/school-management-system/eskoofy-php-app && composer test` (287 tests, must stay green).
- Do NOT register routes.

## Report format per item (final answer)
Controller class + new methods, view fed, one-line approach note, any unresolved view vars.

## Per-item status

- **1 about**: app route is a closure → `view('dashboard.software')`. php DashboardController has no about. Add `about()` feeding `dashboard.software`. View needs no data (uses config app.name, __('')).
- **2 announcements**: app DashboardAnnouncementController create/edit. php AnnouncementController missing create/edit. View `_form` needs `$announcement` (title, title_bn, body, body_bn, audience, display_target, is_published, starts_at, ends_at). create default: is_published=true, audience=['all'], display_target='header'. Views: announcements/create, edit exist.
- **3 attendance**: app DashboardAttendanceController::create → `dashboard.attendance.create` with students (Student::with(user,class) limit 400), teachers (limit 200), batches (limit 80), sections (limit 200), subjects (limit 200), sessions (AcademicSession), statuses (Attendance::getStatuses()), types (Attendance::getTypes()). php AttendanceController missing create. View `attendance/create.blade.php` uses: `$sessions` (->id, ->name), `$students` (->id, ->user?->name, ->class?->name), `$teachers` (->id, ->user?->name), `$statuses` (val→label assoc), `$types` (val→label assoc), `$batches` (->id, ->name ?? ->code ?? '#'.$id), `$sections` (->id, ->name), `$subjects` (->id, ->name). store route dashboard.attendance.store already referenced by legacy template (check php AttendanceController store exists).
- **4 backup**: php BackupController HAS index, create, (download/destroy/restore?). App: index (Storage 'local' disks/backups/*.zip → compact('files') name/path/size/modified), create (backup:run), download/{p}, destroy/{p}, restore/{p}. View `backup/index.blade.php` needs `$files` = list of ['name'=>, 'size'=>int bytes, 'modified'=>timestamp int]. php index currently feeds rows/backups/lastBackup/totalBackups/diskUsage — MUST ALSO feed `$files` in view-expected format (name, size int bytes, modified unix timestamp); adapt. Need restore() (missing), verify download/destroy exist.
- **5 budgets**: php BudgetController missing edit. View `budgets/edit.blade.php` needs `$budget` (model: expense_category_id, period_type, period_start?->toDateString(), period_end?->toDateString(), amount, notes) + `$categories` (->id, ->name). App edit → compact('budget','categories').
- **6 careers**: php CareerController missing show + updateStatus. View `careers/show.blade.php` needs `$application` (->name, ->email, ->phone, ->career?->title, ->cover_letter, ->resume_path, ->status, ->created_at (Carbon->format), ->id). status route dashboard.careers.status. app updateStatus validates status in pending,reviewed,shortlisted,rejected,hired.
- **7 cms**: php CmsController has edit(?string $page). Missing pages(). App pages → `dashboard.cms.pages` with 'pages' + 'registry' = CmsPageRegistry::all(). View `cms/pages.blade.php` uses `$pages`, `$registry[$p->page]['label'/'description']`, `$p->page`, `$p->is_active`, `$siteSettings?->default_locale`. NOTE: view references `$siteSettings` optional — app CmsWebController::pages passes pages+registry only? $siteSettings?-> default checks so optional ok; ensure safe.
- **8 committee**: php CommitteeController has create already (truncated), missing edit. App create → member default (is_active=true, sort_order=0). App edit → compact('member'). Views committee/create uses old(...) only (no $member needed). committee/edit needs `$member` model (name, name_bn, designation, designation_bn, phone, email, sort_order, bio, bio_bn, is_active, photo_url). Add edit.
- **9 documents**: php DocumentController has create, store (edit missing). View `documents/edit.blade.php` unread — need vars. App edit → compact('document','categories') likely.
- **10 favorites**: php FavoriteController::toggle(string $module) EXISTS — module-based, returns favorited bool. App route POST /dashboard/favorites/toggle posts url+label, returns json favorite:bool, prunes to 11. Decide: adapt toggle to optional param + match app JSON shape ('favorite'). Check sidebar JS / helpers for how it's called.
- **11 notices**: php NoticeController missing create/edit (has store/update?). Views notices/create/edit/_form unread — need vars ($notice). App create default notice (pinned=false, audience=['all']).
- **12 notifications**: app NotificationController::list JSON (items{id,type,title,message,url,unread,created_at}, unread_count, csrf) + markAllRead. php NotificationController (index over notification_logs) missing list/markAll. Routes in eskoofy-laravel-app/routes/notifications.php — READ.
- **13 roles**: php RoleController missing create/edit. Views roles/create/edit unread — need vars ($role, $permissions grouped). App create: permissions groupBy first '_' segment. App edit compact('role','permissions').
- **14 users**: php UserController missing edit. Views users/edit unread — need vars ($user, $roles, $permissions...).
- **15 staff (modules)**: eskoofy-laravel-app DashboardModulesController::staff → dashboard.modules.staff ('staff' paginated users w/ roles, 'roles'). php no modules controller; item instructs put in StaffAttendanceController or TeacherController — check which php controllers exist and their current data; read view modules/staff.blade.php for vars.
- **16 settings**: php SettingController has index+updateWebsite (need to inventory full). App: index → dashboard.settings.general compact(settings); general → dashboard.settings.index compact(settings, librarySettings, timezones, mailPresets); cmsSettings → dashboard.settings.cms compact(settings); updateGeneral (POST /settings/general AND /settings/cms per app), updateLocalization (POST /settings/localization). GET /settings/general & GET /settings/cms & POST /settings/localization routes. Views settings/general.blade.php, cms.blade.php, index.blade.php unread — READ. NOTE: no localization.blade.php exists in either (task lists /dashboard/settings/localization route but app route POST only; no GET page).
- **17 media**: php MediaController has index+store; app also download/{id}, destroy/{id}. Verify php has download/destroy; read media/index.blade.php vars.

## Known pains / callouts
- app `/dashboard/about` is a closure (no app controller) — put about() on php DashboardController.
- FavoriteController toggle signature/payload mismatch — must adapt (optional param, return favorite vs favorited, module vs url). Check php table dashboard_favorites schema + helpers.
- backup: app .zip via Storage vs php storage/backups/*.sql glob — keep php approach; restore() missing; adapt index to feed $files (name, size bytes, modified ts) for the Blade.
- settings: GET settings/cms maps to cmsSettings, POST settings/cms maps to updateGeneral (app). Task "GET+PUT" — confirm in app routes file.
- `dashboard.careers.index` and `dashboard.notices.…`/`dashboard.roles.…` etc: ensure feeding exactly the Blade names as app does.

## Files relevant
- Source controllers: eskoofy-laravel-app/app/Http/Controllers/Web/Dashboard{Announcement,Attendance,Backup,Budget,Career,Committee,Document,Favorite,Media,Notice,Role,Setting,User}Controller.php, CmsWebController.php, NotificationController.php, DashboardModulesController.php.
- Source routes: eskoofy-laravel-app/routes/dashboard.php, eskoofy-laravel-app/routes/notifications.php (READ next).
- Target: eskoofy-php-app/app/Controllers/Dashboard/*.php (all exist).
- Views: eskoofy-php-app/resources/views/dashboard/** (feed).
- Core: controllers/Controller.php, View.php, Blade.php.
- php table schemas + models: eskoofy-php-app/app/Models/{Attendance, Student, Teacher, Budget, Notice, Role, User, Settings, MediaItem, CmsPage/WebsiteContent, CommitteeMember, JobApplication, Document}.php — READ as needed.

## Remaining reads
1. eskoofy-laravel-app/routes/notifications.php + full routes/dashboard.php body.
2. App controllers full: Backup (download/destroy/restore), Budget (edit), Career (updateStatus tail), Committee (edit/update), Document (edit/update/destroy), Notification (markAllRead), Role (edit), User (edit), Setting (updateGeneral/updateLocalization tail), Media (download/destroy), DashboardModulesController::staff full.
3. Views: documents/edit, notices/create, notices/edit, notices/_form, roles/create, roles/edit, users/edit, users/create, modules/staff, settings/general, settings/cms, settings/index, media/index, settings/about (if needed), notifications/preferences (markAll trigger), backup/index (DONE).
4. Full php controllers: Announcement, Attendance, Backup, Budget, Career, Committee, Document, Notice, Notification, Role, User, Setting, Media, DashboardController, OnboardingController, TeacherController, StaffAttendanceController — inventory methods.
5. php models + schemas for: Announcement (starts_at etc.), Attendance (getStatuses/getTypes), Student/Teacher relations, Budget, Notice, Role/Permission, User, Settings, MediaItem, CmsPage/WebsiteContent, CommitteeMember, JobApplication, Document.
6. Check helpers for favorites JS (php) + table dashboard_favorites schema.

## Open decisions
- favorites toggle param handling.
- settings GET localization absence.
- staff action placement (TeacherController vs StaffAttendanceController).
- backup index: add $files var (view requires) alongside existing keys.