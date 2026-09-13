# Verification and requirement traceability

This is a required implementation plan, not a report of tests already passing. Reference SQL and JSON parsing are documentation checks only. Run integration tests on the **actual XAMPP engine/version**, not SQLite as a substitute.

## Requirement coverage

| Requirement | Test / evidence |
|---|---|
| FR-01 | T01 valid/invalid/disabled login, generic errors, throttling, session rotation and logout |
| FR-02 | T02 anonymous and clerk direct-write requests, forged actor fields, CSRF enabled |
| FR-03 | T03 canonical category duplicate, blank/oversized name, edit conflict, archive/restore |
| FR-04 | T04 SKU normalization/duplicate, price boundaries, zero initial stock, stock-field rejection |
| FR-05 | T05 optional email/phone, duplicate people allowed, anonymous sale, archived customer race |
| FR-06 | T06 receipt/correction bounds, reasons, retry fingerprint, correction version, archived correction |
| FR-07 | T07 atomic multi-line sale, insufficient stock, duplicate lines, changed prices, last-unit race |
| FR-08 | T08 exact receipt total and snapshots after product rename/reprice; large money stays string |
| FR-09 | T09 cancellation, concurrent retries, clerk rejection, archived product restoration, stock ceiling |
| FR-10 | T10 pagination stability, search escaping, allowlisted sorts, page limits |
| FR-11 | T11 independently calculated fixture reports, cancellations, empty dates, timezone boundaries |
| FR-12 | T12 actor/link/reason/sign/order of timeline; same-timestamp ID tie-break |
| FR-13 | T13 intentional isolated drift/empty header/wrong delta detected without repair |
| FR-14 | T14 CSV escaping/formula neutralization and manager-only export; deferred if Should omitted |
| FR-15 | T15 JS errors/loading/keyboard behavior; React prerequisite exercise; optional |
| FR-16 | T16 later PostgreSQL parity/concurrency and React workflow suite; deferred |
| NFR-01 | T01/T02 plus XSS/injection, safe logging, least-privilege DB and public-root checks |
| NFR-02 | T06/T07/T09/T13 plus actual-engine constraints and injected failures |
| NFR-03 | T17 360px/1280px, keyboard main path, labels/focus/errors/empty state |
| NFR-04 | T18 performance fixture and measured p95 procedure below |
| NFR-05 | T19 clean migration/seed, second setup, independent backup restore |
| NFR-06 | T20 format/unit/integration checks, lockfiles, clean checkout runbook |
| NFR-07 | T12 plus runtime account UPDATE/DELETE denial on ledger/items, retained references |
| NFR-08 | T21 weekly unseen SQL/FD/schedule checks and final viva rubric |

## Critical transaction cases

Use separate connections/processes and explicit barriers, not two calls on one PDO connection. Set a short test lock timeout, capture each outcome, and assert final database rows from a fresh connection. Do not let a test's outer rollback transaction hide committed data from the second connection.

1. Last unit: stock 1; A and B each buy 1 with different keys. Pause A after product lock; start B, confirm it waits; let A commit. B rejects, final stock 0, one sale/item/deduction.
2. Same cart/key concurrent: exactly one header, one set of lines/movements; both receive same sale. Repeat after cancellation and after product/customer archive.
3. Same key/different payload: one operation commits; other conflicts, no extra writes.
4. Multi-product carts opposite input order: both services lock ascending product IDs. Assert no oversell; contention/deadlock retry never duplicates rows.
5. Inject failures after header, item, stock update and movement insertion: each failed attempt leaves counts, balance and ledger unchanged.
6. Cancellation two managers: one restoration per item, unchanged first cancellation attribution. Concurrent restock still yields correct sum.
7. Cancel archived zero-stock product sale: restoration may leave archived positive stock; it appears in all-inventory view and can be corrected/restored. Ceiling failure rolls back every line and header.
8. Manual key retry after later stock changes: returns original movement, doesn't require old version to still match. Changed payload conflicts.
9. Sale vs customer archive: whichever obtains customer lock first defines valid order; no newly created sale to an already archived customer. Existing-key retries remain readable.
10. Product metadata edit vs sale/restock: stale version cannot overwrite stock; snapshots reflect locked facts.
11. Price 0.00 and upper bound; reject 1.001, scientific notation, negative values. Same-price distinct lines both count.
12. Direct SQL constraints in disposable test DB: negative stock, orphan product/item, mismatched movement product, duplicate movement reason, invalid cancellation metadata reject. Explicitly demonstrate that an FK alone does not reject an empty sale header.

Deadlock/lock-timeout simulation must assert whole-transaction rollback and bounded retry; engine errors can have different statement/transaction rollback semantics. Preserve database error codes in safe test logs.

## Fixtures and expected results

Use [database seed specification](DATABASE_DESIGN.md): 4 products, 2 customers, 2 sales, 3 items, 7 movements. Final stock [8,4,2,0]; completed sales value 70.00. Add independent adversarial fixtures; do not derive expected values using the same query under test.

Date test: Dhaka 10 September 2026 maps to UTC range [2026-09-09 18:00:00, 2026-09-10 18:00:00). Include just-before/start/end records. Cancellation removes the original sale from the retrospective completed-value report but remains visible as a later movement.

Reconciliation tests corrupt only an isolated disposable database using an administrative test connection. Normal application permissions must forbid rewriting items/movements. Reconciliation checks run from a consistent snapshot.

## Security/browser details

Framework HTTP tests may bypass CSRF middleware. Include an explicitly enabled middleware test or real browser/direct HTTP request without a token and verify zero writes. Disabling buttons is not authorization evidence. Test session fixation/logout, disabled accounts, forged actor/stock fields, escaped stored text and malicious sort values.

Do not test public exposure using real personal data. For the course demo, prove Apache serves only public/, .env cannot be downloaded, build assets work without CDN access, and DB is not accessed from the browser.

## Performance and release evidence

After fixture generation: record CPU/RAM/OS, PHP/framework/database versions, indexes and EXPLAIN. Run 10 warmups then 100 measured list/report requests with five concurrent clients. Report p50/p95, server-error rate, query count and slowest query. Targets are SRS NFR-04, not promises. Include active filters/date ranges in evidence so timings are reproducible.

Before release: fresh checkout install, migrate/seed, full critical suite, backup restore into a different database, browser walkthrough, dependency audit and documentation link check. CI uses the matching DB family/version; a PostgreSQL or SQLite-only green badge doesn't certify MariaDB behavior.

Record results in `IMPLEMENTATION_STATUS.md` or a dated evidence artifact: date, commit, environment, command, expected/actual, pass/fail, artifact path. No invented screenshots, benchmark numbers or badges.
