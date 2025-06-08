<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get("/", function () {
    return view("welcome");
})->name("home");

Route::view("dashboard", "dashboard")
    ->middleware(["auth", "verified"])
    ->name("dashboard");

Route::middleware(["auth"])->group(function () {
    Route::redirect("settings", "settings/profile");

    Volt::route("settings/profile", "settings.profile")->name(
        "settings.profile"
    );
    Volt::route("settings/password", "settings.password")->name(
        "settings.password"
    );
    Volt::route("settings/appearance", "settings.appearance")->name(
        "settings.appearance"
    );

    Volt::route("songs", "songs.index")->name("songs.index");
    Volt::route("songs/create", "songs.create")->name("songs.create");
    Volt::route("songs/{song}", "songs.show")->name("songs.show");
    Volt::route("songs/{song}/edit", "songs.edit")->name("songs.edit");

    Volt::route("readings", "readings.index")->name("readings.index");
    Volt::route("readings/create", "readings.create")->name("readings.create");
    Volt::route("readings/{reading}", "readings.show")->name("readings.show");
    Volt::route("readings/{reading}/edit", "readings.edit")->name(
        "readings.edit"
    );

    Volt::route("templates", "templates.index")->name("templates.index");
    Volt::route("templates/create", "templates.create")->name(
        "templates.create"
    );
    Volt::route("templates/{template}", "templates.show")->name(
        "templates.show"
    );
    Volt::route("templates/{template}/edit", "templates.edit")->name(
        "templates.edit"
    );
});

require __DIR__ . "/auth.php";
