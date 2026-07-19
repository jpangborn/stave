<?php

use App\Models\Church;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/bulletin', function () {
    $church = auth()->user()?->currentChurch;

    return $church instanceof Church
        ? redirect()->route('bulletin.current', $church)
        : redirect()->route('home');
})->name('bulletin.index');

Route::livewire('/bulletin/{church:slug}', 'pages::bulletin.show')->name('bulletin.current');
Route::livewire('/bulletin/{church:slug}/{service}', 'pages::bulletin.show')->name('bulletin.show')->scopeBindings();

Route::livewire('/invitations/{token}/accept', 'pages::invitations.accept')->name('invitations.accept');

Route::livewire('/join/{token}', 'pages::churches.join')->name('churches.join');

Route::livewire('dashboard', 'pages::dashboard.index')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function (): void {
    Route::livewire('churches/create', 'pages::churches.create')->name('churches.create');

    Route::redirect('settings', 'settings/profile');

    Route::name('settings.')
        ->prefix('settings')
        ->group(function (): void {
            Route::livewire('profile', 'pages::settings.profile')->name('profile');
            Route::livewire('password', 'pages::settings.password')->name('password');
            Route::livewire('appearance', 'pages::settings.appearance')->name('appearance');
            Route::livewire('notifications', 'pages::settings.notifications')->name('notifications');
            Route::livewire('church', 'pages::settings.church')->name('church');
            Route::livewire('church/invitations', 'pages::settings.church-invitations')->name('church-invitations');
        });

    Route::name('songs.')
        ->prefix('songs')
        ->group(function (): void {
            Route::livewire('/', 'pages::songs.index')->name('index');
            Route::livewire('/create', 'pages::songs.create')->name('create');
            Route::livewire('/{song}', 'pages::songs.show')->name('show');
            Route::livewire('/{song}/edit', 'pages::songs.edit')->name('edit');
        });

    Route::name('readings.')
        ->prefix('readings')
        ->group(function (): void {
            Route::livewire('/', 'pages::readings.index')->name('index');
            Route::livewire('/create', 'pages::readings.create')->name('create');
            Route::livewire('/{reading}', 'pages::readings.show')->name('show');
            Route::livewire('/{reading}/edit', 'pages::readings.edit')->name('edit');
        });

    Route::name('series.')
        ->prefix('series')
        ->group(function (): void {
            Route::livewire('/', 'pages::series.index')->name('index');
            Route::livewire('/create', 'pages::series.create')->name('create');
            Route::livewire('/{series}', 'pages::series.show')->name('show');
            Route::livewire('/{series}/edit', 'pages::series.edit')->name('edit');
        });

    Route::name('templates.')
        ->prefix('templates')
        ->group(function (): void {
            Route::livewire('/', 'pages::templates.index')->name('index');
            Route::livewire('/create', 'pages::templates.create')->name('create');
            Route::livewire('/{template}', 'pages::templates.show')->name('show');
            Route::livewire('/{template}/edit', 'pages::templates.edit')->name('edit');
        });

    Route::name('services.')
        ->prefix('services')
        ->group(function (): void {
            Route::livewire('/', 'pages::services.index')->name('index');
            Route::livewire('/create', 'pages::services.create')->name('create');
            Route::livewire('/{service}', 'pages::services.show')->name('show');
            Route::livewire('/{service}/edit', 'pages::services.edit')->name('edit');
        });

    Route::name('groups.')
        ->prefix('groups')
        ->group(function (): void {
            Route::livewire('/', 'pages::groups.index')->name('index');
            Route::livewire('/create', 'pages::groups.create')->name('create');
            Route::livewire('/{group}', 'pages::groups.show')->name('show');
            Route::livewire('/{group}/edit', 'pages::groups.edit')->name('edit');

            Route::name('conversations.')
                ->prefix('/{group}/conversations')
                ->group(function (): void {
                    Route::livewire('/create', 'pages::groups.conversations.create')->name('create');
                    Route::livewire('/{conversation}', 'pages::groups.conversations.show')->name('show');
                });
        });

    Route::livewire('/people', 'pages::people.index')->name('people.index');

    Route::livewire('/people/{person}', 'pages::people.show')->name('people.show');

    Route::livewire('/pastoral-care', 'pages::pastoral-care.index')->name('pastoral-care.index');

    Route::livewire('/prayer-schedule', 'pages::prayer-schedule.index')->name('prayer-schedule.index');

    Route::livewire('/households', 'pages::households.index')->name('households.index');

    Route::name('messages.')
        ->prefix('messages')
        ->group(function (): void {
            Route::livewire('/', 'pages::messages.index')->name('index');
            Route::livewire('/{conversation}', 'pages::messages.index')->name('show');
        });
});

require __DIR__.'/auth.php';
