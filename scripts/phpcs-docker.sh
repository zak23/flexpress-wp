#!/usr/bin/env bash
set -euo pipefail

# Execute PHPCS inside the Docker container as root so we can access global composer bin in /root
CONTAINER_NAME="flexpress_wordpress"
PHPCS_BIN="/root/.composer/vendor/bin/phpcs"

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
  echo "Container ${CONTAINER_NAME} is not running" >&2
  exit 1
fi

# Forward all arguments to phpcs inside the container
exec docker exec -u 0 "${CONTAINER_NAME}" "${PHPCS_BIN}" "$@"


