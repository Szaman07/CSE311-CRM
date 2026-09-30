<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class DatabaseSafety
{
    public static function assertSafe(Application $app): void
    {
        $manager = $app->make('db');
        $connection = $manager->connection();
        self::assertConfiguration(
            $app->environment(),
            $manager->getDefaultConnection(),
            $connection->getConfig('driver'),
            $connection->getDatabaseName(),
        );
        // Only connect after checking the isolated target, and verify the actual engine
        // before RefreshDatabase can run migrations or remove tables.
        self::assertServer((string) $connection->selectOne('SELECT VERSION() AS version')->version);
    }

    public static function assertConfiguration(string $environment, string $connection, string $driver, string $database): void
    {
        if ($environment !== 'testing' || $connection !== 'mariadb'
            || $driver !== 'mariadb' || $database !== 'nexastock_test') {
            throw new RuntimeException('Tests are locked to APP_ENV=testing and the isolated nexastock_test MariaDB database.');
        }
    }

    public static function assertServer(string $version): void
    {
        if (! str_contains($version, 'MariaDB')) {
            throw new RuntimeException('Tests require an actual MariaDB server, not just a mariadb driver name.');
        }
    }
}
