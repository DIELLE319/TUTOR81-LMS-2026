# Deployment (Ubuntu VPS)

Questa cartella contiene uno script per mettere online **questa** app (Node/Express + React build).

## Cosa fa `setup.sh`
- Installa Node.js 20 LTS, Nginx e Certbot (Ubuntu)
- Copia il progetto in `/opt/tutor81-lms`
- Crea un file env in `/etc/tutor81-lms.env` (ti chiede i valori)
- Se non hai Postgres pronto, può anche installare e creare un DB Postgres locale
- Esegue `npm ci` + `npm run build`
- Crea un service systemd `tutor81-lms`
- Configura Nginx come reverse proxy sul dominio scelto
- (Opzionale) abilita HTTPS con Certbot

## Uso

Sul server (Ubuntu), dentro la root del progetto:

```bash
chmod +x deployment/setup.sh
sudo ./deployment/setup.sh
```

## Cartella dedicata "LMS" (consigliato)

Se sul server vuoi avere una cartella chiara e separata tipo `/opt/LMS`, hai due opzioni.

### Opzione A (consigliata): cartella separata, service stabile

Così tieni il nome del service (es. `tutor81-lms`) ma installi in `/opt/LMS`:

```bash
sudo ./deployment/setup.sh --app-name tutor81-lms --app-dir /opt/LMS --domain lms.tutor81.com --port 5000
```

### Opzione B: rinominare anche l'istanza

Nota: `--app-name` influenza anche:
- la cartella app (`/opt/<app-name>`)
- il file env (`/etc/<app-name>.env`)
- l'unit systemd (`/etc/systemd/system/<app-name>.service`)
- il vhost nginx

Esempio (produzione in `/opt/LMS` rinominando tutto):

```bash
sudo ./deployment/setup.sh --app-name LMS --domain lms.tutor81.com --port 5000
```

## Staging (consigliato per evitare confusione)

Puoi creare una seconda istanza indipendente (staging) usando:
- un sottodominio dedicato (es. `stg.lms.tutor81.com`)
- una porta diversa (es. `5001`)
- un service systemd separato (es. `tutor81-lms-staging`)

Esempio (anche sullo stesso server):

```bash
sudo ./deployment/setup.sh \
	--app-name tutor81-lms-staging \
	--domain stg.lms.tutor81.com \
	--port 5001
```

Questo crea:
- app in `/opt/tutor81-lms-staging`
- env in `/etc/tutor81-lms-staging.env`
- unit systemd `/etc/systemd/system/tutor81-lms-staging.service`
- vhost nginx dedicato

Check:
- `https://<dominio>/api/health` deve restituire `{ "ok": true }`

## Update codice (dopo)
Dopo aver aggiornato i file in `/opt/tutor81-lms`:

```bash
cd /opt/tutor81-lms
sudo -u tutor81 npm ci
sudo -u tutor81 npm run build
sudo systemctl restart tutor81-lms
```

## Versioning / rollback (senza staging)

Se vuoi lavorare direttamente su `tutor81.com` ma poter tornare indietro rapidamente, puoi usare un approccio “releases + symlink”:

- Ogni deploy crea una cartella release in `/opt/<app>-releases/<timestamp>-<ref>`
- `/opt/<app>` diventa un symlink alla release corrente
- Rollback = repoint del symlink + restart del service

Script inclusi:
- `deployment/release.sh`
- `deployment/rollback.sh`

Esempio deploy (da root della repo sul server):

```bash
sudo ./deployment/release.sh --app-name tutor81-lms --ref prod
```

Rollback al rilascio precedente:

```bash
sudo ./deployment/rollback.sh --app-name tutor81-lms
```

Nota: questa strategia riduce il rischio, ma **non sostituisce** uno staging (che resta il modo migliore per testare auth/HTTPS/migrazioni senza impattare utenti).

Per staging:

```bash
cd /opt/tutor81-lms-staging
sudo -u tutor81 npm ci
sudo -u tutor81 npm run build
sudo systemctl restart tutor81-lms-staging
```
