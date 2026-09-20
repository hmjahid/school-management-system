# MIRROR-PLAN — Node app = full copy of the Laravel app

Scope: EVERYTHING — frontend, dashboard, demo contents, favicon, log,
backup/bulk (done), and the generic engine for headerless modules.
Each item ships ONLY when its gate runs GREEN in the real tree:
  tsc --noEmit && vitest run && eslint .
A row is DONE only after that gate + commit exist. Red stays Red.

## D1 Demo contents (A)
A1 students/fees/staff/admissions demo rows == Laravel seeders values
   (read from real db/seeders/*.php). gate: seeds-parity test.

## D2 Favicon + log (B)
B1 favicon bytes == Laravel public favicon (cmp gate).
B2 log output format == Laravel single-file log layout (vitest gate).

## D3 Frontend mirror (C)
C1 home/features/pricing/contact/auth/about node pages render the same
   headings + links as real Laravel public blades (snapshot gate).

## D4 Dashboard mirror (D)
D1 10 module indexes, 2 pinned <th> order (staff, contact-submissions)
   = DONE committed 4865ec9, gated by resource-parity test.
D2 create/edit forms for the two pinned modules mirroring real Laravel
   form-field order (form-parity test).

## D5 Backup + bulk (E)
E1 portable backups + ZIP restore + bulk import/export = DONE ae32d21,
   both stacks, 80/80 vitest, gate green.

Rule: nothing labeled done is ever RED; each commit is gated in real tree.