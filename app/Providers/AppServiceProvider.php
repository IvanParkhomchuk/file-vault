<?php

namespace App\Providers;

use App\Services\DeletionNotificationPublisher;
use App\Services\RabbitMqDeletionNotificationPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DeletionNotificationPublisher::class, RabbitMqDeletionNotificationPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
