#!/usr/bin/env bash
#
# Eskoofy build-box — produce `bd` / `int` deployable artifacts.
#
# Usage:
#   ./build/export.sh app bd
#   ./build/export.sh app int
#   ./build/export.sh php bd
#   ./build/export.sh php int
#   ./build/export.sh theme int
#
# Products: app (eskoofy-app, Laravel), php (eskoofy-php, raw PHP),
#           theme (eskoofy-theme, WordPress), website (eskoofy-website, raw PHP).
#
# Output: build/dist/eskoofy-<product>-<variant>.zip  + the raw tree in
#         build/artifacts/ for inspection.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PRODUCT="${1:?usage: export.sh <product|app|php|theme|website> <variant|bd|int>}"
VARIANT="${2:?usage: export.sh <product> <variant>}"
ARTIFACTS="$ROOT/build/artifacts"
DIST="$ROOT/build/dist"
PROFILES_FILE="$ROOT/build/profiles/profiles.php"

mkdir -p "$ARTIFACTS" "$DIST"

# Load the matching profile (bd/int) as shell variables.
PROFILE_JSON="$(php -r '
    $p = require $argv[1];
    $v = $argv[2];
    if (! isset($p[$v])) { fwrite(STDERR, "unknown variant: $v\n"); exit(1); }
    echo json_encode($p[$v]);
' "$PROFILES_FILE" "$VARIANT")"

PROFILE_LABEL="$(php -r 'echo json_decode($argv[1], true)["label"] ?? "";' "$PROFILE_JSON")"
STRIP_BN="$(php -r 'echo in_array("bn", json_decode($argv[1], true)["locales"] ?? []) ? "no" : "yes";' "$PROFILE_JSON")"

echo "==> exporting $PRODUCT [$VARIANT — $PROFILE_LABEL]"

