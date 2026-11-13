# Tatlas

App mobile (Android, poi iOS) per trovare il tatuatore giusto vicino a te: mappa, profili artista e, nella Fase 2, ricerca intelligente per stile, soggetto o immagine. Si parte da Bari.

Monorepo:

| Cartella | Contenuto |
|---|---|
| `backend/` | Laravel 13: API REST `/api/v1` (Sanctum), pannello admin e pagina pubblica artista (Livewire, in arrivo) |
| `mobile/` | App Expo (React Native + TypeScript, Expo Router, TanStack Query) |
| `ai-service/` | Microservizio FastAPI per gli embedding (Fase 2; per ora solo `/health`) |
| `docker/postgres/` | Immagine PostgreSQL 16 + PostGIS 3.4 + pgvector 0.8 |
| `docs/DECISIONS.md` | Decisioni architetturali e motivazioni |

## Requisiti

- Docker e Docker Compose
- Per lavorare fuori dai container: PHP 8.3+ con `pdo_pgsql`, Composer 2, Node 22, Python 3.12+

## Avvio locale

```bash
# 1. Configurazione backend
cp backend/.env.example backend/.env

# 2. Avvio dello stack (postgres, app, worker, ai-service)
docker compose up -d --build

# 3. Dipendenze, chiave, migrazioni e tassonomia stili
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 4. Importa gli studi di tatuaggio da OpenStreetMap (default: Città Metropolitana di Bari)
docker compose exec app php artisan tatlas:import-osm
```

Servizi:

- API: http://localhost:8000 (healthcheck: `/up`)
- AI service: http://localhost:8001/health
- PostgreSQL: `localhost:5432`, db `tatlas` (+ `tatlas_test` per i test), utente `tatlas` / `secret`

### Import da OpenStreetMap

```bash
php artisan tatlas:import-osm                                # bbox di default (OSM_DEFAULT_BBOX)
php artisan tatlas:import-osm --bbox=41.08,16.82,41.16,16.94 # sud,ovest,nord,est
php artisan tatlas:import-osm --dry-run                      # scarica e mappa, non salva nulla
```

Il comando è idempotente: si può rilanciare quando si vuole. Aggiorna gli studi esistenti in base a `osm_type`+`osm_id`, non tocca i campi che il titolare ha modificato (`locked_fields`) e marca con `osm_missing_since` gli studi spariti da OSM senza cancellarli. Usa l'API pubblica di Overpass, quindi non va lanciato più spesso di qualche volta al giorno.

I dati sono © OpenStreetMap contributors, rilasciati con licenza [ODbL](https://www.openstreetmap.org/copyright). L'attribuzione è obbligatoria e deve essere visibile sulla mappa.

## Test e lint

```bash
# Backend: richiede il container postgres (docker compose up -d postgres)
cd backend && vendor/bin/pest && vendor/bin/pint --test

# Mobile
cd mobile && npm ci && npm run lint && npm run typecheck && npm test

# AI service
cd ai-service && python -m venv .venv && .venv/bin/pip install -e ".[dev]"
.venv/bin/pytest && .venv/bin/ruff check . && .venv/bin/ruff format --check .
```

I test del backend girano su un PostgreSQL vero (`tatlas_test`), non su SQLite, perché usano PostGIS e pgvector. La CI (GitHub Actions, `.github/workflows/`) esegue gli stessi comandi per ogni cartella modificata e verifica la build delle immagini Docker.

## App mobile (sviluppo)

L'app usa codice nativo (la mappa MapLibre), quindi **Expo Go non basta**: serve una *development build*.

```bash
cd mobile
npm install
# Emulatore Android o telefono collegato via USB (richiede Android SDK):
npx expo run:android
# poi, per i lanci successivi:
npm start
```

L'URL dell'API si imposta con `EXPO_PUBLIC_API_URL`. Il default `http://10.0.2.2:8000/api/v1` funziona sull'emulatore Android. Su un telefono fisico usa l'IP del PC nella rete locale, per esempio `EXPO_PUBLIC_API_URL=http://192.168.1.20:8000/api/v1 npm start`.

La guida completa alla build EAS installabile sul telefono arriverà con il task G1.

## Stato

Vedi il piano della Fase 1 in `docs/DECISIONS.md` (sezione Roadmap). Fatto finora: setup del monorepo (A1–A6), modello dati (B1), tassonomia stili (B2), import OSM (B3–B4).
