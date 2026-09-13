# CRM application

Laravel 12.69.2 application for the seven-table CRM inventory domain. PHP dependencies are locked in `composer.lock`; browser dependencies are locked in `package-lock.json`.

```powershell
& 'C:/xampp/php/php.exe' artisan migrate
npm ci
npm run build
& 'C:/xampp/php/php.exe' artisan test
```

Tests refuse any database except the isolated MariaDB `nexastock_test`. Development uses restricted `nexastock_app`; migrations use a separate account. Demo seeding requires `NEXASTOCK_DEMO_PASSWORD` in the process environment and refuses production or a nonempty business schema.

Administrative users are managed without public UI or passwords in shell history:

```powershell
& 'C:/xampp/php/php.exe' artisan user:provision
& 'C:/xampp/php/php.exe' artisan user:password user@example.test
& 'C:/xampp/php/php.exe' artisan user:deactivate user@example.test
```

See the root [setup runbook](../docs/SETUP_AND_OPERATIONS.md), [API handoff](../docs/FRONTEND_INTEGRATION.md), and [implementation evidence](../docs/IMPLEMENTATION_STATUS.md).
