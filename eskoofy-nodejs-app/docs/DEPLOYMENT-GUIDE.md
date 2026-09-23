# Eskoofy Node.js Variant — Deployment Guide

> Product: `eskoofy-nodejs-app` (Next.js 15 + Prisma) · Production deployment for the `bd`
> (Bangladesh) or `int` (international) variant. Install & config live in
> [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); sizing in [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md).

Ship the **variant artifact** `eskoofy-nodejs-app-bd.zip` / `eskoofy-nodejs-app-int.zip`
(`build/export.sh node bd|int`). The artifact carries the matching `.env` profile, the
`en`/`bn` locale bundles, and sources — **`npm run build` runs on the server** (like the PHP
products, the Node artifact ships source, not a compiled `.next`).

---

## 1. Prerequisites

- A server meeting [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) (Node 20.9+, MySQL/MariaDB,
  reverse proxy).
- A MariaDB/MySQL database for the app (e.g. `eskoofy_node`).
- TLS ready for the domain.

---

## 2. Upload & install

```bash
# example: /opt/eskoofy
sudo mkdir -p /opt/eskoofy && cd /opt/eskoofy
sudo unzip /path/to/eskoofy-nodejs-app-int.zip
sudo mv eskoofy-nodejs-app-int app

cd app
sudo npm ci --omit=dev          # install prod deps + prisma generate (or `npm ci` for the build)
sudo chown -R node:node .next node_modules public/uploads  # your app user may differ
```

> The artifact ships `.env` pre-shaped for the variant — **verify** it (edit `DATABASE_URL`,
> `AUTH_SECRET`, `APP_URL`). Never ship real secrets in the artifact.

## 3. Configuration tuning (`.env`)

| Key | Production value |
|---|---|
| `NODE_ENV` | `production` |
| `APP_URL` | your real `https://` URL |
| `DATABASE_URL` | `mysql://user:pass@127.0.0.1:3306/eskoofy_node` |
| `AUTH_SECRET` | fresh long random string — keep private |
| `ESKOOFY_VARIANT` | `bd` or `int` (already set by the artifact) |
| `APP_TIMEZONE` | `Asia/Dhaka` (`bd`) / `UTC` (`int`) |
| `SESSION_SECURE`… | tighten the session cookie to HTTPS in production if the code exposes it |

## 4. Database

```bash
npm run prisma:generate        # generate the client for this platform (postinstall does this too)
npm run prisma:push            # sync the 107 tables to the DB
npm run db:seed                # first admin: admin@school.com / ChangeMe!2026$Tr0ng  (change it!)
```

> `prisma:push` is additive/idempotent for new tables; destructive schema changes are managed
> through the app's migrations then re-introspection — follow the app's migration process.

## 5. Build & run

```bash
npm run build                  # production build into .next
npm start                      # serves on port 3000 (NODE_ENV=production)
```

### systemd unit

```ini
# /etc/systemd/system/eskoofy.service
[Unit]
Description=Eskoofy Node variant (Next.js)
After=network.target mariadb.service

[Service]
User=node
WorkingDirectory=/opt/eskoofy/app
Environment=NODE_ENV=production
ExecStart=/usr/bin/npm start
Restart=always

[Install]
WantedBy=multi-user.target
```

Or PM2: `pm2 start npm --name eskoofy -- start && pm2 save && pm2 startup`.

## 6. Reverse proxy (nginx)

```nginx
server {
    listen 443 ssl;
    server_name eskoofy.example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # static uploads straight from disk
    location /uploads/ { alias /opt/eskoofy/app/public/uploads/; }
}
```

TLS via certbot or Cloudflare. Keep `X-Forwarded-Proto: https` so Next issues HTTPS URLs.

## 7. Backups

- **Database:** `mysqldump` (or the app's backup flow) — schedule a nightly dump:
  ```cron
  0 2 * * * mysqldump -u USER eskoofy_node | gzip > /var/backups/eskoofy-$(date +\%F).sql.gz
  ```
- **Uploads:** rsync `public/uploads/` + the DB dump off-host.
- Restore = re-import the SQL + re-point `.env`.

## 8. Update / rollback

**Update:**

```bash
cd /opt/eskoofy
sudo unzip -o /path/to/new/eskoofy-nodejs-app-<variant>.zip
cd app
sudo npm ci && sudo npm run build
sudo systemctl restart eskoofy
```

**Rollback:** the previous `.zip` is the rollback unit — re-extract, re-build, restart; restore
the DB if the schema changed in between.

## 9. Verification

```bash
curl -I https://eskoofy.example.com/               # 200
curl -I https://eskoofy.example.com/dashboard/     # 200 (or a redirect to /login)
curl -s https://eskoofy.example.com/api/v1/ping    # {"success":true,...}
journalctl -u eskoofy -n 50                        # clean startup logs
```

| Gate | Command | Expect |
|---|---|---|
| Typecheck / lint / tests | `npm run typecheck && npm run lint && npm test` | pass |
| Parity | `npm run route:parity` | 585/585 routes, 107 tables |

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`SERVER-REQUIREMENTS.md`](./SERVER-REQUIREMENTS.md) — sizing
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`PORTING-STATUS.md`](./PORTING-STATUS.md) · [`NOT-IMPLEMENTED.md`](./NOT-IMPLEMENTED.md)
- [`../../build/README.md`](../../build/README.md) — the export/build box
- [`../../docs/operations/PRODUCTION-CHECKLIST.md`](../../docs/operations/PRODUCTION-CHECKLIST.md)