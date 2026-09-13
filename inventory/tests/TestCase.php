<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')
            || config('database.default') !== 'mariadb'
            || config('database.connections.mariadb.database') !== 'nexastock_test') {
            throw new \RuntimeException('Tests are locked to the isolated nexastock_test MariaDB database.');
        }
    }
}
