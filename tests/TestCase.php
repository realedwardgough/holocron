<?php

declare(strict_types=1);

namespace Egough\Holocron\Tests;

use Egough\Holocron\HolocronServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HolocronServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('holocron.actor_resolver', static fn () => null);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $migration = require __DIR__.'/../database/migrations/create_holocron_entries_table.php.stub';
        $migration->up();
    }
}
