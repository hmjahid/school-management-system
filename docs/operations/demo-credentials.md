# Demo Credentials

> This is the lowercase alias for [DEMO-CREDENTIALS.md](./DEMO-CREDENTIALS.md).
> The canonical file is `DEMO-CREDENTIALS.md`; this file exists to match
> common tooling that expects lowercase filenames.

See [DEMO-CREDENTIALS.md](./DEMO-CREDENTIALS.md) for the full reference.

## Summary

| Product | How to create demo users |
|---|---|
| **eskoofy-app** (Laravel) | `php artisan migrate:fresh --seed` (requires `ALLOW_DEMO_DATA=true` in production) |
| **eskoofy-php** (raw PHP) | `php database/seed_demo.php` |
| **eskoofy-theme** (WordPress) | Users auto-created on theme activation (`after_switch_theme` → `esk_create_demo_users()`) |

All three products use the same email/password pairs. See the canonical file for the full table.
