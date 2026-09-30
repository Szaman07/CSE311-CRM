<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\DatabaseSafety;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // This runs before RefreshDatabase or any other database setup trait.
        DatabaseSafety::assertSafe($app);

        return $app;
    }
}
