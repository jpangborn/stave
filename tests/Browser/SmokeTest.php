<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders guest pages without smoke', function (): void {
    $routes = ['/', '/login', '/register'];

    $this->visit($routes)->assertNoSmoke();
});
