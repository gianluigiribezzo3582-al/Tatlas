#!/usr/bin/env bash
# Local setup in one command (Linux / macOS / WSL).
# Usage: scripts/dev-setup.sh [PC_IP] [--no-demo]
#   PC_IP: your computer's LAN address (e.g. 192.168.1.20), used in links sent to the phone.
set -euo pipefail
cd "$(dirname "$0")/.."

IP="${1:-}"
DEMO=1
[[ "${2:-}" == "--no-demo" || "${1:-}" == "--no-demo" ]] && DEMO=0
[[ "$IP" == "--no-demo" ]] && IP=""

export UID GID="$(id -g)"

[[ -f backend/.env ]] || cp backend/.env.example backend/.env
if [[ -n "$IP" ]]; then
  sed -i.bak "s#^APP_URL=.*#APP_URL=http://$IP:8000#" backend/.env && rm -f backend/.env.bak
fi

echo "▶ Building and starting containers (first run takes a few minutes)…"
docker compose up -d --build

run() { docker compose exec -T app "$@"; }
echo "▶ Installing PHP dependencies…"
run composer install --no-interaction
grep -q '^APP_KEY=base64' backend/.env || run php artisan key:generate --force
echo "▶ Database and style taxonomy…"
run php artisan migrate --seed --force
run php artisan storage:link 2>/dev/null || true
echo "▶ Importing tattoo studios from OpenStreetMap (Bari area)…"
run php artisan tatlas:import-osm || echo "⚠ OSM import failed (Overpass busy?). Retry later: docker compose exec app php artisan tatlas:import-osm"
if [[ "$DEMO" == 1 ]]; then
  echo "▶ Fictional demo artists (remove with: docker compose exec app php artisan tatlas:demo-data --purge)…"
  run php artisan tatlas:demo-data
fi

cat <<MSG

✅ Ready.
   API:          http://${IP:-localhost}:8000/api/v1/styles
   Admin panel:  http://localhost:8000/admin  (create an admin:
                 docker compose exec app php artisan tatlas:make-admin you@example.com --password=...)
   Emails:       http://localhost:8025
   In the app:   ⚙︎ → turn off demo mode → server ${IP:-<PC_IP>}:8000 → Save and test
MSG
