# Project audit — 30 September 2026

CRM is a small coursework application with a useful database core: transactional stock changes, exact prices, an append-only ledger, and a shared service layer for Blade and the API. Keep that scope while learning SQL and preparing the submission. The current evidence supports a working local project; it does not establish production readiness or guarantee a grade.

## Fixes

| Finding | Change |
|---|---|
| Test safety checks ran after database setup | Guard effective environment, driver and database before migrations; catch `DB_URL` overrides and verify the actual MariaDB server. Apply the guard to subprocess workers too. |
| Category edits lost the submitted version | Validate and pass `expected_version`; test successful edits and missing/stale versions through API and Blade. |
| Integer fields could be silently truncated or accept inappropriate values | Share strict whole-number validation across IDs, versions, quantities and pagination; independently validate service cart input before casts. |
| Unknown write fields were silently ignored | Reject unknown top-level and cart-row fields, while accepting Blade transport metadata. |
| Malformed queries/dates caused server errors | Validate query shape, allowed filters and effective date ranges after defaults; return field errors. |
| Renames/repricing split top-product totals | Group by product identity, display current labels, sum snapshot prices and retain original receipt snapshots. |
| Report API types differed from the contract | Serialize IDs, versions and unbounded aggregates as strings and dates in ISO UTC form. |
| Archived categories blocked edits to existing products | Allow metadata edits in the existing category; reject reassignment to another archived category. |
| API login/logout could redirect to HTML | Return JSON for API paths even without an `Accept` header. Validate login bounds and reject unknown fields. |
| Password resets left existing sessions usable | Check the password hash on protected requests, including the first request after login. |
| Exhausted contention returned a generic failure; exception logs could contain SQL bindings | Return a retryable conflict and log only request ID and engine error codes. |
| Sale form had only three fixed rows and discarded failed carts | Add/remove up to 100 rows, preserve failed submissions, regenerate keys after edits, keep contiguous input names and restore focus. Explicitly accept changed prices through “Use current prices,” preserving unchanged receipt retries. |
| Failed stock forms lost input and correction versions | Preserve only the submitted operation and its original retry fields; provide a reset link for a different operation or fresh version. |
| Malformed flashed email input broke the login page | Render only a string email value. |
| Documentation/spec checks missed or misrepresented behavior | Fix closed edit schemas, numeric bounds and response examples; handle Markdown paths containing parentheses; remove duplicate README text; add documentation checks to CI. |

No schema changes were needed. The migrations and synchronized SQL reference retain the same seven business tables. The root prototype, existing study-guide work and UI redesign plans were preserved.

## Verification

- PHPUnit: **47 tests, 263 assertions passed** on XAMPP PHP 8.2.12 / MariaDB 10.4.32, using only `APP_ENV=testing`, `mariadb`, `nexastock_test`.
- Four coordinated subprocess races: last unit, same-key sale, concurrent cancellation, same-key receipt; all assert zero reconciliation drift.
- Additional regressions cover enabled CSRF denial, injected transaction rollback, independent report totals/date boundaries, actor spoofing, CSV formula neutralization, session revocation and safe database-error responses.
- Pint, Blade compilation, Vite build and Redocly OpenAPI lint passed. Composer/npm audits reported no known vulnerabilities at the time checked.
- Documentation and offline learning-guide checks passed. Existing generated learning material was verified, not rewritten as part of this application audit.
- Local synthetic cart preview: four-row entry, keyboard Add/price-review activation, selection, quantity editing, middle-row removal, contiguous names and focus checked; 100-row Add limit and recovery after removal passed. Layouts inspected at 360px and 1280px. No live business records were used and no screenshots were committed.

Exact regression mappings are in [TEST_PLAN.md](TEST_PLAN.md); broader status is in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Next work

1. Run the backup/restore drill and the supplied performance fixture/workload; record results rather than claiming targets were met.
2. Add coordinated archive races and actual retry-exhaustion injection; expand rollback injection to the remaining transaction checkpoints.
3. Complete the keyboard walkthrough when redesigning the UI. The cart preview does not certify the whole application.
4. Confirm the instructor's rubric and defend the schema, normalization, joins, constraints, isolation, locking and idempotency independently.
5. Consider hosting, PostgreSQL, React and integrations after the semester core is explainable and verified. Keep CV claims tied to completed features and measured results.

The application is published in [CSE311-CRM](https://github.com/Szaman07/CSE311-CRM). Project learning guides are published separately on [nsu-courses/cse311_project](https://github.com/Szaman07/nsu-courses/tree/cse311_project). Existing unrelated workspace changes were preserved.
