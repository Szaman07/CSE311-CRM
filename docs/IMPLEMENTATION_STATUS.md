# Implementation status

Updated 12 September 2026. Status terms: **implemented** means code exists; **executed-pass** means the named local check ran successfully; **prepared** means configuration/tooling exists but the target exercise was not run.

| Phase | Status | Evidence |
|---|---|---|
| Repository/environment audit | executed-pass | Existing CRM preserved; XAMPP/PHP/Apache/MariaDB/Composer/Node measured in `ENVIRONMENT_EVIDENCE.md`; Laravel 12 compatibility chosen. |
| Migrations/authentication | executed-pass | Seven business tables, checks/FKs/indexes/two invoker views migrated on MariaDB; sessions, throttled login, active account, idle/absolute lifetime, CSRF middleware, and admin CLI commands implemented. |
| Catalog/customers/ledger | executed-pass | Normalization, optimistic versions, archive rules, dedicated stock operations, immutable movement history, customer history, bounded searches/pagination. |
| Sales/cancellation | executed-pass | Exact prices, merged carts, snapshots, atomic writes, canonical UUID/SHA-256 idempotency, ordered product locks, full once-only cancellation. Two-process last-unit and same-key races pass. |
| Reports/API/Blade | executed-pass | Inventory, low stock, completed recorded value, top products, customer/movement history, reconciliation, CSV; 61 web/API routes; compiled Blade/Vite assets; OpenAPI validates. |
| Least privilege | executed-pass | Actual `nexastock_app` denial checks for history UPDATE/DELETE and DDL passed. |
| Local Apache browser path | executed-pass | XAMPP Apache served only `inventory/public` at `127.0.0.1:8080`; visible login/dashboard/reports rendered. Real HTTP passed CSRF denial, login/token rotation, zero-stock creation, receipt, sale, cancellation, and zero-drift reconciliation. |
| Backup/restore | prepared, not run | Secret-safe scripts supplied and PowerShell parsing passed; an independent restore drill still needs execution and recorded counts. |
| Performance NFR-04 | prepared, not run | A guarded exact-size fixture command and 100-request/five-client workload script are supplied; no latency claim is made before execution. |
| Linux production | prepared, not deployed | Nginx/release/security/recovery guidance supplied; no host exists. |
| React/PostgreSQL | later | Deliberately excluded from semester core until Laravel/SQL mastery and parity tests. |
| Learner mastery NFR-08 | unverified | Only the student can produce unseen SQL/FD/schedule answers and defend lock/idempotency choices. |

Final local checks: Pint formatting passed; Blade templates compiled; Vite built 59 modules; Redocly validated OpenAPI; Composer and npm reported zero known vulnerabilities; documentation checker passed 27 Markdown files, 62 links, 24 requirements, 20 rules, and seven business tables. PHPUnit passed **20 tests / 97 assertions** against XAMPP MariaDB, including two coordinated independent-process races. The service-driven demo fixture reconciled with zero differences.

The repository was initialized on branch `main`. Public review material excludes local CVs, third-party inspiration images, runtime secrets, dependencies, backups, logs, and stale archives. GitHub publication status is recorded in the repository history and remote configuration.

Remaining acceptance gates are instructor approval of Laravel, an independent backup/restore drill, execution of the supplied exact-size NFR-04 benchmark fixture/workload, simultaneous cancellation and archive race evidence, retry-exhaustion fault injection, a full keyboard-only audit at both target widths, a real Linux deployment, and learner viva evidence. These limits do not block local implementation.
