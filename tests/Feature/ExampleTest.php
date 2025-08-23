<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful response', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
});
