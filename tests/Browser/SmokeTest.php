<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('has not smoke on key pages', function (): void {
    $routes = ['/', '/login', '/register'];

    $this->visit($routes)->assertNoSmoke();
});
