<?php

use App\Models\User;

it('renders demo seed guidance on an empty dashboard', function () {
    $response = $this
        ->actingAs(User::factory()->admin()->create())
        ->get('/');

    $response
        ->assertSuccessful()
        ->assertSeeText('Need a demo workspace?')
        ->assertSeeText('performance-hub:seed-demo');
});
