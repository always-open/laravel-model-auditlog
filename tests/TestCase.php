<?php

namespace AlwaysOpen\AuditLog\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use AlwaysOpen\AuditLog\AuditLogServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * SetUp.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/Fakes/migrations/');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AuditLogServiceProvider::class,
        ];
    }
}
