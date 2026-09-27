#!/bin/sh
# Eskoofy Node dev entrypoint.
#
# The app source is bind-mounted at /app but `node_modules` (which holds the
# generated Prisma client) and `.next` live in named volumes seeded from the
# image at build time. Whenever `prisma/schema.prisma` changes on the host, the
# running container would otherwise keep serving the STALE generated client —
# new models (e.g. cloud_backup_settings) show up as `prisma.x is undefined`.
#
# So before starting the dev server we always:
#   1. regenerate the Prisma client from the bind-mounted schema, and
#   2. push the schema to MySQL (`prisma db push` is idempotent), so table
#      changes land in the container DB on the next start.
#
# `prisma generate` is cheap and idempotent, so running it on every start costs
# nothing and guarantees the container matches the code you just edited.

set -eu

cd /app

if [ ! -d node_modules/.prisma ]; then
  echo "==> node_modules empty — restoring from image..."
  npm ci --no-audit --no-fund --prefer-offline
fi

echo "==> Regenerating Prisma client from bind-mounted schema..."
npx prisma generate

echo "==> Waiting for database connection..."
i=0
until node -e '
  const net = require("net");
  const [h, p] = [process.env.DB_HOST || "db", Number(process.env.DB_PORT || 3306)];
  const s = net.connect({ host: h, port: p });
  s.on("connect", () => { s.end(); process.exit(0); });
  s.on("error", () => { s.destroy(); process.exit(1); });
  setTimeout(() => process.exit(1), 1500);
' 2>/dev/null; do
  i=$((i + 1)); [ "$i" -gt 40 ] && { echo "DB did not come up" >&2; exit 1; }
  sleep 2
done

echo "==> Pushing schema to the database (idempotent)..."
npx prisma db push --skip-generate

echo "==> Starting: $*"
exec "$@"