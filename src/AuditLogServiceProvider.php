<?php

namespace AlwaysOpen\AuditLog;

use Illuminate\Support\ServiceProvider;
use AlwaysOpen\AuditLog\Console\Commands\MakeModelAuditLogTable;

class AuditLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/model-auditlog.php' => config_path('model-auditlog.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/model-auditlog.php', 'model-auditlog');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModelAuditLogTable::class,
            ]);
        }
    }
}
