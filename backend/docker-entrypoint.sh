#!/bin/sh
set -eu

attempt=1
max_attempts=30

while [ "$attempt" -le "$max_attempts" ]; do
    if php artisan migrate --force; then
        php artisan storage:link --force || true
        exec "$@"
    fi

    echo "Database belum siap; migration akan dicoba lagi ($attempt/$max_attempts)." >&2
    attempt=$((attempt + 1))
    sleep 2
done

echo "Database tidak siap setelah $max_attempts percobaan; container dihentikan." >&2
exit 1
