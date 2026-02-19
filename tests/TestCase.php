<?php

namespace Adnan\LaravelNexus\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Adnan\LaravelNexus\NexusServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Adnan\\LaravelNexus\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            NexusServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('app.key', 'base64:eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHg=');
        config()->set('app.cipher', 'AES-256-CBC');

        $migration = include __DIR__.'/../database/migrations/create_nexus_tables.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_nexus_dlq_table.php.stub';
        $migration->up();

        $migration = include __DIR__.'/../database/migrations/create_nexus_webhook_table.php.stub';
        $migration->up();
    }
}
