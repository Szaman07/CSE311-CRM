# Implementation status

Updated 30 September 2026. Status terms: **implemented** means code exists; **executed-pass** means the named local check ran successfully; **prepared** means configuration/tooling exists but the target exercise was not run. The [latest audit](PROJECT_AUDIT_2026-09-30.md) records fixes and their limits; older environment, Apache and permission evidence below remains dated 12 September.

| Phase | Status | Evidence |
|---|---|---|
| Repository/environment audit | executed-pass | Existing CRM preserved; XAMPP/PHP/Apache/MariaDB/Composer/Node measured in `ENVIRONMENT_EVIDENCE.md`; Laravel 12 compatibility chosen. |
| Migrations/authentication | executed-pass | Seven business tables, checks/FKs/indexes/two invoker views migrated on MariaDB; sessions, throttled login, active account, idle/absolute lifetime, CSRF middleware, and admin CLI commands implemented. |
| Catalog/customers/ledger | executed-pass | Normalization, optimistic versions, archive rules, dedicated stock operations, immutable movement history, customer history, bounded searches/pagination. |
| Sales/cancellation | executed-pass | Exact prices, merged carts, snapshots, atomic writes, canonical UUID/SHA-256 idempotency, ordered product locks, full once-only cancellation. Four coordinated independent-process races pass: last-unit sale, same-key sale, cancellation and same-key stock receipt. |
| Reports/API/Blade | executed-pass | Inventory, low stock, completed recorded value, top products, customer/movement history, reconciliation, CSV; 61 web/API routes; compiled Blade/Vite assets; OpenAPI validates. |
| Least privilege | executed-pass | Actual `nexastock_app` denial checks for history UPDATE/DELETE and DDL passed. |
| Local Apache browser path | executed-pass | XAMPP Apache served only `inventory/public` at `127.0.0.1:8080`; visible login/dashboard/reports rendered. Real HTTP passed CSRF denial, login/token rotation, zero-stock creation, receipt, sale, cancellation, and zero-drift reconciliation. |
| Backup/restore | prepared, not run | Secret-safe scripts supplied and PowerShell parsing passed; an independent restore drill still needs execution and recorded counts. |
| Performance NFR-04 | prepared, not run | A guarded exact-size fixture command and 100-request/five-client workload script are supplied; no latency claim is made before execution. |
| Linux production | prepared, not deployed | Nginx/release/security/recovery guidance supplied; no host exists. |
| React/PostgreSQL | later | Deliberately excluded from semester core until Laravel/SQL mastery and parity tests. |
| Learner mastery NFR-08 | unverified | Only the student can produce unseen SQL/FD/schedule answers and defend lock/idempotency choices. |

Latest local checks: Pint formatting passed; Blade templates compiled; Vite built 60 modules; Redocly validated OpenAPI; Composer and npm reported zero known vulnerabilities. The documentation checker passed the current workspace, including 24 requirements, 20 rules, seven business tables and ten negative contract fixtures. The offline study-guide verifier passed source/export hashes, links, mirrors and independent example calculations. PHPUnit passed **47 tests / 263 assertions** against XAMPP MariaDB 10.4.32. All four coordinated process races assert zero reconciliation drift and no sale/header inconsistencies. A local rendered-cart preview passed add/remove, the 100-row limit, price-review feedback, keyboard activation, row renumbering and focus checks; layouts were inspected at 360px/1280px. This is partial browser coverage.

The application is maintained in [CSE311-CRM](https://github.com/Szaman07/CSE311-CRM), with project learning guides on [nsu-courses/cse311_project](https://github.com/Szaman07/nsu-courses/tree/cse311_project). Existing study-guide work and the preserved root prototype were retained. Runtime secrets, dependencies, logs, backups and temporary preview files are ignored. No UI screenshots were added to the repository.

Remaining acceptance gates are instructor approval of Laravel, an independent backup/restore drill, execution of the supplied exact-size NFR-04 benchmark fixture/workload, archive race evidence, real engine retry-exhaustion fault injection, failures at the remaining transaction checkpoints, a full keyboard-only audit at both target widths, a real Linux deployment if hosting is pursued, and learner viva evidence. The injected contention test verifies response/logging behavior only. These limits do not block local implementation.
