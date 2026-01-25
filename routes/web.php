<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::name('settings.')
        ->prefix('settings')
        ->group(function (): void {
            Route::livewire('profile', 'pages::settings.profile')->name('profile');
            Route::livewire('password', 'pages::settings.password')->name('password');
            Route::livewire('appearance', 'pages::settings.appearance')->name('appearance');
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

    Route::name('people.')
        ->prefix('people')
        ->group(function (): void {
            Route::livewire('/', 'pages::people.index')->name('index');
            Route::livewire('/create', 'pages::people.create')->name('create');
            Route::livewire('/{person}', 'pages::people.show')->name('show');
            Route::livewire('/{person}/edit', 'pages::people.edit')->name('edit');
        });
});

require __DIR__.'/auth.php';
