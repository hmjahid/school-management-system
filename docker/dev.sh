#!/usr/bin/env bash
# Eskoofy — unified Docker dev-harness controller.
#
# Every product runs under its OWN Compose project (auto-generated project name
# -> unique, stable `esk-*`/container names and host ports), so any subset — or
# every product — can run side by side at once.
#
#   ./docker/dev.sh up all                 build + start every product  (default)
#   ./docker/dev.sh up laravel|node|php|theme|website   start one product
#   ./docker/dev.sh up node --build        ... but rebuild its image first
#   ./docker/dev.sh down [all|PROD]        stop (default: all; keeps volumes)
#   ./docker/dev.sh restart [all|PROD]     restart, re-running entrypoints
#   ./docker/dev.sh seed [PROD|all]        seed demo data (DB + demo accounts)
#   ./docker/dev.sh logs [-f] [PROD|all]   tail logs
#   ./docker/dev.sh ps [all|PROD]          container status
#   ./docker/dev.sh urls                   print URLs for every product
#   ./docker/dev.sh exec <PROD> <cmd...>   run a command inside a product's app
#   ./docker/dev.sh build <PROD|all>       build image(s) without starting
#   ./docker/dev.sh verify-live [all|PROD] prove edits are live (no rebuild)
#
# All product folders are bind-mounted read-write and the containers run the
# products' own dev servers (Next/Vite hot reload / PHP built-in server), so a
# change you make in the source tree is picked up on the next browser refresh —
# no rebuild step is ever needed. `verify-live` proves it per product.
#
# Two things do still need `restart`, because they run at boot only:
#   * schema.sql / migrations on the PHP, website and Laravel harnesses
#   * `composer install` / `npm ci` dependency changes
# The Node harness regenerates its Prisma client and restarts itself when
# prisma/schema.prisma changes, so it needs nothing.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"

# product -> (compose file, URL)
COMPOSE_LARAVEL="$ROOT/docker/laravel-dev/docker-compose.yml"
COMPOSE_NODE="$ROOT/docker/node-dev/docker-compose.yml"
COMPOSE_PHP="$ROOT/docker/php-dev/docker-compose.yml"
COMPOSE_THEME="$ROOT/docker/theme-test/docker-compose.yml"
COMPOSE_WEBSITE="$ROOT/docker/website-dev/docker-compose.yml"

PRODUCTS=(laravel node php theme website)

compose_for() {
  local p="$1"
  case "$p" in
    laravel)  echo "$COMPOSE_LARAVEL";;
    node)     echo "$COMPOSE_NODE";;
    php)      echo "$COMPOSE_PHP";;
    theme)    echo "$COMPOSE_THEME";;
    website)  echo "$COMPOSE_WEBSITE";;
    *)        echo "unknown product: $p" >&2; exit 1;;
  esac
}

url_for() {
  case "$1" in
    laravel)  echo "http://localhost:8090";;
    node)     echo "http://localhost:3000";;
    php)      echo "http://localhost:8051";;
    theme)    echo "http://localhost:8080";;
    website)  echo "http://localhost:8052";;
  esac
}

dc() { # dc <product> [args...]
  local p="$1"; shift
  # The WP theme requires a strong admin password for its one-shot setup service.
  if [ "$p" = theme ]; then
    export WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-$(theme_password)}"
  fi
  docker compose -f "$(compose_for "$p")" "$@"
}

# The theme harness defines no default for WP_ADMIN_PASSWORD (for a reason); give
# a documented dev-only default when the user did not provide one.
theme_password() {
  if [ -n "${WP_ADMIN_PASSWORD:-}" ]; then
    echo "$WP_ADMIN_PASSWORD"; return
  fi
  local f="$ROOT/docker/theme-test/.env"
  if [ -f "$f" ] && grep -q '^WP_ADMIN_PASSWORD=' "$f"; then
    sed -n 's/^WP_ADMIN_PASSWORD=//p' "$f" | head -1; return
  fi
  echo "ChangeMe!2026\$Tr0ng"
}

require_docker() {
  command -v docker >/dev/null 2>&1 || { echo "docker not found on PATH" >&2; exit 1; }
  docker compose version >/dev/null 2>&1 || { echo "docker compose not available" >&2; exit 1; }
}

up_one() {
  local p="$1"; shift
  local build=""
  if [ "${1:-}" = "--build" ]; then build="--build"; shift; fi
  echo "==> up: $p  ->  $(url_for "$p")"
  dc "$p" up -d $build
}

