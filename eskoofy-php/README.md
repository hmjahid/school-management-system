# Eskoofy PHP — Raw PHP (no framework) version

**Status: NOT STARTED — gated.** See `WORKPLAN.md` Phase 6.

This is a planned full rewrite of the Laravel app (`eskoofy-app/`) in raw PHP with no
framework. It is a separate codebase by definition (different stack, no shared code).

**Gate (Phase 0.2):** work starts ONLY when a concrete target host/market that cannot run
Composer or Laravel is named and documented. Until then this folder stays a stub.

If the gate opens, first deliverables are `ARCHITECTURE.md` (router, auth+RBAC, schema
port, queue-less fallbacks, PDF, gateway drivers) and a vertical-slice proof (e.g. notices
module end-to-end on the target host) before any full porting.

Variants (`bd`/`int`) will be build-time profiles of this single codebase, matching the
monorepo golden rule.