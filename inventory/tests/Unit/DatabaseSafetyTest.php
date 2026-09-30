<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\DatabaseSafety;

final class DatabaseSafetyTest extends TestCase
{
    public function test_only_the_isolated_mariadb_test_configuration_is_allowed(): void
    {
        DatabaseSafety::assertConfiguration('testing', 'mariadb', 'mariadb', 'nexastock_test');
        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeConfigurations')]
    public function test_unsafe_configuration_is_rejected_before_database_setup(string $environment, string $connection, string $driver, string $database): void
    {
        $this->expectException(RuntimeException::class);
        DatabaseSafety::assertConfiguration($environment, $connection, $driver, $database);
    }

    public static function unsafeConfigurations(): array
    {
        return [
            ['local', 'mariadb', 'mariadb', 'nexastock_test'],
            ['testing', 'mysql', 'mysql', 'nexastock_test'],
            ['testing', 'mariadb', 'mysql', 'nexastock_test'],
            ['testing', 'mariadb', 'mariadb', 'nexastock_dev'],
        ];
    }

    public function test_database_url_override_cannot_bypass_the_guard(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', 'testing');
        $app->instance('config', new Repository(['database' => [
            'default' => 'mariadb',
            'connections' => ['mariadb' => [
                'driver' => 'mariadb', 'database' => 'nexastock_test',
                'url' => 'mariadb://unused@127.0.0.1/nexastock_dev',
            ]],
        ]]));
        $app->instance('db', new DatabaseManager($app, new ConnectionFactory($app)));
        $this->expectException(RuntimeException::class);
        // Building the lazy connection configuration performs no SQL.
        DatabaseSafety::assertSafe($app);
    }

    public function test_actual_mariadb_server_is_allowed(): void
    {
        DatabaseSafety::assertServer('10.4.32-MariaDB');
        $this->addToAssertionCount(1);
    }

    public function test_mysql_server_is_rejected_even_with_a_mariadb_driver(): void
    {
        $this->expectException(RuntimeException::class);
        DatabaseSafety::assertServer('8.0.45');
    }
}