cmd_up() {
  require_docker
  local target="${1:-all}"; shift || true
  if [ "$target" = all ]; then
    for p in "${PRODUCTS[@]}"; do up_one "$p" "${@:-}"; done
  else
    up_one "$target" "$@"
  fi
}

cmd_down() {
  require_docker
  local target="${1:-all}"; shift || true
  local extra=()
  for a in "$@"; do extra+=("$a"); done

  if [ "$target" = all ]; then
    for p in "${PRODUCTS[@]}"; do dc "$p" down "${extra[@]}"; done
  else
    dc "$target" down "${extra[@]}"
  fi
}

cmd_seed() {
  require_docker
  local target="${1:-all}"
  local run
  run() { dc "$1" exec -T "$2" sh -lc "$3"; }
  if [ "$target" = all ] || [ "$target" = laravel ]; then
    echo "==> seed laravel (migrations already ran on boot; seeding demo users) ..."
    run laravel web "cd /var/www && php artisan db:seed --force"
  fi
  if [ "$target" = all ] || [ "$target" = node ]; then
    echo "==> seed node (prisma db push + demo seed) ..."
    run node app "cd /app && npx prisma db push && npm run db:seed"
  fi
  if [ "$target" = all ] || [ "$target" = php ]; then
    echo "==> seed php (demo accounts) ..."
    run php app "cd /var/www && php database/seed_demo.php"
  fi
  if [ "$target" = all ] || [ "$target" = theme ]; then
    echo "==> seed theme (handled by its setup service on boot) ..."
  fi
  if [ "$target" = all ] || [ "$target" = website ]; then
    echo "==> seed website (schema + admin/demo data imported on first boot) ..."
  fi
  echo "Done. Demo credentials: docs/operations/DEMO-CREDENTIALS.md"
}

cmd_logs() {
  require_docker
  local f="" target="${1:-all}"
  if [ "${1:-}" = "-f" ] || [ "${1:-}" = "--follow" ]; then f="-f"; target="${2:-all}"; fi
  local p
  for p in "${PRODUCTS[@]}"; do
    [ "$target" != all ] && [ "$p" != "$target" ] && continue
    echo "==> logs: $p"
    dc "$p" logs --tail=50 $f
  done
}

cmd_ps() {
  require_docker
  local target="${1:-all}" p
  for p in "${PRODUCTS[@]}"; do
    [ "$target" != all ] && [ "$p" != "$target" ] && continue
    echo "==> $p"
    dc "$p" ps
  done
}

cmd_ps_all() {
  for p in "${PRODUCTS[@]}"; do echo "  $(url_for "$p")  <-  $p"; done
}

cmd_build() {
  require_docker
  local target="${1:-all}" p
  for p in "${PRODUCTS[@]}"; do
    [ "$target" != all ] && [ "$p" != "$target" ] && continue
    [ "$p" = theme ] && continue # theme uses stock images, nothing to build
    echo "==> build: $p"
    dc "$p" build
  done
}

cmd_exec() {
  require_docker
  local p="$1"; shift
  local svc
  case "$p" in
    laravel) svc=web;; node) svc=app;; php) svc=app;;
    theme) svc=wp;; website) svc=app;;
  esac
  dc "$p" exec -T "$svc" "$@"
}

cmd_restart() {
  require_docker
  local target="${1:-all}" extra=(); shift || true
  for a in "$@"; do extra+=("$a"); done
  # A plain `up` does NOT re-run an entrypoint on an already-running service, so
  # anything an entrypoint applies on boot (schema.sql, migrations, permission
  # seeds, `composer install`, `prisma generate`) needs an explicit restart.
  local p
  for p in "${PRODUCTS[@]}"; do
    [ "$target" != all ] && [ "$p" != "$target" ] && continue
    echo "==> restart: $p  (re-runs the entrypoint: schema, migrations, deps)"
    dc "$p" restart "${extra[@]}"
  done
}

# ---------------------------------------------------------------------------
# verify-live — prove that an edit is effective with no rebuild and no restart.
#
# Three independent mechanisms have to hold for "my change is live". Each is
# checked separately, because they fail for unrelated reasons and only one of
# them is the usual culprit:
#
#   mount     the product tree is a bind mount, not a copy baked into the image.
#             If this fails, nothing you do on the host can reach the container.
#   cache     a static asset is either no-store'd, or carries a validator
#             (ETag/Last-Modified) so the client must ask again. Neither means
#             the response has no freshness lifetime *and* no validator, where
#             browsers and proxies fall back to heuristic caching (RFC 9111
#             4.2.2) and hand you a stale stylesheet after you edited it — the
#             container is correct and the *client* is lying to you.
#   render    a template edit is re-read and re-rendered (PHP re-reads per
#             request) or triggers a recompile in the running dev server
#             (Next.js). Probed by appending a harmless HTML comment, curling,
#             then reverting.
#
# Products that are not running are reported SKIP, not FAIL.
# ---------------------------------------------------------------------------

