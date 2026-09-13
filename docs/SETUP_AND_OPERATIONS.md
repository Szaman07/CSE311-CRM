# Setup, migration and operations runbook

Executed local runbook plus clean-machine instructions. Laravel is implemented under `inventory/` with real lockfiles. Existing root `app/` and `sql/` remain a separate initial prototype; `sql/01_schema.sql` drops its own database and must not be imported for CRM. Instructor approval of Laravel remains an academic acceptance gate.

## 1. Record the actual environment

Use the **installed** XAMPP directory; C:/xampp is only an example, not an observed path.

```powershell
& 'C:/xampp/php/php.exe' --version
& 'C:/xampp/php/php.exe' --ini
& 'C:/xampp/php/php.exe' -m
composer --version
node --version
npm --version
```

Via phpMyAdmin or the installed database client:
```sql
SELECT VERSION(), @@version_comment, @@sql_mode, @@time_zone;
SHOW VARIABLES LIKE '%isolation%';
SHOW ENGINES;
```

Confirm InnoDB, strict mode, PDO_MYSQL, required PHP extensions and Composer compatibility. CLI PHP and Apache PHP must match; inspect Apache's local runtime and remove any diagnostic phpinfo page after checking. Record evidence in your future environment report. Do not assume MySQL 8 from XAMPP branding.

The measured XAMPP PHP 8.2 selected Laravel 12.69.2. Laravel 12 security fixes end 24 February 2027; upgrade before that date. No platform requirement was ignored.

## 2. Install locked application dependencies

```powershell
Set-Location E:/study/Projects/CSE311/inventory
& 'C:/xampp/php/php.exe' -d extension=zip C:/tools/composer/composer.phar install
npm ci
npm run build
```

Copy `.env.example` to ignored `.env`, generate `APP_KEY` once, and supply restricted development credentials. Use `nexastock_dev` plus a distinct disposable `nexastock_test`. The committed config explicitly selects UTC storage/connection, Asia/Dhaka display/ranges, file sessions/cache, 30-minute idle, and eight-hour absolute sessions.

Generate APP_KEY once for each environment; don't regenerate an established environment's key during routine deployment. Keep .env out of Git and commit only safe .env.example placeholders.

## 3. Migrations and seed

Translate the [reference DDL](schema/inventory_reference.mysql.sql) into ordered Laravel migrations, then make migrations the executable authority. Preserve named constraints/FKs/indexes; document any driver-specific raw SQL. Framework scaffolding may include infrastructure tables: inspect deliberately.

Use non-destructive migrate for development/release; review SQL before applying. migrate:fresh drops tables and is permitted only for an explicitly disposable test database, never the course database with work you need. Do not auto-run destructive migration commands at application startup.

Seed synthetic users/categories/products/customers, then opening stock/sales/cancellation through domain services as specified. Use local demo credentials supplied outside tracked files. On an existing DB, seeders must refuse duplicate demonstration setup or be deliberately idempotent; don't reset live state to make a rerun appear successful.

Standard commands inside `inventory/` are:
```powershell
composer install
php artisan migrate
php artisan db:seed
npm ci
npm run build
php artisan test
```
These commands are implemented and were executed locally. Seed only an empty local database by setting `NEXASTOCK_DEMO_PASSWORD` outside tracked files. Migration uses separate elevated DB credentials; runtime uses restricted credentials.

## 4. Apache local configuration

The ready vhost is `deploy/apache/nexastock-vhost.conf.example` and listens locally on `127.0.0.1:8080`. Its DocumentRoot is the absolute `inventory/public` path. Include it from XAMPP Apache configuration, verify `mod_rewrite`, then restart Apache. Apache must never serve the repository root, `.env`, storage logs, or database exports.

Use XAMPP Apache for course demonstration; php artisan serve can be a development diagnostic, not evidence that the Apache setup works. Keep database/phpMyAdmin local. Build assets for an offline demo so a CDN outage doesn't break styling.

## 5. Privileges and backups

Use a dedicated runtime DB user restricted to this schema: SELECT/INSERT/UPDATE on mutable business tables; SELECT/INSERT only on sale_items and stock_movements; SELECT on the two invoker views. No runtime DDL/DELETE. Validate all required reads/joins still work. Migration/admin credentials remain separate.

Backup example (adjust client path/name and DB after measurement):
```powershell
& 'C:/xampp/mysql/bin/mysqldump.exe' --host=127.0.0.1 --user=nexastock_backup --password --single-transaction --routines --triggers --result-file='E:/study/Projects/CSE311/nexastock-backup.sql' nexastock_dev
```

Password prompt avoids placing it in command history. Grant only required backup privileges, store backups outside served/tracked directories, and exclude the example dump filename from Git before use. Do not perform concurrent schema changes during backup. --single-transaction assumes transactional tables; verify the dump tool is compatible with the actual server.

Restore through the database client's SOURCE command into a **different explicitly created empty database**, using a dump without source-database switching statements. Inspect the dump first. Verify counts, exact totals, ledger reconciliation and login; record restore duration and result. A backup that has never restored isn't evidence of recoverability.

## 6. Git, CI and future hosting

Initialize Git when implementation begins, review staged paths and exclude .env, vendor, node_modules, logs, sessions, database dumps and actual personal data. Commit lockfiles, migrations, synthetic fixtures and test instructions. CI should install locked dependencies, compile assets, check formatting and run the actual-DB critical suite against the matching family/version.

Public hosting is later: supported PHP/runtime/database, HTTPS, APP_DEBUG=false, restricted secrets/DB access, built assets and tested backups. XAMPP is not the public production package. Do not publish the current unauthenticated prototype.

## First-build checklist

Measured versions and framework permission; disposable DB ready; schema constraints tested; migration authority established; Apache public root checked; login/category vertical slice passing. These concrete gates are enough to start. PostgreSQL and React are later work, not blockers for SQL practice.
