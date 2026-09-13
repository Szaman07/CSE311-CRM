# Documentation review record

Baseline specification: 9 September 2026. Implementation synchronization: 12 September 2026. The initial root prototype remains preserved; CRM lives under `inventory/`.

## Completed passes

| Pass | Result |
|---|---|
| Current-state audit | Historical drafts separated from active documents; destructive CRM SQL marked out of scope. |
| Domain and stack | Deals replaced by inventory/sales/customers; XAMPP + Laravel/Blade chosen with a measured version gate. |
| Data and behavior | Seven-table DDL, ERD, FDs, normalization, constraints, lock order, stock bounds, cancellation, and idempotency aligned. |
| Cross-document review | String IDs/money, canonical requests, archived cancellation, report definitions, duplicate carts, and traceability aligned. |
| Implementation sync | Laravel migrations became executable authority; reference SQL, routes, JSON contract, setup, tests, and deployment docs updated to shipped behavior. |
| Runtime evidence | MariaDB migration/seed/checks, PHPUnit workflows, independent-process races, runtime privilege denials, Blade/assets, and OpenAPI validation executed. |

Run the documentation checks from the repository root:

```powershell
python audit/check_documentation.py
python audit/reproduce_current_findings.py
```

The first checker validates local links, requirement IDs, seven DDL tables, constraint names, and sale-contract fixtures. The second preserves a reproducible join-multiplication lesson from the old CRM. Neither substitutes for Laravel/MariaDB tests. Current runtime status and open gates are in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

The remaining review boundaries are explicit: instructor approval, independent backup/restore, NFR-04 benchmark, responsive/keyboard visual evidence, a real Linux target, PostgreSQL/React parity, and the student's unseen SQL/viva performance. Reopen the SRS and documents when the instructor changes scope; do not silently expand the business model.
