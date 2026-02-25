#!/bin/sh
set -e

if [ "$1" = "frankenphp" ]; then
    echo "Waiting for database and running migrations..."
    attempt=0
    max_attempts=30
    while [ $attempt -lt $max_attempts ]; do
        if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1; then
            echo "Migrations completed successfully."
            break
        fi
        attempt=$((attempt + 1))
        if [ $attempt -eq $max_attempts ]; then
            echo "ERROR: Migrations failed after $max_attempts attempts. Exiting."
            exit 1
        fi
        echo "Migration attempt $attempt/$max_attempts failed. Retrying in 3s..."
        sleep 3
    done
fi

exec "$@"