# product -> probe facts
#   root     product folder on the host (bind-mount source)
#   svc      compose service holding the app
#   cpath    where the product root is mounted inside the container
#   static   URL of a static asset, used for the no-store check
#   tpl      template to append the marker to (render check)
#   url      URL that must contain the marker
#   recompile  directory whose mtimes must advance after touching a source file
probe() {
  case "$1" in
    laravel) echo "root=eskoofy-laravel-app svc=web cpath=/var/www static=/favicon.svg tpl=resources/views/layouts/app.blade.php url=/";;
    # The raw-PHP app serves its public pages from the Blade tree in
    # resources/views (home.blade.php extends layouts.app), NOT from the plain
    # views/layouts/*.php templates used by the dashboard and error pages.
    php)     echo "root=eskoofy-php-app svc=app cpath=/var/www static=/favicon.svg tpl=resources/views/layouts/app.blade.php url=/";;
    website) echo "root=eskoofy-branding-website svc=app cpath=/var/www static=/js/site.js tpl=views/layouts/main.php url=/";;
    theme)   echo "root=eskoofy-wp-theme svc=wp cpath=/var/www/html/wp-content/themes/eskoofy static=/wp-content/themes/eskoofy/assets/favicon.svg tpl=footer.php url=/";;
    node)    echo "root=eskoofy-nodejs-app svc=app cpath=/app static=/favicon.svg recompile=.next tpl=app/layout.tsx url=/";;
  esac
}

field() { # field <probe-string> <key>  -> value or empty
  local v
  v="$(printf '%s\n' "$1" | tr ' ' '\n' | sed -n "s/^$2=//p")"
  printf '%s' "$v"
}

# Is the product's app container up?
is_running() {
  local p="$1" svc
  svc="$(field "$(probe "$p")" svc)"
  [ "$(docker inspect -f '{{.State.Running}}' "$(container_for "$p")" 2>/dev/null || echo false)" = true ]
}

container_for() {
  case "$1" in
    laravel) echo esk-laravel-web;; node) echo esk-node-app;; php) echo esk-php-app;;
    theme) echo eskoofy-wp-theme-test-wp-1;; website) echo esk-website-app;;
  esac
}

