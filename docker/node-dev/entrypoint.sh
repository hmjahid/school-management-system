#!/bin/sh
# Eskoofy Node dev entrypoint.
#
# The app source is bind-mounted at /app but `node_modules` (which holds the
# generated Prisma client) and `.next` live in named volumes seeded from the
# image at build time. Whenever `prisma/schema.prisma` changes on the host, the
# running container would otherwise keep serving the STALE generated client —
# new models (e.g. cloud_backup_settings) show up as `prisma.x is undefined`.
#
# Before starting the dev server we always:
#   1. regenerate the Prisma client from the bind-mounted schema, and
#   2. push the schema to MySQL (`prisma db push` is idempotent), so table
#      changes land in the container DB.
#
# Doing that once is not enough. The generated client is a Node module that the
# running dev server already `require`d, so rewriting the file on disk does not
# change the in-memory model set — and Next's watcher deliberately ignores
# node_modules. A schema edit therefore still needed a manual restart. So the
# dev server runs as a child of this script and is RESTARTED whenever the schema
# changes, which is the only way the new client actually gets loaded.
#
# Set ESK_WATCH_SCHEMA=0 to opt out and get the old boot-time-only behaviour.

set -eu

cd /app

WATCH_SCHEMA="${ESK_WATCH_SCHEMA:-1}"
WATCH_INTERVAL="${ESK_WATCH_INTERVAL:-3}"

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

apply_schema() {
  echo "==> prisma/schema.prisma changed — regenerating Prisma client..."
  if npx prisma generate; then
    echo "==> Pushing schema to the database (idempotent)..."
    npx prisma db push --skip-generate || {
      echo "    prisma db push failed — the DB may not match the schema" >&2
    }
  else
    echo "    prisma generate FAILED — leaving the running client untouched" >&2
    return 1
  fi
}

SERVER_PID=""

# Kill the dev server on the way out. Deliberately does NOT call `exit`: a trap
# that exits 0 would clobber the real status, so a dev server crashing with a
# non-zero code would look like a clean shutdown to Docker and to `restart:
# unless-stopped`.
cleanup() {
  trap - EXIT
  if [ -n "$SERVER_PID" ] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi
}
# PID 1 in a container gets no default signal handling, so without this
# `docker compose stop` would wait out the full 10s SIGKILL grace period.
trap cleanup EXIT
trap 'exit 0' INT TERM

schema_stamp() { cksum prisma/schema.prisma 2>/dev/null | cut -d' ' -f1,2; }

echo "==> Starting: $*"
"$@" &
SERVER_PID=$!

if [ "$WATCH_SCHEMA" = "1" ]; then
  echo "==> Watching prisma/schema.prisma (every ${WATCH_INTERVAL}s) — a schema"
  echo "    change regenerates the client, re-pushes the DDL and restarts the"
  echo "    dev server so the new models are actually loaded."
fi

LAST="$(schema_stamp)"

while :; do
  # A dev server that exits on its own must take the container down with it —
  # and with its exit code, or a crash is indistinguishable from a clean stop.
  # `set +e` because a non-zero `wait` would otherwise trip errexit before the
  # status can be captured.
  if ! kill -0 "$SERVER_PID" 2>/dev/null; then
    set +e
    wait "$SERVER_PID"
    code=$?
    set -e
    exit "$code"
  fi

  sleep "$WATCH_INTERVAL"

  NOW="$(schema_stamp)"
  [ "$NOW" = "$LAST" ] && continue
  LAST="$NOW"

  if ! apply_schema; then
    continue # keep serving; the next edit retries
  fi

  echo "==> Restarting the dev server to load the new Prisma client..."
  kill "$SERVER_PID" 2>/dev/null || true
  wait "$SERVER_PID" 2>/dev/null || true
  # Give the listener a moment to release :3000 before rebinding.
  sleep 1
  "$@" &
  SERVER_PID=$!
done
