# Tutor81 LMS — Deploy (Vultr / any VPS)

Questa repo contiene **una singola app Node/Express** che serve sia:
- API (`/api/*`)
- Frontend React (build in `dist/public`)

In produzione il server avvia `dist/index.cjs` e serve static da `dist/public`.

## Importante: `tutor81.com/admin-login` NON è questa app

L’URL `https://tutor81.com/admin-login` oggi risponde HTML “legacy” (non le API JSON di questa app).
Per poter testare le pagine React (sidebar nera/gialla) devi:
- mettere la nuova app su un **sottodominio** (consigliato), oppure
- fare reverse proxy su un **path dedicato** (es. `/app`) evitando di rompere il legacy.

## Check rapido: sto parlando con la nuova app?

Apri nel browser:
- `https://<TUO-DOMINIO>/api/health`

Risultati attesi:
- nuova app: JSON `{ "ok": true }`
- legacy: HTML (pagina web)

## Prerequisiti

- Node.js 20+ (consigliato 20 LTS)
- PostgreSQL (per DB + sessioni)
- HTTPS terminato su reverse proxy (cookie session è `secure: true`)

## Variabili ambiente

Nel repo trovi solo il template [.env.example](.env.example). Il file reale con i valori (segreti inclusi) **non** è versionato: va creato sul server (es. `/etc/tutor81-lms.env`) oppure esportato come variabili d’ambiente.

Crea un file env sul server (es. `/etc/tutor81-lms.env`) e imposta almeno:

- `NODE_ENV=production`
- `PORT=5000`
- `DATABASE_URL=postgres://USER:PASSWORD@HOST:5432/DBNAME`
- `SESSION_SECRET=...` (stringa lunga random)

Auth (attuale): Replit OIDC
- `REPL_ID=...`
- `ISSUER_URL=https://replit.com/oidc` (opzionale)

OVH MySQL (quando usi API che leggono/scrivono su OVH)
- `OVH_DB_HOST=...`
- `OVH_DB_PORT=3306`
- `OVH_DB_USER=...`
- `OVH_DB_PASSWORD=...`
- `OVH_DB_NAME=...`

## Build

Nel folder della repo sul server:

1. Install deps
   - `npm ci`

2. Build production
   - `npm run build`

3. (Se serve) migrazioni Drizzle
   - `npm run db:push`

Avvio manuale (test):
- `node dist/index.cjs`

## Setup automatico (Ubuntu)

Se preferisci uno script “one shot” (installa Node/Nginx, configura systemd + reverse proxy e builda), vedi:
- [deployment/README.md](deployment/README.md)

## Staging (per evitare confusione)

Consigliato: crea uno staging separato su Vultr (o anche sullo stesso VPS) con:
- sottodominio dedicato (es. `stg.lms.tutor81.com`)
- istanza/app separata (service systemd + env file separati)

Esempio:

```bash
sudo ./deployment/setup.sh \
   --app-name tutor81-lms-staging \
   --domain stg.lms.tutor81.com \
   --port 5001
```

Vantaggi pratici:
- non tocchi la produzione mentre provi HTTPS + auth
- puoi testare callback OIDC su un dominio distinto
- puoi fare smoke test (`/api/health`) e patchare endpoints senza rischi

## Cartella dedicata "LMS"

Se vuoi tenere l'app in una cartella server più leggibile (es. `/opt/LMS`), puoi usare `--app-dir`:

```bash
sudo ./deployment/setup.sh --app-name tutor81-lms --app-dir /opt/LMS --domain lms.tutor81.com --port 5000
```

In alternativa, se vuoi rinominare anche service+env, usa `--app-name LMS` (crea `LMS.service` e `/etc/LMS.env`).

## systemd (consigliato)

Esempio unit: `/etc/systemd/system/tutor81-lms.service`

```ini
[Unit]
Description=Tutor81 LMS (Node)
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/tutor81-lms
EnvironmentFile=/etc/tutor81-lms.env
ExecStart=/usr/bin/node /opt/tutor81-lms/dist/index.cjs
Restart=always
RestartSec=3

# Hardening base
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

Comandi:
- `sudo systemctl daemon-reload`
- `sudo systemctl enable tutor81-lms --now`
- `sudo systemctl status tutor81-lms`

## Nginx reverse proxy (esempio)

### Opzione A (consigliata): sottodominio dedicato

Esempio: `lms.tutor81.com` → proxy su `http://127.0.0.1:5000`

```nginx
server {
  server_name lms.tutor81.com;

  # TLS qui (certbot / acme)

  location / {
    proxy_pass http://127.0.0.1:5000;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  }
}
```

### Opzione B: path dedicato su dominio legacy

Se vuoi restare su `tutor81.com` senza rompere il sito attuale, usa un path tipo `/app`.
Attenzione: questa app serve HTML con routing client-side, quindi serve riscrivere tutto sotto quel prefisso.
In questo caso conviene prima mettere il sottodominio (Opzione A) e solo dopo fare refactor “base path” se necessario.

## Auth (Replit OIDC) — nota critica

L’auth attuale usa callback `https://<DOMINIO>/api/callback` determinata da `req.hostname`.
Quindi:
- il dominio reale (es. `lms.tutor81.com`) deve essere consentito/configurato nel provider OIDC
- senza questo, il login fallirà anche se il deploy è corretto

Se l’obiettivo è eliminare Replit Auth in produzione, va pianificato un cambio di auth (es. password/login interno o altro OIDC).