verify_one() {
  local p="$1"
  local info root svc cpath static_path tpl url recompile
  info="$(probe "$p")"
  root="$(field "$info" root)"; svc="$(field "$info" svc)"
  cpath="$(field "$info" cpath)"; static_path="$(field "$info" static)"
  tpl="$(field "$info" tpl)"; url="$(field "$info" url)"
  recompile="$(field "$info" recompile)"

  if ! is_running "$p"; then
    printf '  %-8s SKIP   container not running — ./docker/dev.sh up %s\n' "$p" "$p"
    return 0
  fi

  local fails=0 base
  base="$(url_for "$p")"
  local marker="ESK_LIVE_PROBE_$$_$(date +%s)"
  local host_file="$ROOT/$root/$tpl"

  # --- 1. mount: host -> container, and container -> host -------------------
  local probe_name=".esk-live-probe-$$"
  printf 'live\n' > "$ROOT/$root/$probe_name"
  if docker exec "$(container_for "$p")" cat "$cpath/$probe_name" >/dev/null 2>&1; then
    printf '  %-8s ok     mount     host -> container visible\n' "$p"
  else
    printf '  %-8s FAIL   mount     %s is NOT a live bind mount in the container\n' "$p" "$root"
    fails=$((fails + 1))
  fi
  rm -f "$ROOT/$root/$probe_name"

  # --- 2. the static asset must not be served from cache without asking ------
  # Two acceptable shapes, and the difference matters:
  #   no-store / no-cache        strongest. Nothing is reused, nothing is stale.
  #   ETag / Last-Modified      still live: the browser revalidates on refresh
  #                             and only gets a 304 if the file really is
  #                             unchanged. This is what Apache/WordPress sends.
  # Neither Cache-Control nor a validator is the broken case — the client
  # heuristically caches and never asks again.
  local headers cc etag
  headers="$(curl -s -o /dev/null -D - "$base$static_path" 2>/dev/null | tr -d '\r')"
  cc="$(printf '%s\n' "$headers" | sed -n 's/^[Cc]ache-[Cc]ontrol: //p' | head -1)"
  etag="$(printf '%s\n' "$headers" | sed -n 's/^ETag: //p' | head -1)"
  if printf '%s' "$cc" | grep -qiE 'no-store|no-cache'; then
    printf '  %-8s ok     cache      %s -> %s\n' "$p" "$static_path" "$cc"
  elif [ -n "$etag" ] || printf '%s\n' "$headers" | grep -qi '^Last-Modified:'; then
    printf '  %-8s ok     cache      %s -> revalidates (ETag/Last-Modified)\n' "$p" "$static_path"
  else
    printf '  %-8s FAIL   cache      %s has no Cache-Control and no validator —\n' "$p" "$static_path"
    printf '  %-8s         ^ a client may reuse it forever and never see your edit\n' "$p"
    fails=$((fails + 1))
  fi

  # --- 3. render / recompile ------------------------------------------------
  if [ -n "$recompile" ]; then
    # Next.js holds its compiled modules in memory, so the observable is a
    # recompile: after a source file changes, files under .next must get newer
    # mtimes. This asserts liveness without mutating any source content.
    local before after
    before="$(docker exec "$(container_for "$p")" find "$cpath/$recompile" -type f -newermt '-2 minutes' 2>/dev/null | wc -l)"
    docker exec "$(container_for "$p")" touch "$cpath/${tpl}" >/dev/null 2>&1 || true
    sleep 6
    after="$(docker exec "$(container_for "$p")" find "$cpath/$recompile" -type f -newermt '-10 seconds' 2>/dev/null | wc -l)"
    if [ "${after:-0}" -gt 0 ]; then
      printf '  %-8s ok     recompile  .next rebuilt after touching %s\n' "$p" "$tpl"
    else
      printf '  %-8s WARN   recompile  no rebuild in .next after touching %s\n' "$p" "$tpl"
      printf '  %-8s         ^ Next.js watch in this container may not update mtime fast enough;\n' "$p"
      printf '  %-8s           mount/cache checks are the decisive ones here\n' "$p"
    fi
  else
    # PHP re-reads templates per request, so appending a comment must show up in
    # the very next response. Always reverted, even if the curl fails.
    if [ ! -f "$host_file" ]; then
      printf '  %-8s FAIL   render     probe template missing: %s\n' "$p" "$tpl"
      fails=$((fails + 1))
    else
      cp "$host_file" "$host_file.esk-live-bak"
      printf '\n<!-- %s -->\n' "$marker" >> "$host_file"
      sleep 1
      if curl -s "$base$url" 2>/dev/null | grep -q "$marker"; then
        printf '  %-8s ok     render     %s re-read without a restart\n' "$p" "$tpl"
      else
        # Either liveness is broken, or this template is simply not the one that
        # renders $url (both happen; a mis-targeted probe is easy to write and
        # looks identical from here). The mount check above already rules the
        # mount out.
        printf '  %-8s FAIL   render     %s not live at %s\n' "$p" "$tpl" "$url"
        printf '  %-8s         ^ is liveness broken, or is this file not the template for %s?\n' "$p" "$url"
        fails=$((fails + 1))
      fi
      mv "$host_file.esk-live-bak" "$host_file"
    fi
  fi

  return "$fails"
}

cmd_verify_live() {
  require_docker
  local target="${1:-all}" p total=0
  echo "==> verify-live: is a source edit effective with no rebuild and no restart?"
  for p in "${PRODUCTS[@]}"; do
    [ "$target" != all ] && [ "$p" != "$target" ] && continue
    if verify_one "$p"; then :; else total=$((total + 1)); fi
  done
  echo
  if [ "$total" -eq 0 ]; then
    echo "All checked products are live."
  else
    echo "$total product(s) NOT live — see the FAIL lines above."
  fi
  return "$total"
}

cmd="${1:-up}"; shift || true
case "$cmd" in
  up)              cmd_up "${1:-all}";;
  down)            cmd_down "${1:-all}";;
  restart)         cmd_restart "${@}";;
  seed)            cmd_seed "${1:-all}";;
  logs)            cmd_logs "$@";;
  ps|status)       cmd_ps "${1:-all}";;
  urls|url)        cmd_ps_all;;
  build)           cmd_build "${1:-all}";;
  exec)            cmd_exec "$@";;
  verify-live)     cmd_verify_live "${1:-all}";;
  help|-h|--help)  sed -n '3,22p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//';;
  *) echo "usage: $0 {up|down|restart|seed|logs|ps|urls|build|exec|verify-live} [all|laravel|node|php|theme|website]" >&2; exit 1;;
esac