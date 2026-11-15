# Provare Tatlas sul telefono

Guida per installare l'APK di test su un telefono Android.

## 0. Senza PC: modalità demo

L'APK di test **parte in modalità demo**: studi, artisti, guest spot e immagini sono **fittizi** e inclusi nell'app, quindi non serve nessun server. Basta installare l'APK (passo 3) e aprirlo. In alto compare il badge viola «MODALITÀ DEMO · dati fittizi».

Puoi provare tutte le funzioni della tabella al passo 5. Gli studi demo sono sparsi tra Bari, Molfetta, Monopoli, Altamura e altri comuni: allontana lo zoom per vederli tutti. Le date dei guest spot si calcolano rispetto al giorno in cui apri l'app, quindi ci sono sempre guest "in arrivo".

Per usare i dati veri del backend: **⚙︎ → disattiva "Modalità demo"** e imposta il server (passi 1–4). Se il collegamento al server va a buon fine, la modalità demo si spegne da sola. Puoi riattivarla in ogni momento dalle Impostazioni, oppure dal pulsante «Usa la modalità demo» che compare quando il server non risponde.

## 1. Con il PC: avvia il backend

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

### Con il backend: funzioni da tatuatore e pannello admin

Servono il PC e la modalità demo disattivata (⚙︎ → spegni "Modalità demo").

Prima crea un amministratore sul PC:

```bash
docker compose exec app php artisan tatlas:make-admin tuo@email.it --password=una-password-lunga
```

| # | Funzione | Come provarla | Risultato atteso |
|---|---|---|---|
| 16 | Registrazione | ☺ in alto → Registrati, lascia attivo "Sono un tatuatore", accetta la privacy | Si apre direttamente il form del profilo artista |
| 17 | Profilo artista | Nome d'arte, bio, Instagram (anche solo `@nome`), lingue, prezzo, fino a 5 stili → Salva | Nell'account compare "⏳ Profilo in attesa di verifica"; il profilo non appare ancora sulla mappa |
| 18 | Verifica admin | Dal browser del PC apri <http://localhost:8000/admin>, accedi, scheda "Artisti da verificare" → **Verifica e pubblica** | Nell'app l'account mostra "✓ verificato" e il profilo è visibile pubblicamente |
| 19 | Dove lavoro | Account → Dove lavoro → cerca lo studio per nome o città → Resident → Salva | Lo studio mostra il tuo profilo tra gli artisti |
| 20 | Guest spot | Stesso percorso, scegli Guest spot e le date dal calendario | Lo studio diventa viola sulla mappa e compare con il filtro "Guest spot in arrivo" |
| 21 | Nuovo studio | "Lo studio non c'è? Aggiungilo" → nome e indirizzo | Lo studio viene geolocalizzato con Photon e lo gestisci tu |
| 22 | Portfolio | Account → Portfolio → Dalla galleria o Scatta foto → descrizione e stili → Pubblica | Il lavoro appare nella griglia e nella scheda pubblica. Il telefono ridimensiona e ricomprime la foto, il server la ricomprime di nuovo: posizione GPS e dati EXIF non vengono salvati. Massimo 12 lavori; tieni premuto un lavoro per eliminarlo |
| 23 | Rivendica uno studio | Scheda di uno studio OSM → "Rivendica questo studio" → messaggio e link di prova | Nel pannello admin, scheda "Rivendicazioni": **Approva** ti assegna lo studio; **Rifiuta** richiede un motivo |
| 24 | Rimozione GDPR | Per ora solo via API: `POST /api/v1/removal-requests` | Nel pannello admin, scheda "Richieste di rimozione": **Nascondi contenuto** lo toglie da mappa e API |
| 25 | Logout | Account → Esci | Il token viene revocato sul server e cancellato dal telefono |
| 26 | Conferma email | Dopo la registrazione apri <http://localhost:8025> sul PC (Mailpit) e la mail «Conferma la tua email» → link | Pagina «Email confermata ✓». Nell'app tocca «Ho confermato» e l'avviso sparisce |
| 27 | Password dimenticata | Accedi → «Password dimenticata?» → email → apri la mail in Mailpit → nuova password | Tutte le sessioni vengono chiuse; accedi di nuovo con la nuova password |
| 28 | Pagina web artista | Sul PC apri `http://localhost:8000/artisti/<slug>` (lo slug è nella scheda artista, pulsante Condividi) | Pagina con stili, portfolio, studi, mappa e pulsante «Apri nell'app» |
| 29 | Segnala / rimozione | Scheda studio o artista → «Segnala un errore o chiedi la rimozione» | Si apre il modulo web già compilato con il contenuto; la richiesta arriva nel pannello admin |
| 30 | Elimina account | Account → Elimina account → password | Account, profilo e foto vengono cancellati; l'app torna alla mappa |

> **Link nelle email dal telefono:** i link usano `APP_URL`. Per aprirli dal telefono imposta in `backend/.env` `APP_URL=http://<IP-del-PC>:8000` e riavvia con `docker compose restart app worker`.

## Non ancora disponibile in questa build

- Modifica dei dati di uno studio rivendicato dall'app (l'API `PATCH /studios/{id}` è pronta).
- Apertura automatica dei link `https://tatlas.app/...` nell'app: richiede il dominio reale e l'impronta della chiave di firma (vedi ADR-026).
- Pagina web pubblica dell'artista e link che aprono l'app (App Links): E2.
- Ricerca intelligente "descrivi il tatuaggio": Fase 2.

## Problemi comuni

- **La mappa è grigia:** le tile arrivano da `tiles.openfreemap.org`, quindi il telefono deve avere accesso a Internet, non solo alla rete locale.
- **"Server non raggiungibile":** IP sbagliato, PC e telefono su reti diverse (anche la rete "ospiti" del router conta come diversa), firewall che blocca la porta 8000, oppure `docker compose` non avviato.
- **Le immagini del portfolio non si vedono:** hai saltato `php artisan storage:link`.
