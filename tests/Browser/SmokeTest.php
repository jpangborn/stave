<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders key pages without smoke', function (): void {
    $routes = ['/', '/login', '/register'];

    $this->visit($routes)->assertNoSmoke();
});
