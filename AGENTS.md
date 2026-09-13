# Repository working rules

- Treat `docs/SRS.md` as behavior authority and `inventory/database/migrations` as executable schema authority. Keep `docs/schema/inventory_reference.mysql.sql` synchronized.
- Work on CRM only under `inventory/`. The root `app/` and `sql/` are preserved prototype comparison material; never import their destructive SQL into an inventory database.
- Run tests only when `APP_ENV=testing`, connection is MariaDB, and database is exactly `nexastock_test`; `tests/TestCase.php` enforces this.
- Never expose `.env`, credentials, personal data, logs, backups, or session data. Never add business DELETE endpoints or direct stock setters.
- Blade and `/api/v1` must call the same domain services. Keep money as decimal strings, actors server-resolved, mutations transactional, histories append-only, and product locks in ascending ID order.
- New behavior requires a mapped test and documentation update. Do not weaken concurrency, constraint, or reconciliation assertions to make a run green.
