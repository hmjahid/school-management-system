#!/bin/sh
# Eskoofy theme test bootstrap.
#
# Waits for WordPress to be installed, forces a stable site URL,
# activates the Eskoofy theme (which creates all custom DB tables
# via after_switch_theme), and maps every template file to a demo
# page so the whole public site is browsable.
#
# Runs inside the wordpress:cli-php8.2 image as root.

set -eu

WP_PATH="/var/www/html"
CLI="wp --allow-root --path=${WP_PATH}"

echo "==> Waiting for wp-config.php (written by the wp container entrypoint)..."
i=0
until [ -f "${WP_PATH}/wp-config.php" ]; do
  i=$((i + 1))
  if [ "$i" -ge 90 ]; then
    echo "ERROR: wp-config.php never appeared in 3 minutes." >&2
    echo "       Check 'docker compose logs wp' for PHP/install errors." >&2
    exit 1
  fi
  sleep 2
done
echo "    wp-config.php present."

if $CLI core is-installed >/dev/null 2>&1; then
  echo "==> WordPress core already installed; skipping install."
else
  echo "==> Installing WordPress (${WP_URL})..."
  $CLI core install \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
fi

echo "==> Pinning site URL to ${WP_URL} ..."
$CLI option update siteurl "${WP_URL}" >/dev/null
$CLI option update home "${WP_URL}" >/dev/null

echo "==> Activating the Eskoofy theme (creates esk_* tables)..."
$CLI theme activate eskoofy

echo "==> Setting pretty permalinks..."
$CLI rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

echo "==> Seeding demo content (mirrors the Laravel app's seeded data)..."
$CLI eval-file /wptest/seed-demo.php

echo "==> Creating demo pages for every template (idempotent)..."
page_id() {
  slug="$1"
  existing=$($CLI post list --post_type=page --name="$slug" --field=ID --format=ids 2>/dev/null | tr -dc '0-9')
  if [ -z "$existing" ]; then
    $CLI post create --post_type=page --post_status=publish --post_title="$slug" --post_name="$slug" --porcelain
  else
    printf '%s' "$existing"
  fi
}

for slug in \
  about academics admission admissions committee contact events faculty \
  fees gallery login news portal privacy results students terms transport; do
  id=$(page_id "$slug")
  $CLI post meta update "$id" _wp_page_template "template-${slug}.php" >/dev/null 2>&1
  printf '    /%s/ -> template-%s.php\n' "$slug" "$slug"
done

echo
echo "Done. Theme is active."
echo "  Front page : ${WP_URL}"
echo "  Admin      : ${WP_URL}/wp-admin/ (${WP_ADMIN_USER} / ${WP_ADMIN_PASSWORD})"
echo "  WP-CLI     : docker compose run --rm --entrypoint wp setup <cmd>"
echo "  PHP errors : wp-content/debug.log (WP_DEBUG enabled)"