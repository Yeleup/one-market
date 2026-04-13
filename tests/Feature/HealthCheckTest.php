<?php

test('the health route reports the application is up', function () {
    $this->get('/up')->assertSuccessful();
});
