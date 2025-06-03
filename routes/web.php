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
    Volt::route("songs/{song}", "songs.show")->name("songs.show");
    Volt::route("songs/{song}/edit", "songs.edit")->name("songs.edit");
    Volt::route("songs/create", "songs.create")->name("songs.create");
});

require __DIR__ . "/auth.php";
