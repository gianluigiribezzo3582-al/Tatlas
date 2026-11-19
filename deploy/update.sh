#!/usr/bin/env bash
# Pulls the deployment branch and rebuilds only when something changed.
set -euo pipefail
cd /opt/tatlas
BRANCH="${BRANCH:-ccr-5fc7a7c3-55si3b}"

git fetch --quiet origin "$BRANCH"
if [[ "$(git rev-parse HEAD)" == "$(git rev-parse "origin/$BRANCH")" ]]; then
  exit 0
fi

echo "Updating $(git rev-parse --short HEAD) -> $(git rev-parse --short "origin/$BRANCH")"
git reset --hard "origin/$BRANCH"     # deploy/.env is untracked and survives
cd deploy
docker compose -f docker-compose.prod.yml up -d --build --remove-orphans
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec -T app php artisan db:seed --class=StyleSeeder --force
docker image prune -f >/dev/null
