docker run \
    -e "OPINE_ENV=docker" \
    --rm \
    --link opine-memcached:memcached \
    -v "$(pwd)/../":/app \
    opine:phpunit-cache \
    --bootstrap /app/tests/bootstrap.php