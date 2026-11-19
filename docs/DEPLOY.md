# Server di test pubblico

Per provare l'app con **dati reali solo dal telefono**, senza PC acceso, il backend deve girare su un server raggiungibile da Internet. Questa guida crea il server in circa 15 minuti, senza usare il terminale: lo configura uno script al primo avvio.

Cosa ottieni:

- backend, pannello admin e pagine web su `https://<ip-del-server>.sslip.io`, con certificato HTTPS automatico e nessun dominio da comprare;
- studi reali di Bari importati da OpenStreetMap, più (facoltativo) artisti **fittizi** per provare le schede;
- una casella email di test su `https://<…>.sslip.io/mail`, dove arrivano le email di conferma e di reset password;
- aggiornamenti automatici: ogni 10 minuti il server scarica le ultime modifiche del branch e si ricostruisce da solo.

## 1. Crea il server

Il consigliato è **Hetzner Cloud**: server nell'UE, semplice, pochi euro al mese a consumo, si cancella quando vuoi. In alternativa va bene qualsiasi VPS con **Ubuntu 24.04** e almeno **2 vCPU e 4 GB di RAM**, purché permetta di incollare uno script di avvio (*cloud-init* / *user data*).

Su Hetzner Cloud (console.hetzner.cloud):

1. Crea un progetto e poi **Add Server**.
2. **Location**: una in Germania o Finlandia. **Image**: Ubuntu 24.04. **Type**: shared vCPU, x86, **CX22** o superiore. ARM va bene anche: tutte le immagini usate sono multi-architettura.
3. **Networking**: lascia attivo IPv4 pubblico.
4. **SSH keys**: facoltativo. Senza chiave Hetzner ti manda la password di root per email: tienila da parte, servirà solo per eventuali interventi.
5. **Cloud config**: incolla il blocco qui sotto, dopo aver cambiato email e password (almeno 12 caratteri):

```bash
#!/bin/bash
export ADMIN_EMAIL="tua@email.it"
export ADMIN_PASSWORD="scegli-una-password-lunga"
export DEMO=1   # 1 = aggiunge artisti fittizi per provare l'app, 0 = solo dati reali
curl -fsSL https://raw.githubusercontent.com/gianluigiribezzo3582-al/Tatlas/ccr-5fc7a7c3-55si3b/deploy/server-setup.sh -o /root/tatlas-setup.sh
bash /root/tatlas-setup.sh
```

6. **Create & Buy now**. Annota l'**indirizzo IPv4** del server, per esempio `203.0.113.7`.

## 2. Aspetta il primo avvio (10–15 minuti)

Lo script installa Docker, compila l'app, crea il database, importa gli studi da OpenStreetMap e attiva HTTPS.

L'indirizzo del server è l'IP con i trattini al posto dei punti: `203.0.113.7` diventa **`https://203-0-113-7.sslip.io`**.

È pronto quando questi indirizzi rispondono (dal browser del telefono va benissimo):

- `https://203-0-113-7.sslip.io/api/v1/styles`: elenco degli stili;
- `https://203-0-113-7.sslip.io/admin`: pannello admin, accesso con l'email e la password che hai messo nello script;
- `https://203-0-113-7.sslip.io/mail`: casella email di test, utente `admin` con la stessa password.

> Se dopo 20 minuti non risponde, entra dalla **Console** di Hetzner (icona `>_` del server) come root e manda le ultime righe di `tail -50 /var/log/tatlas-setup.log`.

## 3. Collega l'app

Hai due strade:

- **Subito, con l'APK che hai già:** ⚙︎ Impostazioni → spegni «Modalità demo» → server `https://203-0-113-7.sslip.io` → «Salva e prova».
- **APK che parte già collegato:** su GitHub vai in *Settings → Secrets and variables → Actions → Variables* e crea la variabile `TATLAS_API_URL` con valore `https://203-0-113-7.sslip.io/api/v1`. Da lì ogni nuova build dell'APK punta al server e parte con i dati reali. In alternativa mandami l'IP e la imposto io nel codice.

Da qui valgono tutti i test di `docs/TESTING.md`, sostituendo `http://localhost:8000` con l'indirizzo del server: registrazione, verifica dal pannello admin, studi, guest spot, portfolio, email (su `/mail`).

## Manutenzione

- **Aggiornamenti:** automatici, ogni 10 minuti (`systemctl status tatlas-update.timer`).
- **Comandi** (dalla console del server):
  ```bash
  cd /opt/tatlas/deploy
  docker compose -f docker-compose.prod.yml ps                       # stato
  docker compose -f docker-compose.prod.yml logs -f app              # log
  docker compose -f docker-compose.prod.yml exec app php artisan tatlas:demo-data --purge   # togli i dati demo
  docker compose -f docker-compose.prod.yml exec app php artisan tatlas:import-osm          # reimporta OSM
  ```
- **Spegnere tutto:** elimina il server dalla console Hetzner. I dati di test spariscono con lui.

## Prima di aprire al pubblico

Questo è un ambiente di **test** (`APP_ENV=staging`). Prima della pubblicazione sugli store servono:

- un dominio definitivo, legato alla scelta del nome;
- un vero servizio SMTP al posto della casella di test;
- backup automatici del database;
- l'informativa privacy definitiva;
- lo storage delle immagini su R2/S3.
