<?php

it('renders demo seed guidance on an empty dashboard', function () {
    $response = $this->get('/');

    $response
        ->assertSuccessful()
        ->assertSeeText('Need a demo workspace?')
        ->assertSeeText('performance-hub:seed-demo');
});
