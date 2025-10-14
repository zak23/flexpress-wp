#!/usr/bin/env bash
set -euo pipefail

# Execute PHPCBF inside the Docker container as root so we can access global composer bin in /root
CONTAINER_NAME="flexpress_wordpress"
PHPCBF_BIN="/root/.composer/vendor/bin/phpcbf"

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
  echo "Container ${CONTAINER_NAME} is not running" >&2
  exit 1
fi

exec docker exec -u 0 "${CONTAINER_NAME}" "${PHPCBF_BIN}" "$@"


