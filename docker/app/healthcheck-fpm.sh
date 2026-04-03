#!/bin/sh
set -eu

response="$(
    SCRIPT_NAME=/fpm-ping \
    SCRIPT_FILENAME=/fpm-ping \
    REQUEST_METHOD=GET \
    REQUEST_URI=/fpm-ping \
    SERVER_PROTOCOL=HTTP/1.1 \
    cgi-fcgi -bind -connect 127.0.0.1:9000 2>/dev/null
)"

printf '%s' "$response" | grep -q '^Content-type: text/plain'
printf '%s' "$response" | grep -q 'pong'
