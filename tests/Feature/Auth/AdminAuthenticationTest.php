<?php

use App\Models\User;

it('redirects guests to the admin login screen', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');
});

it('allows an admin user to sign in and access the dashboard', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirectToRoute('dashboard.overview');
    $this->assertAuthenticatedAs($admin);
});

it('rejects non-admin users from signing in to the dashboard', function () {
    User::factory()->create([
        'email' => 'member@example.com',
        'password' => 'password',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => 'member@example.com',
        'password' => 'password',
    ]);

    $response
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('forbids authenticated non-admin users from viewing the dashboard', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get('/')
        ->assertForbidden();
});
