<?php

namespace App\Providers;

use App\Channels\DigestChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use NotificationChannels\WebPush\WebPushChannel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }

        Blaze::optimize()
            ->in(resource_path('views/components'))
            ->in(resource_path('views/components/layouts'), compile: false)
            ->in(resource_path('views/flux/navlist'))
            ->in(resource_path('views/flux/icon'), memo: true);

        if (app()->isLocal()) {
            Blaze::debug();
        }

        Notification::extend('webpush', fn ($app) => $app->make(WebPushChannel::class));
        Notification::extend('digest', fn ($app) => $app->make(DigestChannel::class));
    }
}
