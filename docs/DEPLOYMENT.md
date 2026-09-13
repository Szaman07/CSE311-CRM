# Deployment preparation

Status: **configuration prepared, no remote target deployed or verified**. XAMPP is the measured local course platform. A first public target should be one Linux application instance with a currently supported PHP release and a supported InnoDB-compatible database release. Re-run the MariaDB tests against that exact engine; XAMPP 8.2.12's bundled PHP 8.2.12 and MariaDB 10.4.32 are unsuitable as an assumed long-lived public production baseline.

## Release boundary

Serve only `inventory/public`. Keep the repository, `.env`, storage logs, database dumps, old `app/`, old `sql/`, phpMyAdmin, and XAMPP administration pages outside public routing. The examples are `deploy/apache/nexastock-vhost.conf.example` for local Apache and `deploy/nginx/nexastock.conf.example` for a future Linux host.

Production needs `APP_ENV=production`, `APP_DEBUG=false`, a stable environment-managed `APP_KEY`, the canonical HTTPS `APP_URL`, secure/HTTP-only cookies, trusted proxy settings, private database networking, and separate migration/runtime database accounts. Install locked PHP dependencies with `composer install --no-dev --classmap-authoritative`; install locked JS dependencies with `npm ci`, then `npm run build`. Only `storage/` and `bootstrap/cache/` need web-process write access; never use mode 777.

The runtime account needs SELECT on the schema, INSERT/UPDATE on users/categories/products/customers/sales, and INSERT only on sale_items/stock_movements. It needs no DELETE or DDL. Migrations run with the separate migration account and are never executed automatically at web startup.

## Release sequence

1. Verify a recent restorable backup and maintenance plan. Preserve `APP_KEY` and secret configuration.
2. Put the instance in maintenance mode, install the reviewed commit and locked dependencies, and build assets.
3. Run `php artisan migrate --force` with temporary migration credentials. Prefer forward fixes; an arbitrary `migrate:rollback` is not a recovery promise.
4. Restore restricted runtime credentials, run `config:cache`, `route:cache`, and `view:cache`, then restart PHP workers.
5. Smoke-test anonymous login redirect, login, product list, one isolated reversible workflow, reports, built asset responses, and reconciliation. Leave maintenance mode.
6. Watch application/web/database logs and 5xx rates. Rotate logs and alert on repeated failed reconciliation. Do not log passwords, cookies, CSRF tokens, or customer records.

A shallow liveness check can confirm the process serves the login page. Readiness should be an authenticated/privately monitored query that verifies database access without returning engine details. CRM deliberately exposes no public diagnostic `/up` endpoint.

File sessions and cache require persistent local writable storage and one application instance. Before adding replicas, move sessions/cache to a supported shared store and retest expiry, CSRF, idempotency, and failure handling. No queue or scheduled job is currently required.

## Backup and recovery

`deploy/backup.ps1` uses `mysqldump --single-transaction` and prompts for its credential; keep dumps encrypted/private and off-host with a retention policy. `deploy/restore-verify.ps1` refuses ordinary database names and restores only into an explicitly prepared `nexastock_restore_*` database. Inspect the dump, restore in isolation, run counts/reconciliation/login checks, record duration, and delete the drill database only after review. Schema changes must not overlap the logical backup.

A PostgreSQL move is a later engineering project: write PostgreSQL migrations, export/transform/import data, validate IDs/decimals/timestamps/constraints/indexes, and rerun concurrency, privilege, report, and reconciliation tests. Changing `DB_CONNECTION` is insufficient.
