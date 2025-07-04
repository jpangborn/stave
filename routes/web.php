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

    Route::name("settings.")
        ->prefix("settings")
        ->group(function () {
            Volt::route("profile", "settings.profile")->name("profile");
            Volt::route("password", "settings.password")->name("password");
            Volt::route("appearance", "settings.appearance")->name(
                "appearance"
            );
        });

    Route::name("songs.")
        ->prefix("songs")
        ->group(function () {
            Volt::route("/", "songs.index")->name("index");
            Volt::route("/create", "songs.create")->name("create");
            Volt::route("/{song}", "songs.show")->name("show");
            Volt::route("/{song}/edit", "songs.edit")->name("edit");
        });

    Route::name("readings.")
        ->prefix("readings")
        ->group(function () {
            Volt::route("/", "readings.index")->name("index");
            Volt::route("/create", "readings.create")->name("create");
            Volt::route("/{reading}", "readings.show")->name("show");
            Volt::route("/{reading}/edit", "readings.edit")->name("edit");
        });

    Route::name("templates.")
        ->prefix("templates")
        ->group(function () {
            Volt::route("/", "templates.index")->name("index");
            Volt::route("/create", "templates.create")->name("create");
            Volt::route("/{template}", "templates.show")->name("show");
            Volt::route("/{template}/edit", "templates.edit")->name("edit");
        });

    Route::name("services.")
        ->prefix("services")
        ->group(function () {
            Volt::route("/", "services.index")->name("index");
            Volt::route("/create", "services.create")->name("create");
            Volt::route("/{service}", "services.show")->name("show");
            Volt::route("/{service}/edit", "services.edit")->name("edit");
        });
});

require __DIR__ . "/auth.php";
