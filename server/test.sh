docker run \
    -e "OPINE_ENV=docker" \
    --rm \
    -v "$(pwd)/../":/app \
    opine:phpunit-cache \
    --bootstrap /app/tests/bootstrap.php
