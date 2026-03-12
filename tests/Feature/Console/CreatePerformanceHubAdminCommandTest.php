<?php

use App\Models\User;

it('creates or promotes an admin user from the console', function () {
    $this->artisan('performance-hub:create-admin admin@example.com password --name="Ops Admin"')
        ->expectsOutputToContain('admin@example.com')
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($user->name)->toBe('Ops Admin')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});
