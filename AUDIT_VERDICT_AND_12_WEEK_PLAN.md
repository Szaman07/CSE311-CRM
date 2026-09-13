# Repository audit and verdict

Updated 9 September 2026. Current direction: **NexaStock, a one-store inventory/sales/customer application**, using Laravel on the required XAMPP environment.

## Verdict

Proceed with this narrower project. It offers strong DBMS learning through M:N relationships, exact numeric data, normalization tradeoffs, transactions, concurrency, history and analytical SQL. Salesforce is useful inspiration for navigation and record detail screens; matching its scope would undermine your three-month learning objective.

Your first priority is independent SQL/exam competence, then a complete tested workflow, then frontend polish. The [12-week plan](docs/LEARNING_PLAN.md) allocates time for all three. Laravel + Blade initially and React/PostgreSQL later is a manageable progression. “Industry standard” should mean clear boundaries, constraints, tests, safe authentication, reproducible setup and honest evidence.

## Scope of inspection and limits

The earlier audit covered the 28 original ideation/SQL/PHP/theory files. That report and originals are retained in [archive](archive/README.md), including the [pre-pass audit](archive/before_documentation_pass_2026_09_09/AUDIT_VERDICT_AND_12_WEEK_PLAN.md). The workspace subsequently contained a newer seven-table plain-PHP CRM prototype. This pass inspects that current code and replaces its active documentation with the user-selected inventory specification.

Current source inspected: ten PHP files (app root and config/db.php), three SQL files, the README, prior audit and seven theory modules. Historical source findings remain historical; they are not presented as bugs in a nonexistent inventory app. The documentation review includes the newly written requirements/design/theory and machine-readable references.

Native PHP/database execution is unavailable in this environment; no claim of working Laravel, imported schema, enforced CHECK, live browser behavior or concurrent correctness. A portable SQL fanout reproduction proves the aggregation counterexample only. Full runtime gates are in [TEST_PLAN](docs/TEST_PLAN.md).

## Current prototype findings

P1 means a serious correctness/security/setup problem before multi-user or public use; P2 means a material learning/quality gap.

| ID | Priority | Evidence and consequence | Target disposition |
|---|---|---|---|
| C01 | P1 | [schema](sql/01_schema.sql) starts DROP DATABASE IF EXISTS nexacrm_db; rerun destroys existing work | Preserve as legacy; new non-destructive migrations in isolated inventory DB |
| C02 | P1 | [DB config](app/config/db.php) uses root/blank password; current app has no login, sessions or enforced role boundary | Laravel auth/policies, restricted runtime DB account |
| C03 | P1 | [close handler](app/close_deal.php) and [detail](app/deal_view.php) take posted user_id/default1; UI can choose actor | Session-attributed actor only |
| C04 | P1 | [company query](app/companies.php) joins contacts and deals independently before SUM | FinCorp's two contacts multiply 75,000+12,000 into 174,000; true total 87,000. Aggregate at correct grain |
| C05 | P1 | [deal creation](app/deals.php) accepts posted stage; forged closed_won bypasses invoice/closed timestamp workflow | Dedicated controlled inventory operations; no client-set state |
| C06 | P1 | Close handler rejects already-won only; crafted request can change closed_lost to won despite hidden button | Explicit allowed state transitions |
| C07 | P1 | invoices.deal_id UNIQUE enforces at most one; comment claims exactly one | Distinguish uniqueness from required child existence |
| C08 | P1 | Mutation forms lack CSRF checks; UI visibility isn't authorization | Framework middleware plus direct-request tests |
| C09 | P2 | Company website escaped into href but scheme not validated server-side | No URL field in inventory core; future URLs need scheme allowlist |
| C10 | P2 | Close handler converts DECIMAL to float and stores subtotal/tax/total independently | Exact decimal strings/SQL totals, boundary/reconciliation tests |
| C11 | P2 | Contact/deal company consistency not constrained; activities may lack both deal/contact | New schema constraints plus explicit optional relationships |
| C12 | P2 | Initial stage history attributes assigned rep as actual actor | Trusted actor recorded by service |
| C13 | P1 | Cascade deletes and broad DB privileges contradict “immutable audit” wording | RESTRICT history references, no delete UI, insert-only ledger grants |
| C14 | P2 | Repeat close request errors rather than returning original invoice | Idempotent sale/manual stock/cancellation contracts |
| C15 | P2 | Prototype primarily creates/lists; no complete edit/archive/search/pagination workflow | SRS names actual Must scope and tests |
| C16 | P2 | Activity insertion occurs before detail lookup and lacks a local safe failure path; SQL errors exposed in several handlers | Validation/policies before write; safe exception handling |
| C17 | P2 | “Revenue” labels describe won value; zero-closure win rate is 0 instead of N/A | Operational metric definitions, no cash claims |
| C18 | P2 | All seed companies have deals; created_at NOW can postdate historical activity; rerun isn't safe | Fixed synthetic timelines and zero-child/adversarial fixtures |
| C19 | P2 | [header](app/header.php) loads runtime Tailwind CDN; [footer](app/footer.php) claims 3NF/ACID without complete evidence | Built assets and qualified design/test statements |
| C20 | P2 | No application test suite, lockfiles, CI, backup/restore or deployment evidence | Explicit release evidence gates |

The existing close handler has a useful transaction/row-lock/rollback skeleton. It is a learning example, not proof that surrounding authorization, state rules and money semantics are correct. Reuse understanding, not unchecked code.

## Historical audit carry-forward

Original commerce drafts contained invalid PostgreSQL UUID literals, an invalid alias, role/default inconsistency, missing RLS policies, missing MySQL checks, an unbound audit trigger, a single-connection “concurrency” page, unsafe/incomplete webhook deduplication/signature handling, and broader unsupported enterprise-readiness claims. Their detailed evidence remains in the archived audit.

None justifies restoring social integrations, agents or vector search to the inventory scope. The existing archived reproduction script uses historical paths and is not the active setup or validation entrypoint.

## Documentation corrections

The active documents now specify inventory rather than deals; XAMPP first; Laravel version compatibility; seven business tables and framework infrastructure distinction; explicit roles/routes/request schemas; bounded stock/money; transaction/idempotency/cancellation behavior; schema normalization tradeoffs; SQL theory and worked problems; PHP/JS/React progression; later PostgreSQL parity; testing/operations and an achievable learning schedule.

The theoretical corrections distinguish SQL bags from relational sets, at-most-one from required existence, FD assumptions from sample data, 2NF from 3NF, serializability from equal final state, MVCC from pure lock protocols, normal views from materialization, and prepared parameters from complete security.

## What to do next

1. Read SRS and draw the ERD yourself.
2. Record course rubric/framework permission and actual XAMPP/PHP/database versions.
3. Work SQL exercises and test reference constraints in a disposable database.
4. Build one Laravel login/category vertical slice, then stock receipt -> sale -> cancellation.
5. Earn portfolio claims with tests, screenshots and a reproducible release.

Don't spend another month expanding documents before trying SQL. This pass is a reviewable baseline; update it when implementation or instructor requirements provide new evidence.
