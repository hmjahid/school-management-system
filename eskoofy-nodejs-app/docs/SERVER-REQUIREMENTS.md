# Eskoofy Node.js Variant — Server Requirements

> Product: `eskoofy-nodejs-app` (Next.js 15 + TypeScript + Prisma) · Use this page to size and
> prepare a server. Install steps live in [`SETUP-GUIDE.md`](./SETUP-GUIDE.md); go-live in
> [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md).

---

## 1. Software requirements

| Software | Minimum | Recommended | Notes |
|---|---|---|---|
| Node.js | **20.9** | **22 LTS** | `package.json` sets `engines.node >= 20.9.0` |
| npm | 10 | 10+ | Prisma + Next build |
| Database | MySQL 8 / MariaDB **10.x** | MariaDB 11 / MySQL 8 | **Required** — same schema as the Laravel app (107 tables) |
| Process manager | systemd / PM2 | PM2 or PM2 cluster | Runs the production Next server |
| Reverse proxy | nginx or Caddy | nginx + TLS | Forward to the Next server port (3000) |
| OS | Linux (glibc) | Debian/Ubuntu LTS | Prisma engines are platform-specific |

### Notes

- **Building on the server** is expected: the artifact ships source, and the deployer runs
  `npm ci && npm run build`. Building locally and uploading `.next` is possible with identical
  Node/prisma versions but is fragile — prefer on-server builds.
- `Postgres` is NOT supported here — the schema targets MySQL/MariaDB (matching the app).

---

## 2. Hardware sizing

| Setup | Min CPU | Min RAM | Recommended |
|---|---|---|---|
| Development (`npm run dev`) | 1 vCPU | 1 GB | 2 GB |
| Small school (< 100 users) | 1 vCPU | 1 GB | 2 GB |
| Medium school (100–500 users) | 2 vCPU | 2 GB | 4 GB |
| Large / multi-branch (500+ users) | 4 vCPU | 4 GB | 8 GB |

Next.js server-side rendering + Prisma benefit from 2+ CPUs; the runtime is lighter than a
full PHP-FPM + node toolchain but the **build step** is memory-hungry (the `next build` phase
can peak above 1 GB — don't use 512 MB boxes).

| Component | Disk |
|---|---|
| Source + `node_modules` | ~400–700 MB |
| `.next` build output | ~150–300 MB |
| Database | 100 MB – 1 GB |
| Uploads/Media (`public/uploads`) | 500 MB – 5 GB |
| OS + packages | 5–10 GB |
| **Total recommended** | **25–50 GB SSD** |

---

## 3. Network & services

| Item | Requirement |
|---|---|
| Inbound | HTTP/HTTPS only (80/443) |
| Outbound HTTPS | Needed for payments, SMS, mail, CDNs (fonts/UI) when integrated |
| Reverse proxy | nginx/Caddy in front of the Next server port 3000 |
| TLS | Let's Encrypt / Cloudflare |
| SMTP / SMS / payment | Same optional services as the Laravel app (gateway integrations pending in this variant — see `NOT-IMPLEMENTED.md`) |

---

## 4. Web server requirements

- nginx (or Caddy) `proxy_pass http://127.0.0.1:3000` for `/`.
- WebSocket/IPC not required; Next runs as a single Node process (cluster with PM2 for scale).
- HTTPS mandatory; terminate at the proxy and forward plain HTTP to Node.
- `public/uploads/` writable by the Node process user.

---

## 5. Minimum viable production stack (summary)

| Piece | Choice |
|---|---|
| OS | Ubuntu 22.04/24.04 LTS |
| Runtime | Node 22 LTS, `npm ci` → `npm run build` → `npm start` |
| DB | MariaDB 11 / MySQL 8 on the same server or managed |
| Process | PM2 (`pm2 start npm --name eskoofy -- start`) or a systemd unit |
| Proxy | nginx + Let's Encrypt TLS → `127.0.0.1:3000` |
| Size | 2 vCPU / 2 GB RAM / 25 GB SSD floor |

---

## Related docs

- [`SETUP-GUIDE.md`](./SETUP-GUIDE.md) — install & configure
- [`DEPLOYMENT-GUIDE.md`](./DEPLOYMENT-GUIDE.md) — production deployment
- [`USER-MANUAL.md`](./USER-MANUAL.md) — usage
- [`../README.md`](../README.md) — architecture & stack
- [`../../docs/planning/server-requirements.md`](../../docs/planning/server-requirements.md) — monorepo-level sizing notes