case "$PRODUCT" in
  app)
    SRC="$ROOT/eskoofy-app"
    [ -d "$SRC" ] || { echo "error: $SRC not found"; exit 1; }
    OUT="$ARTIFACTS/eskoofy-app-$VARIANT"
    STAGE="$OUT-stage"

    rm -rf "$STAGE" "$OUT"
    mkdir -p "$STAGE"

    # rsync the app tree, excluding dev-only / local files.
    rsync -a \
        --exclude '.git/' \
        --exclude 'node_modules/' \
        --exclude 'vendor/' \
        --exclude '.env' \
        --exclude '.env.production.example' \
        --exclude '.phpunit.result.cache' \
        --exclude 'storage/app/backups/' \
        --exclude 'storage/framework/cache/*' \
        --exclude 'storage/framework/sessions/*' \
        --exclude 'storage/framework/views/*' \
        --exclude 'storage/logs/*' \
        --exclude 'archive/' \
        "$SRC/" "$STAGE/"

    # Apply variant profile: .env with the profile's overrides.
    cp "$STAGE/.env.example" "$STAGE/.env"

    php -r '
        $overrides = json_decode($argv[1], true)["env"];
        $path = $argv[2];
        $env = file_get_contents($path);
        foreach ($overrides as $line) {
            $parts = explode("=", $line, 2);
            $key = $parts[0];
            // replace existing key or append
            if (preg_match("/^${key}=.*$/m", $env)) {
                $env = preg_replace("/^${key}=.*$/m", $line, $env);
            } else {
                $env .= "\n${line}\n";
            }
        }
        file_put_contents($path, $env);
    ' "$PROFILE_JSON" "$STAGE/.env"

    # Per-variant resource handling.
    if [ "$STRIP_BN" = "yes" ]; then
        rm -rf "$STAGE/lang/bn"
    else
        # bd is bilingual — keep bn + en
        :
    fi

    mv "$STAGE" "$OUT"
    ;;
  theme)
    SRC="$ROOT/eskoofy-theme"
    [ -d "$SRC" ] || { echo "error: $SRC not found"; exit 1; }
    OUT="$ARTIFACTS/eskoofy-theme-$VARIANT"

    rm -rf "$OUT"
    mkdir -p "$OUT"
    cp -r "$SRC/." "$OUT/"

    # Generate language template if wp-cli is available.
    if command -v wp >/dev/null 2>&1 && [ -d "$OUT" ]; then
        ( cd "$OUT" && wp i18n make-pot . languages/eskoofy.pot --slug=eskoofy 2>/dev/null || true )
    fi
    ;;
  php)
    SRC="$ROOT/eskoofy-php"
    [ -d "$SRC" ] || { echo "error: $SRC not found"; exit 1; }
    OUT="$ARTIFACTS/eskoofy-php-$VARIANT"
    STAGE="$OUT-stage"

    rm -rf "$STAGE" "$OUT"
    mkdir -p "$STAGE"

    # rsync the raw PHP tree, excluding dev / local-only tooling.
    rsync -a \
        --exclude '.git/' \
        --exclude 'vendor/' \
        --exclude '.env' \
        --exclude '.phpunit.cache/' \
        --exclude 'tests/' \
        --exclude 'composer.json' \
        --exclude 'composer.lock' \
        --exclude 'phpunit.xml' \
        --exclude '.gitignore' \
        --exclude 'public/uploads/' \
        --exclude 'storage/' \
        "$SRC/" "$STAGE/"

    # Apply variant profile to .env. eskoofy-php reads APP_TIMEZONE (not TIMEZONE),
    # so translate the profile's generic TIMEZONE key.
    cp "$STAGE/.env.example" "$STAGE/.env"

    php -r '
        $overrides = json_decode($argv[1], true)["env"];
        $path = $argv[2];
        $env = file_get_contents($path);
        foreach ($overrides as $line) {
            $parts = explode("=", $line, 2);
            $key = $parts[0];
            if ($key === "TIMEZONE") {
                $line = "APP_TIMEZONE=" . $parts[1];
                $key = "APP_TIMEZONE";
            }
            if (preg_match("/^${key}=.*$/m", $env)) {
                $env = preg_replace("/^${key}=.*$/m", $line, $env);
            } else {
                $env .= "\n${line}\n";
            }
        }
        file_put_contents($path, $env);
    ' "$PROFILE_JSON" "$STAGE/.env"

    # Per-variant resource handling.
    if [ "$STRIP_BN" = "yes" ]; then
        rm -rf "$STAGE/lang/bn"
    fi

    mv "$STAGE" "$OUT"
    ;;
  website)
    # The licensing site is English-only / USD / UTC in BOTH variants, so it is
    # always exported as `int`. Passing `bd` simply produces the int artifact.
    SRC="$ROOT/eskoofy-website"
    [ -d "$SRC" ] || { echo "error: $SRC not found"; exit 1; }
    OUT="$ARTIFACTS/eskoofy-website-$VARIANT"
    STAGE="$OUT-stage"

    rm -rf "$STAGE" "$OUT"
    mkdir -p "$STAGE"

    # Ship the runtime tree minus dev tooling, secrets, and test doubles.
    rsync -a \
        --exclude '.git/' \
        --exclude 'vendor/' \
        --exclude '.env' \
        --exclude '.phpunit.cache/' \
        --exclude 'tests/' \
        --exclude 'composer.json' \
        --exclude 'composer.lock' \
        --exclude 'phpunit.xml' \
        --exclude '.gitignore' \
        "$SRC/" "$STAGE/"

    cp "$STAGE/.env.example" "$STAGE/.env"

    # English-only, USD, UTC. ESKOOFY_VARIANT stays `int` always.
    php -r '
        $path = $argv[1];
        $env = file_get_contents($path);
        $overrides = ["ESKOOFY_VARIANT=int", "APP_LOCALE=en", "APP_TIMEZONE=UTC", "LICENSE_CURRENCY=USD", "GATEWAY_CURRENCY=USD"];
        foreach ($overrides as $line) {
            $parts = explode("=", $line, 2);
            $key = $parts[0];
            if (preg_match("/^${key}=.*$/m", $env)) {
                $env = preg_replace("/^${key}=.*$/m", $line, $env);
            } else {
                $env .= "\n${line}\n";
            }
        }
        file_put_contents($path, $env);
    ' "$STAGE/.env"

    mv "$STAGE" "$OUT"
    ;;
  *)
    echo "error: unknown product '$PRODUCT' (expected: app|php|theme|website)"
    exit 1
    ;;
esac

# Zip it.
ZIP="$DIST/eskoofy-$PRODUCT-$VARIANT.zip"
rm -f "$ZIP"
( cd "$ARTIFACTS" && zip -rq "$ZIP" "$(basename "$OUT")" )

# INT smoke assertions.
if [ "$STRIP_BN" = "yes" ] && { [ "$PRODUCT" = "app" ] || [ "$PRODUCT" = "php" ]; }; then
    if unzip -l "$ZIP" | grep -q "lang/bn/"; then
        echo "error: bn lang pack leaked into int artifact"
        exit 1
    fi
fi

echo "==> done: $ZIP"
echo "size: $(du -h "$ZIP" | cut -f1)"