<?php

declare(strict_types=1);

namespace Egough\Holocron;

use Egough\Holocron\Services\HistoryRecorder;
use Illuminate\Support\ServiceProvider;

class HolocronServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/holocron.php', 'holocron');

        $this->app->singleton(HistoryRecorder::class, fn (): HistoryRecorder => new HistoryRecorder);
        $this->app->singleton(Holocron::class, fn ($app): Holocron => new Holocron($app->make(HistoryRecorder::class)));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/holocron.php' => config_path('holocron.php'),
        ], 'holocron-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_holocron_entries_table.php.stub' => $this->migrationFilePath(),
        ], 'holocron-migrations');
    }

    protected function migrationFilePath(): string
    {
        return database_path('migrations/'.date('Y_m_d_His').'_create_holocron_entries_table.php');
    }
}
