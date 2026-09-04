# Demo Credentials

Credentials for the seeded demo accounts, used for local testing and development.
All accounts are created by `DatabaseSeeder` (`admin@school.com` comes from
`AdminUserSeeder`; the rest from `AdminUserSeeder` / `DemoUsersSeeder` /
`DemoTeacherSeeder` / `DemoStudentSeeder`).

> **Security:** The admin password is supplied by `ADMIN_PASSWORD` in your `.env`.
> Never use these default credentials in production — the seeder refuses to run
> with weak passwords when `APP_ENV=production`.

## Admin

| Name | Email | Password | Role |
|---|---|---|---|
| Super Administrator | `admin@school.com` | from `.env` `ADMIN_PASSWORD` (`ChangeMe!2026$Tr0ng` in dev) | `admin` |
| School Principal | `principal@school.com` | `principal123` | `admin` |

## Named Demo Accounts

| Name | Email | Password | Role |
|---|---|---|---|
| John Smith (Senior Teacher) | `teacher.john@school.com` | `teach1234` | `teacher` |
| Sarah Johnson (Junior Teacher) | `teacher.sarah@school.com` | `teach5678` | `teacher` |
| Demo Accountant | `accountant@school.com` | `accountant123` | `accountant` |
| Demo Librarian | `librarian@school.com` | `librarian123` | `librarian` |

## Bulk Demo Accounts

| Type | Emails | Password |
|---|---|---|
| Teachers | `teacher1@school.com` … `teacher30@school.com` | `password` |
| Students | `student1@school.com` … `student5@school.com` | `password` |
| Parents/Guardians | `parent1@school.com` … `parent10@school.com` | `password` |

## Login URLs

- Admin/staff dashboard: `GET /login`
- Student dashboard: `GET /student/login`
- Guardian dashboard: `GET /guardian/login`

## API Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "admin@school.com", "password": "ChangeMe!2026$Tr0ng"}'
```

## Notes

- Passwords are stored with bcrypt; nothing is stored in plaintext.
- Accounts are pre-configured with roles and permissions (see `DemoUsersSeeder::DEMO_ACCOUNTS`).
- Seeded automatically by `php artisan migrate:fresh --seed` (demo data requires
  `ALLOW_DEMO_DATA=true` when `APP_ENV=production`).
- Change these passwords before any production use.