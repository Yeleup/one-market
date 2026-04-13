#!/bin/sh
set -eu

php <<'PHP'
<?php

$context = stream_context_create([
    'http' => [
        'ignore_errors' => true,
        'timeout' => 3,
    ],
]);

@file_get_contents('http://127.0.0.1:8000/up', false, $context);

$statusLine = $http_response_header[0] ?? '';

if (str_contains($statusLine, '200')) {
    exit(0);
}

fwrite(STDERR, "Health check failed: {$statusLine}\n");
exit(1);
PHP
