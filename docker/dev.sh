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
#   ./docker/dev.sh seed [PROD|all]        seed demo data (DB + demo accounts)
#   ./docker/dev.sh logs [-f] [PROD|all]   tail logs
#   ./docker/dev.sh ps [PROD|all]          container status
#   ./docker/dev.sh urls                   print URLs for every product
#   ./docker/dev.sh exec <PROD> <cmd...>   run a command inside a product's app
#   ./docker/dev.sh build <PROD|all>       build image(s) without starting
#
# All product folders are bind-mounted read-write, and the containers run the
# products' own dev servers (Next/Vite hot reload / PHP built-in server), so a
# change you make in the source tree is picked up on the next browser refresh —
# no rebuild step is ever needed.

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

cmd="${1:-up}"; shift || true
case "$cmd" in
  up)              cmd_up "${1:-all}";;
  down)            cmd_down "${1:-all}";;
  seed)            cmd_seed "${1:-all}";;
  logs)            cmd_logs "$@";;
  ps|status)       cmd_ps "${1:-all}";;
  urls|url)        cmd_ps_all;;
  build)           cmd_build "${1:-all}";;
  exec)            cmd_exec "$@";;
  *) echo "usage: $0 {up|down|seed|logs|ps|urls|build|exec} [all|laravel|node|php|theme|website]" >&2; exit 1;;
esac