# 10. PostgreSQL after the XAMPP release

Deferred learning milestone; do not run both engines for the initial submission. Start from a tagged, tested Laravel/XAMPP release. Study the [PostgreSQL tutorial](https://www.postgresql.org/docs/current/tutorial.html), then inspect the chosen version's type, constraint and isolation documentation.

## Migration checklist

| Concern | Work required |
|---|---|
| Identity | AUTO_INCREMENT -> generated identity; map unsigned BIGINT into signed bigint range or redesign before import |
| Strings/collation | Recreate explicit SKU/email/category normalization and uniqueness; compare real Unicode/case fixtures |
| Booleans | TINYINT active flag -> boolean with correct casts |
| Money | DECIMAL -> numeric with same precision, validation and decimal-string output |
| Time | Prefer timestamptz for instants; import old DATETIME as explicitly UTC, not server-local time |
| Enums/checks | Retain explicit checks or deliberately selected enum types; verify cancellation/null semantics |
| Indexes/FKs/views | Translate syntax, retain composite item/product FK and uniqueness; inspect query plans |
| SQL functions | Replace engine-specific date/format/upsert idioms; don't assume Query Builder translates raw SQL |
| Transactions | Test new default isolation and FOR UPDATE behavior; classify serialization/deadlock errors and retry whole transactions |
| Privileges | Recreate least-privilege roles and view access; database migration doesn't supply app authorization |
| Framework | Update connection/config, migrations and casts; preserve services and HTTP behavior |

PDO is an access interface, not a SQL dialect translator. Read [PostgreSQL isolation](https://www.postgresql.org/docs/current/transaction-iso.html) and [constraints](https://www.postgresql.org/docs/current/ddl-constraints.html) before claiming parity.

## Data migration procedure

1. Back up original and prove a restore; freeze writes for this small migration exercise.
2. Export consistent synthetic data with explicit column lists and UTF-8 encoding.
3. Create target schema in separate PostgreSQL database; import parent tables before children.
4. Preserve IDs and set identity sequences above imported maxima.
5. Compare row counts, key sets, exact money totals, timestamps, snapshots and ledger balances.
6. Run the same bad-input, concurrency, retry and cancellation cases; investigate any behavior differences.
7. Re-run EXPLAIN/performance checks rather than carrying over index claims.
8. Keep original release/data available for rollback; switch only after parity evidence.

Don't convert through CSV numeric cells that lose ID/money precision. Treat hash/password strings as opaque. For a personal learning demo there is no need for zero-downtime dual writes.

## Exercise

Explain why a database migration passing row-count checks could still be wrong. Answers include timezone shifts, collation uniqueness differences, truncated IDs, altered numeric values, sequence collisions, different isolation outcomes and missing grants.

If a managed provider is chosen later, write a separate hosting/auth/access decision. That choice is not necessary to learn PostgreSQL itself.
