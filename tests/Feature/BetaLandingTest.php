<?php

it('shows the private-beta landing page at the root', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('One calm place to hold the whole service together.')
        ->assertSee('Private Beta')
        ->assertSee('Liturgy Management')
        ->assertSee('Pastoral Care')
        ->assertSee('Congregation Messaging');
});

it('points every sign-in call to action at the local login route', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('login'))
        ->assertDontSee('steve.pangborn.cloud');
});
