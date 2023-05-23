<?php

namespace Gitlabit\GoogleAuth\Tests;

use Gitlabit\GoogleAuth\GoogleAuthServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    protected function getPackageProviders($app)
    {
        return [
            GoogleAuthServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_apiclient_table.php.stub';
        $migration->up();
        */
    }
}
