<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders guest pages without smoke', function (): void {
    $routes = ['/', '/login', '/register'];

    $this->visit($routes)->assertNoSmoke();
});
