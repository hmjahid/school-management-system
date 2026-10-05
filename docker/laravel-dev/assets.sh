#!/bin/sh
# Eskoofy Laravel — Vite dev server (HMR) launcher.
#
# The lifecycle of public/hot is the whole point of this script. Blade's @vite
# directive resolves assets in this order:
#
#   1. public/hot  exists  -> emit URLs pointing at the dev server (:5173)
#   2. public/build/manifest.json exists -> emit URLs to those prebuilt files
#   3. neither -> throw "Vite manifest not found"
#
# public/hot is written into the bind mount by laravel-vite-plugin when the dev
# server boots, so both containers see it. Two failure modes follow, and both
# make a CSS/JS edit look like "nothing happened":
#
#   * A stale public/hot left behind by a crashed/killed dev server makes Blade
#     emit http://localhost:5173/... URLs for a server that is not listening —
#     every stylesheet 404s. Removed here on both entry and exit.
#   * A stale public/build/manifest.json (a normal `npm run build` artefact,
#     gitignored but often present) silently wins when hot is absent, so Blade
#     serves a prebuilt bundle that predates your edit. This is why the web
#     entrypoint warns about it, and why assets.sh always recreates hot.

set -eu

cd /var/www

HOT_FILE="public/hot"

# A hot file from a previous run is never trustworthy: the server it points at
# is not the one we are about to start.
rm -f "$HOT_FILE"

cleanup() {
  # Only remove the file if it still points at this dev server; if the plugin
  # rewrote it we must not delete the live pointer.
  if [ -f "$HOT_FILE" ] && grep -q '5173' "$HOT_FILE" 2>/dev/null; then
    rm -f "$HOT_FILE"
  fi
}
trap cleanup EXIT INT TERM

if [ ! -x node_modules/.bin/vite ]; then
  echo "==> node_modules missing — npm ci..."
  npm ci --no-audit --no-fund
fi

# The config binds 0.0.0.0 so the published :5173 is reachable but advertises HMR
# on localhost, otherwise laravel-vite-plugin stamps public/hot with
# http://0.0.0.0:5173 and Blade emits URLs no browser can resolve.
echo "==> Starting Vite dev server (HMR on :5173)..."
exec npm run dev -- --config /var/www/vite.config.dev.js
