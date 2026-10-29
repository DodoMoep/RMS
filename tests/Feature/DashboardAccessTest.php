<?php

use App\Models\User;

it('redirects guests from the dashboard to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('shows the dashboard to a verified user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

it('sends a user with an unverified email address to the verification notice', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/')
        ->assertRedirect(route('verification.notice'));
});
