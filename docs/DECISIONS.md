# Decisioni architetturali

Registro delle decisioni (formato ADR breve). Ogni voce riporta il contesto, la decisione e le conseguenze. Le voci superate non si cancellano: si aggiunge una nuova voce che le sostituisce.

---

## ADR-001 · Monorepo con tre progetti indipendenti
**Data:** 2026-11-13
**Decisione:** `backend/` (Laravel), `mobile/` (Expo), `ai-service/` (FastAPI) nello stesso repository. Ogni progetto ha le sue dipendenze e un workflow CI separato, filtrato per path.
**Perché:** un solo team e un'API che evolve insieme all'app. Le modifiche che toccano più parti finiscono in un unico commit o PR.
**Conseguenze:** niente tooling di monorepo (Nx/Turborepo) finché non serve davvero.

## ADR-002 · Laravel 13 al posto di Laravel 11
**Data:** 2026-11-13
**Decisione:** si usa l'ultima versione stabile al momento del setup (Laravel 13.x, PHP ≥ 8.3).
**Perché:** Laravel 11 non riceve più aggiornamenti di sicurezza da marzo 2026. Laravel 13 supporta già nel query builder le colonne `vector` (pgvector), le colonne `geography` e `whereVectorSimilarTo`. Confermato dal product owner.

## ADR-003 · Test del backend su PostgreSQL reale
**Decisione:** Pest gira sul database `tatlas_test` (PostGIS + pgvector), non su SQLite in memoria.
**Perché:** query geografiche, vincoli `CHECK`, indici parziali e vettori non si possono simulare su SQLite. Un test che passa su SQLite e fallisce in produzione è peggio di nessun test.
**Conseguenze:** in locale serve il container `postgres`. In CI l'immagine viene costruita da `docker/postgres`.

## ADR-004 · Immagine Postgres: PostGIS + pgvector copiato dall'immagine ufficiale
**Decisione:** `FROM postgis/postgis:16-3.4` con i file dell'estensione copiati in multi-stage da `pgvector/pgvector:0.8.0-pg16`.
**Perché:** non esiste un'immagine ufficiale con entrambe le estensioni. La copia multi-stage non richiede `apt` durante la build, quindi è veloce, riproducibile e funziona anche dietro proxy restrittivi.
**Conseguenze:** quando si aggiorna una delle due immagini, Postgres deve restare alla stessa major (16). Le estensioni vengono create anche su `template1` e da una migrazione (`Schema::ensureExtensionExists`).

## ADR-005 · Il tatuatore è l'entità principale; la presenza in uno studio è una relazione con date
**Decisione:** la tabella `artist_studio` ha un `id`, un `type` (`resident`|`guest`) e le date `starts_at`/`ends_at`. Lo stesso artista può comparire più volte nello stesso studio (resident e guest, o più guest spot in date diverse). Vincoli a livello DB: un guest ha sempre `starts_at`, e `ends_at >= starts_at`.
**Perché:** riflette come lavorano davvero i tatuatori e rende semplice il filtro "guest spot in arrivo".

## ADR-006 · Import OSM: idempotente, non distruttivo, con campi bloccabili
**Decisione:** chiave naturale `(osm_type, osm_id)`, perché gli id OSM sono unici solo per tipo. A ogni import:
- i campi sincronizzati (nome, posizione, indirizzo, contatti, orari) si aggiornano, tranne quelli elencati in `locked_fields`, cioè modificati dal titolare o da un admin;
- lo slug non cambia mai dopo la creazione, per non rompere i link condivisi;
- gli studi OSM dentro il bbox che non compaiono più ricevono `osm_missing_since`, senza essere cancellati (potrebbero avere un titolare, artisti collegati, una storia);
- gli elementi senza nome o senza coordinate vengono scartati e contati come `skipped`;
- ways e relations usano il centroide (`out center`).

I tag OSM grezzi restano in `osm_tags` (jsonb), utili per futuri mapping senza dover reimportare.
**Licenza:** i dati sono ODbL. L'attribuzione «© OpenStreetMap contributors» deve essere visibile sulla mappa (app e web) e il comando la stampa. Se in futuro i dati OSM venissero arricchiti e redistribuiti come database, quel database derivato andrebbe rilasciato sotto ODbL: da verificare prima di qualsiasi export pubblico.

