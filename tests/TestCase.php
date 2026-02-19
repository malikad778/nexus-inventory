<?php

namespace Adnan\LaravelNexus\Tests;

use Adnan\LaravelNexus\NexusServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * The latest response.
     *
     * @var \Illuminate\Testing\TestResponse|null
     */
    public static $latestResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Adnan\\LaravelNexus\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            NexusServiceProvider::class,
        ];

        if (class_exists(\Livewire\LivewireServiceProvider::class)) {
            $providers[] = \Livewire\LivewireServiceProvider::class;
        }

        return $providers;
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
