# Provare Tatlas sul telefono

Guida per installare l'APK di test su un telefono Android e collegarlo al backend che gira sul tuo PC.

## 1. Avvia il backend sul PC

```bash
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed   # tabelle + tassonomia stili
docker compose exec app php artisan storage:link      # servono le immagini del portfolio
docker compose exec app php artisan tatlas:import-osm # studi reali da OpenStreetMap (Bari)
docker compose exec app php artisan tatlas:demo-data  # 30 artisti FITTIZI con guest spot e immagini
```

Controlla che risponda: apri <http://localhost:8000/api/v1/styles> nel browser del PC.

> I dati demo sono inventati: ogni artista demo ha uno slug che inizia con `demo-`. Si cancellano con `php artisan tatlas:demo-data --purge`. Se l'import OSM fallisce (Overpass a volte è sovraccarico), `tatlas:demo-data` crea anche 8 studi demo intorno a Bari, così l'app resta utilizzabile.

## 2. Trova l'IP del PC

Telefono e PC devono essere sulla **stessa rete Wi-Fi**.

- Windows: `ipconfig` → "Indirizzo IPv4", per esempio `192.168.1.20`
- macOS: `ipconfig getifaddr en0`
- Linux: `hostname -I`

Dal browser del telefono apri `http://<IP>:8000/api/v1/styles`. Se non si apre, sblocca la porta 8000 nel firewall del PC.

## 3. Scarica e installa l'APK

1. Su GitHub apri **Actions → android-apk** e scegli l'ultima esecuzione verde sul branch.
2. In fondo alla pagina, sotto **Artifacts**, scarica `tatlas-apk` (uno zip che contiene `tatlas-<commit>.apk`).
3. Copia l'APK sul telefono e aprilo. Android chiede di consentire l'installazione da questa fonte: accetta.

Per compilare un APK con il tuo IP già impostato: **Actions → android-apk → Run workflow**, inserisci `http://<IP>:8000/api/v1`. In alternativa lo imposti dall'app (passo 4).

> È un APK di test firmato con la chiave di debug e abilitato al traffico HTTP verso la rete locale. Non è la build per il Play Store: quella si farà con EAS.

## 4. Collega l'app al PC

Al primo avvio, se l'app mostra "Server non raggiungibile":

1. Tocca **⚙︎** in alto a destra (Impostazioni).
2. Inserisci `192.168.1.20:8000`, cioè il tuo IP. L'app aggiunge da sola `http://` e `/api/v1`.
3. Tocca **Salva e prova**. Deve comparire "✓ Connesso al server". Torna indietro e la mappa si carica.

## 5. Cosa puoi provare

| # | Funzione | Come provarla | Risultato atteso |
|---|---|---|---|
| 1 | Mappa | Apri l'app | Mappa di Bari a tutto schermo, attribuzione © OpenStreetMap visibile |
| 2 | Cluster | Allontana lo zoom | I punti vicini si raggruppano in cerchi neri con il numero; toccando un cluster la mappa si avvicina |
| 3 | Marker | Tocca un punto arancione | Il punto si ingrandisce e lo studio sale in cima alla lista |
| 4 | Guest spot | Punti viola | Studi con un guest spot in corso o nei prossimi 60 giorni |
| 5 | Lista sincronizzata | Sposta la mappa | Il titolo "N studi in quest'area" e la lista si aggiornano dopo circa mezzo secondo |
| 6 | Bottom sheet | Trascina la maniglia in su o in giù, oppure toccala | La lista si espande o si riduce |
| 7 | Posizione | Tocca il banner 📍 oppure ◎ | Android chiede il permesso (solo durante l'uso). Se accetti, la mappa si centra su di te e la lista mostra le distanze. Se rifiuti, l'app continua su Bari |
| 8 | Permesso negato | Rifiuta due volte, poi tocca il banner | Si aprono le impostazioni di Android per riattivare la posizione |
| 9 | Filtro stile | Tocca un chip (per esempio "Blackwork") | Restano solo gli studi con artisti di quello stile, compresi i sotto-stili (dotwork, geometrico…). Tocca di nuovo per togliere il filtro |
| 10 | Filtro guest | Tocca "Guest spot in arrivo" | Solo gli studi con guest spot imminenti |
| 11 | Scheda studio | Tocca una riga della lista | Indirizzo, indicazioni stradali (apre Maps), chiamata, sito, orari, artisti resident e guest con le date |
| 12 | Scheda artista | Dalla scheda studio tocca un artista | Stili, bio, griglia del portfolio (immagini demo astratte), studi e guest spot, link, pulsante Condividi |
| 13 | Condividi | "Condividi profilo" | Si apre il menu di condivisione di Android con il link `tatlas.app/artisti/…` (la pagina web arriverà con il task E2) |
| 14 | Server | Impostazioni → indirizzo sbagliato → Salva e prova | Messaggio "il server non risponde"; "Ripristina" torna al valore predefinito |
| 15 | Lingua | Imposta il telefono in inglese | Interfaccia e nomi degli stili in inglese |

## Non ancora disponibile in questa build

- Registrazione e login dall'app: l'API c'è già (`/api/v1/auth/*`), le schermate arrivano con F1.
- Rivendicazione di uno studio, pannello admin, upload del portfolio: F1–F4.
- Pagina web pubblica dell'artista e link che aprono l'app (App Links): E2.
- Ricerca intelligente "descrivi il tatuaggio": Fase 2.

## Problemi comuni

- **La mappa è grigia:** le tile arrivano da `tiles.openfreemap.org`, quindi il telefono deve avere accesso a Internet, non solo alla rete locale.
- **"Server non raggiungibile":** IP sbagliato, PC e telefono su reti diverse (anche la rete "ospiti" del router conta come diversa), firewall che blocca la porta 8000, oppure `docker compose` non avviato.
- **Le immagini del portfolio non si vedono:** hai saltato `php artisan storage:link`.