## ADR-007 · Punti geografici come `geography(Point, 4326)` con un value object PHP
**Decisione:** colonna `geography` con indice GIST; in PHP un `App\Support\Geo\Point` (lat/lng) con il cast `AsPoint`. Il cast scrive e legge **EWKB esadecimale**, lo stesso formato che restituisce PostgreSQL.
**Perché:** `geography` calcola le distanze in metri senza dover gestire proiezioni, che è quello che serve a "entro X km". Scrivendo EWKB identico all'output di Postgres, il dirty-check di Eloquent non vede modifiche finte (con EWKT ogni import risultava "updated").

## ADR-008 · Tassonomia stili controllata, gerarchica, con sinonimi IT/EN
**Decisione:** tabella `styles` con `parent_id`, `name_it`, `name_en` e `synonyms` (jsonb, minuscolo). I dati stanno in `database/data/styles.php` e uno seeder idempotente li inserisce o aggiorna per slug. Filtrare per uno stile padre include anche i figli. I pesi su `artist_style` e `work_style` (0..1) e `source` (`artist`|`ai`) preparano il suggerimento automatico della Fase 2.
**Perché:** con dati puliti dal primo giorno, ricerca e filtri sono affidabili. I tag liberi (`tags` + `taggables`) coprono tutto ciò che la tassonomia non prevede.

## ADR-009 · Vettori dimensionati sui modelli scelti, nullable fino alla Fase 2
**Decisione:** `artists.text_embedding vector(384)` e `works.caption_embedding vector(384)` per multilingual-e5-small; `works.image_embedding vector(768)` per SigLIP 2 base. Gli indici HNSW si creano nella Fase 2, quando ci sono dati.
**Conseguenze:** se cambia modello e quindi dimensione, serve una migrazione e il ricalcolo degli embedding. È previsto e va fatto con un job.

## ADR-010 · Morph map esplicita
**Decisione:** `Relation::enforceMorphMap` con alias `studio`, `artist`, `work`, `user`.
**Perché:** nelle colonne polimorfiche (claim, rimozioni, tag, token Sanctum) finiscono nomi stabili invece dei nomi delle classi PHP, così un refactoring non rompe i dati.

## ADR-011 · Struttura del codice backend: convenzioni Laravel
**Decisione:** modelli in `app/Models`, integrazioni in `app/Services/<Area>`, value object in `app/Support`, enum in `app/Enums`. Abbandonata la cartella `app/Domain` proposta nel piano iniziale.
**Perché:** per le dimensioni attuali la struttura standard è più semplice da leggere e da navigare. Si riconsidera se il codice cresce.

## ADR-012 · App: Expo SDK 58, development build, configurazione via env
**Decisione:** Expo Router (routing a file) e TanStack Query per lo stato server; i18n con i18next (italiano di default, inglese pronto, fallback italiano); `app.config.ts` legge `EXPO_PUBLIC_API_URL` ed `EXPO_PUBLIC_WEB_URL`. Il package Android e il bundle iOS sono `app.tatlas`; il dominio pubblico `tatlas.app` è un segnaposto configurabile. Il profilo EAS `development` produce un APK installabile direttamente.
**Perché:** MapLibre richiede codice nativo, quindi niente Expo Go. Tutta la configurazione dipendente dall'ambiente passa da variabili, senza modifiche al codice.

## ADR-013 · Lint: Pint (PHP), ESLint + Prettier (TS), Ruff (Python)
**Decisione:** Larastan/PHPStan **rimandato**: nell'ambiente di sviluppo iniziale il pacchetto `phpstan/phpstan` (distribuito solo come zip da GitHub) non era scaricabile. Da aggiungere alla CI appena possibile, al livello 5 come punto di partenza.

## ADR-014 · ai-service: modelli opzionali nelle dipendenze
**Decisione:** `pyproject.toml` ha tre extra: `dev` (test e lint), `ml` (torch, transformers, sentence-transformers) e il core minimo. In Fase 1 l'immagine non scarica i modelli (`AI_LOAD_MODELS=false`).
**Perché:** immagini piccole e CI veloce finché la ricerca intelligente non serve. Prima di attivare SigLIP 2, nella Fase 2, vanno verificate la licenza (Apache 2.0) e la qualità sulle query in italiano.

