#!/usr/bin/env bash
#
# Eskoofy cross-product feature propagation gate.
#
# Orchestrates "apply this feature change to all products unless the task says
# otherwise". This script NEVER edits files — it derives and prints the per-product
# file plan and enforces the confirmation gate before any implementation may run.
#
# Usage:
#   ./build/propagate/propagate-feature.sh "add a new dashboard report" [--plan] [--dry-run] [--yes]
#   ./build/propagate/propagate-feature.sh --plan < docs/prompts/features-impl-prompt-17.md
#
# Flags:
#   --plan      print the derived per-product plan and exit (no pause, no edits)
#   --dry-run   print the plan and the commands that WOULD run; make no edits
#   --yes       skip the interactive confirmation (explicit human opt-in)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

PRODUCTS=( "eskoofy-app" "eskoofy-php" "eskoofy-theme" "eskoofy-website" )
FEATURE=""
MODE="gate"

# @@ arg parsing @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
args=()
for a in "$@"; do
  case "$a" in
    --plan)   MODE="plan" ;;
    --dry-run) MODE="dryrun" ;;
    --yes)    MODE="gate-auto" ;;
    *)        args+=( "$a" ) ;;
  esac
done
FEATURE="$(echo "${args[*]:-}" | xargs | sed 's/[[:space:]]*$//')"

if [ -z "$FEATURE" ] && [ ! -t 0 ]; then
  FEATURE="$(cat)"   # prompt piped via stdin
fi
if [ -z "$FEATURE" ]; then
  echo "usage: propagate-feature.sh \"<feature description>\" [--plan|--dry-run|--yes]"
  exit 1
fi

# @@ sanity: every product folder exists @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
for p in "${PRODUCTS[@]}"; do
  [ -d "$ROOT/$p" ] || { echo "error: $ROOT/$p not found"; exit 1; }
done

echo
echo "== Eskoofy multi-product feature gate =="
echo "  Feature : $FEATURE"
echo "  Scope   : default=ALL products (app, php, theme, website)"
echo "  Mode    : $MODE"
echo

# @@ build the per-product file plan @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
plan_app="$(printf '  - eskoofy-app:  controllers/models/routes/views under app/, routes/, resources/views/ + migrations if DB change')"
plan_php="$(printf '  - eskoofy-php:  mirror app code to app/Controllers+Models, and copy resources/views/** byte-identical')"
plan_theme="$(printf '  - eskoofy-theme: views/admin/<slug>.php + inc/front-dashboard.php route + inc/admin-shell.php (title/icon/group) + inc/database.php if table change')"
plan_web="$(printf '  - eskoofy-website: /products copy + features list + pricing/license if monetisable')"

echo "Per-product file plan:"
echo "  Tip: for exact paths, run:  rg '<feature>' eskoofy-app/ | head"
echo "$plan_app"
echo "$plan_php"
echo "$plan_theme"
echo "$plan_web"
echo

# @@ confirmation gate @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
case "$MODE" in
  plan)
    echo ">> plan mode: plan printed, exiting without changes. OK."
    exit 0
    ;;
  dryrun)
    echo ">> dry-run mode: would run per-product verification afterwards:"
    echo "     (cd $ROOT/eskoofy-app && composer test)"
    echo "     (cd $ROOT/eskoofy-php && composer test)"
    echo "     (cd $ROOT/eskoofy-theme && php -l)   on touched files"
    echo "  No changes made. OK."
    exit 0
    ;;
  gate-auto)
    echo ">> --yes given (no confirmation pause)."
    ;;
  *)
    echo "Apply to ALL 4 products? [y/N] "
    read -r -n1 ans
    echo
    case "$ans" in
      y|Y) echo ">> confirmed — proceeding via default agent/harness." ;;
      *)
        echo ">> aborted. When you scope a change to one product, say so explicitly "
        echo "   (e.g. \"only in eskoofy-app\"). See docs/FEATURE-PROPAGATION.md."
        exit 2
        ;;
    esac
    ;;
esac

# @@ hand off (this script never edits) @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
echo ">> Apply the change to: eskoofy-app → eskoofy-php → eskoofy-theme → eskoofy-website"
echo ">> Then run the verification gates in docs/FEATURE-PROPAGATION.md (last section)."
exit 0