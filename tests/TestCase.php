<?php

namespace Jaulz\Limax\Tests;

use Jaulz\Limax\LimaxServiceProvider;
use Tpetry\PostgresqlEnhanced\PostgresqlEnhancedServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LimaxServiceProvider::class,
            PostgresqlEnhancedServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app) {
    }
}