<?php

use App\Models\Client;

it('uses bin as the authentication identifier', function () {
    expect((new Client)->getAuthIdentifierName())->toBe('bin');
});
