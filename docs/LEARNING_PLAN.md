# Twelve-week learning and implementation plan

Plan for one primary learner, about 12–15 hours/week; this availability is an assumption. Spend roughly half on course theory/SQL and half on implementation initially. If you have less time, revise scope early. Use the instructor's syllabus and past exams to prioritize academic practice; this project cannot guarantee an A.

## Weekly outcomes

| Week | Course/learning work | Build artifact and readiness gate |
|---|---|---|
| 1 | SQL restart: SELECT, WHERE, NULL, joins, grouping; relational model | Record XAMPP/PHP/engine versions and rubric; draw seven-table ERD from memory; solve 10 short queries unaided |
| 2 | Keys, ER mapping, FDs, closure, normalization | Write schema SQL yourself in a disposable DB; explain each FK/UNIQUE/CHECK; test invalid inserts |
| 3 | PHP syntax, arrays, functions, HTTP/forms; Composer and Laravel lifecycle | Scaffold chosen compatible Laravel; port reviewed schema to migrations; login/logout; one category vertical slice |
| 4 | Joins/subqueries/views; policies and validation | Product/customer CRUD with versions/archival; demonstrate direct-request role rejection |
| 5 | ACID, locks, lost updates; first JS DOM exercises | Restock/correction service and ledger; retries, bounds and reconciliation tests pass |
| 6 | Serializability, isolation, deadlocks; exact decimals | Multi-item sale service, price snapshots, key fingerprint; rollback and last-unit tests pass |
| 7 | Recovery/WAL and transaction failure analysis | Full cancellation, history and receipt; once-only restoration tests pass |
| 8 | Aggregation pitfalls, dates, indexes and EXPLAIN | Reports/search/pagination; calculate fixture results by hand and match SQL; freeze Must scope |
| 9 | Security/views/routines and unseen SQL; JS fetch/errors | Hardening, responsive/accessibility pass; optional React read-only catalog exercise only if core is green |
| 10 | Timed past-paper problems, FD/schedule mock viva | Fresh install/backup restore, performance evidence, bug fixes; no major new features |
| 11 | Explain SQL and code without notes; exam weak areas | GitHub README, ERD/screenshots, demo script, test evidence, dependency/secret checks |
| 12 | Two timed mock exams, oral defense, independent schema reconstruction | Rehearse offline XAMPP demo; tag coursework release; reserve time for course submission fixes |

## How to study each day

Use 30–45 minutes of retrieval practice before opening solutions: write queries, compute closures, or draw a precedence graph. Then implement one small behavior, observe its SQL, and explain the result aloud. Keep a mistake log: incorrect assumption, smallest counterexample, corrected rule, one new exercise.

Treat AI as reviewer/tutor: attempt first, ask for hints, verify against SQL output/textbook, then reproduce without generated code. A working page you cannot explain is not academic mastery.

## Completion gates

- SQL gate: 8/10 unseen questions correct, including LEFT JOIN zero-child cases and NULL traps; explain errors and retry a different set.
- Design gate: identify all candidate keys for a small supplied FD set; prove one lossless decomposition; explain dependency preservation.
- Transaction gate: predict a two-session schedule and reproduce oversell prevention; explain why a UNIQUE constraint alone doesn't create missing rows.
- Web gate: trace browser -> middleware -> controller -> service -> SQL -> response and identify authorization/CSRF checks.
- Release gate: critical tests and clean restore pass; no documented Must feature silently absent.

These self-assessment thresholds support learning; instructor grading may use different criteria.

## Scope cuts and later milestones

If behind at week 6, stop optional CSV/React and visual extras. Finish inventory receipt/sale/cancellation correctness before dashboards. If the Must scope still does not fit, explicitly amend SRS with a smaller course-approved scope; don't label unfinished behavior complete.

After submission: learn PostgreSQL with the same SQL exercises, migrate schema/data and test parity; then add React/Inertia to the stable Laravel backend. Change one major dependency at a time. Multi-warehouse stock, suppliers, partial returns and accounting are separate future designs, not quick checkbox features.

## GitHub presentation

A strong repository needs a truthful feature matrix, setup versions, migration/seed steps, ERD, key transaction explanation, screenshots from the actual app, test commands/results, known limitations, and a short demo. Use meaningful incremental commits. Choose a license deliberately; don't imply third-party assets/code are yours. A polished monolith with reproducible tests is a credible portfolio project without adopting every popular framework.