## ADR-015 · API: bbox in ordine GeoJSON, ricerca artisti tramite i loro studi
**Data:** 2026-11-13
**Decisione:** il parametro `bbox` dell'API è `west,south,east,north`, lo stesso ordine dei bounds di MapLibre e dello standard GeoJSON. Il comando OSM invece usa l'ordine di Overpass (`south,west,north,east`), perché è quello che si copia da overpass-turbo. Gli artisti si localizzano tramite i loro studi: resident, più i guest in corso o che iniziano entro 60 giorni (`AreaFilter::GUEST_HORIZON_DAYS`). `distance_m` è la distanza dallo studio più vicino. Il filtro per stile include i sotto-stili tramite una CTE ricorsiva.
**Perché:** l'app passa i bounds della mappa così come sono, senza conversioni in cui è facile sbagliare.

## ADR-016 · Dati demo espliciti e rimovibili
**Decisione:** `tatlas:demo-data` crea artisti fittizi con slug `demo-*`, bio dichiaratamente demo e immagini astratte generate con GD (nessuna foto reale). Non gira in produzione e `--purge` lo annulla. Gli studi OSM non vengono mai cancellati dal purge.
**Perché:** finché i tatuatori non si registrano, le schede artista e i filtri non si possono provare in altro modo.

## ADR-017 · URL delle immagini costruiti dall'host della richiesta (disco locale)
**Decisione:** con il disco `public`, gli URL del portfolio usano lo schema e l'host della richiesta, non `APP_URL`. Su R2/S3 si usa l'URL del disco.
**Perché:** un telefono che chiama `http://192.168.x.x:8000` deve ricevere immagini allo stesso indirizzo, senza dover cambiare `.env`.

## ADR-018 · APK di test compilato da GitHub Actions; server configurabile a runtime
**Decisione:** il workflow `android-apk` esegue `expo prebuild` + `gradlew assembleRelease` (solo arm64, firma di debug) e pubblica l'APK come artifact. Il traffico HTTP in chiaro è abilitato solo se `TATLAS_ALLOW_CLEARTEXT=1` (`expo-build-properties`). L'URL del backend si può cambiare dalle Impostazioni dell'app ed è salvato in AsyncStorage.
**Perché:** permette di provare l'app su un telefono vero senza account Expo e senza Android SDK in locale; lo stesso APK funziona con qualunque IP di rete locale. Per gli store si userà EAS (profili già presenti in `eas.json`) senza HTTP in chiaro.

## ADR-019 · Bottom sheet senza dipendenze native
**Decisione:** un bottom sheet a due posizioni scritto con `Animated` e `PanResponder`, invece di `@gorhom/bottom-sheet`.
**Perché:** meno dipendenze native (reanimated, gesture-handler) da allineare con RN 0.88 e New Architecture nell'MVP. Se servono più snap point o gesti più fluidi, si sostituisce il componente senza toccare il resto.

## ADR-020 · Tile della mappa: OpenFreeMap "positron" dietro `TileSource`
**Decisione:** `mobile/src/map/tiles.ts` definisce sorgente, URL dello stile e attribuzione. Per ora si usa lo stile positron di OpenFreeMap, chiaro e poco invasivo sotto i marker. Il passaggio a Protomaps si fa modificando solo questo file. Il riquadro di attribuzione nativo di MapLibre resta attivo e la lista riporta anche «Dati © OpenStreetMap contributors (ODbL)».

---

## Roadmap Fase 1

| Task | Stato |
|---|---|
| A1–A6 Setup monorepo, Docker, CI | ✅ |
| B1 Modello dati | ✅ |
| B2 Tassonomia stili | ✅ |
| B3–B4 Client Overpass + comando import idempotente | ✅ |
| B5 Geocoder Photon (cache + rate limit) | ✅ |
| C1–C4 API v1 (bbox/raggio, dettaglio, filtri, auth) | ✅ |
| D1–D4 Mappa nell'app | ✅ |
| E1 Schermate studio e artista | ✅ |
| E2 Pagina web pubblica artista + App Links | ⏳ |
| F1–F4 Claim, admin, portfolio, stili e guest spot | ⏳ |
| G0 APK di test via GitHub Actions | ✅ |
| G1–G2 Build EAS, privacy/GDPR | ⏳ |
