# TUTOR81 — Versione e Architettura

## Data: 20 Febbraio 2026

---

## 🏗️ ARCHITETTURA

### LMS (Learning Management System)
- **URL**: https://tutor81.com
- **Server**: 45.32.154.126 (Vultr VPS)
- **Reverse Proxy**: Caddy (`/etc/caddy/Caddyfile`)
- **Process Manager**: PM2 (`tutor81-lms`)
- **PM2 script**: `/var/www/tutor81/current/dist/index.cjs`
- **PM2 cwd**: `/var/www/tutor81/current` (symlink)
- **Release attiva**: `/var/www/tutor81/releases/20260220-no-ovh`
- **Porta**: 3001
- **Database**: PostgreSQL locale (tutor81:tutor81pass@localhost/tutor81)
- **Stack**: Node.js + Express + Drizzle ORM + React + TailwindCSS + Vite
- **Auth**: Email/password con sessione (express-session + connect-pg-simple)

### CMS (Content Management System)
- **URL**: https://cm.tutor81.com
- **Server**: 107.191.63.149 (Vultr VPS)
- **Process Manager**: PM2 (`tutor81-cms`)
- **Path**: `/var/www/tutor81-cms/dist/`
- **Porta**: 3002 (su server 45 — ma DNS punta a 107)
- **Database**: PostgreSQL su 107 (separato dal LMS)

### Relazione CMS → LMS
- Il CMS è la fonte autoritativa per i **corsi**
- Quando un corso viene pubblicato nel CMS, deve essere sincronizzato nel DB LMS
- Le enrollment nel LMS hanno FK sulla tabella `courses` locale

---

## 🔐 CREDENZIALI

### Admin LMS
- **Email**: admin@tutor81.com
- **Password**: ZXcv1712
- **Role**: 1000 (super admin)
- **Hash**: SHA-256

### Database PostgreSQL (server 45)
- **User**: tutor81
- **Password**: tutor81pass
- **Database**: tutor81

---

## 📦 DEPLOY LMS

### Struttura directory server
```
/var/www/tutor81/
├── current -> /var/www/tutor81/releases/20260220-no-ovh  (symlink)
├── releases/
│   ├── 20260217-191525/   (release originale con OVH+Replit)
│   ├── 20260220-stable/   (prima release VPS)
│   └── 20260220-no-ovh/   (release pulita — ATTIVA)
└── snapshots/
    ├── 20260220-114612/
    ├── 20260220-115046/
    ├── 20260220-151254/   (pre-rimozione OVH)
    └── backup-20260220-123013/  (backup completo)
```

### Comandi deploy (da locale)
```bash
# 1. Build locale
cd /Users/lucapedretti/tutor81-lms
npm run build

# 2. Crea release sul server
RELEASE="$(date +%Y%m%d-%H%M%S)"
ssh root@45.32.154.126 "mkdir -p /var/www/tutor81/releases/${RELEASE}/dist"

# 3. Copia build
scp -r dist/* root@45.32.154.126:/var/www/tutor81/releases/${RELEASE}/dist/

# 4. Copia .env
ssh root@45.32.154.126 "cp /var/www/tutor81/current/.env /var/www/tutor81/releases/${RELEASE}/.env"

# 5. Aggiorna symlink e riavvia
ssh root@45.32.154.126 "ln -sfn /var/www/tutor81/releases/${RELEASE} /var/www/tutor81/current && pm2 restart tutor81-lms"
```

### Snapshot e rollback
```bash
# Salva snapshot della release corrente
ssh root@45.32.154.126 "lms-save"

# Ripristina da snapshot
ssh root@45.32.154.126 "lms-restore <TIMESTAMP>"
```

---

## 🧹 PULIZIA EFFETTUATA (20 Feb 2026)

### OVH MySQL — RIMOSSO COMPLETAMENTE
- ❌ `mysql2/promise` import
- ❌ `getOvhDbConfig`, `getOvhConnection`, `hasOvhSyncEnv`
- ❌ Cache tutor OVH (`OvhActiveTutorIdsCache`, `OvhActiveTutorKeysCache`, `OvhActiveTutorPgIdsCache`)
- ❌ `getOvhActiveTutorKeysCached`, `getOvhActiveTutorPgIdsCached`, `getOvhActiveTutorIdsCached`
- ❌ `syncEnrollmentToOvh` (sync iscrizioni verso OVH)
- ❌ Endpoint test: `/api/test-ovh`, `/api/test-ovh-recent`, `/api/test-ovh-check/:licenseCode`, `/api/test-ovh-compare/:license1/:license2`, `/api/test-ovh-course/:courseId`
- ❌ `/api/fix-ovh-password/:userId`
- ❌ `/api/export/tutor-gerarchia` (CSV da OVH)
- ❌ Fallback OVH in `/api/check-fiscal-code` (ora solo PostgreSQL locale)
- ❌ Fallback OVH in `/api/player/validate-license` (ora solo PostgreSQL locale)
- ❌ Lettura vendite da OVH in `/api/sales` (ora usa `tutors_purchases` locale)
- ❌ Filtraggio tutor OVH in `/api/tutors`, `/api/clients`, `/api/companies`, `/api/companies/tutors`

**Risultato**: -1008 righe, +84 righe. routes.ts da ~2870 a ~2070 righe.

### Replit OIDC — RIMOSSO COMPLETAMENTE
- ❌ `openid-client`, `passport`, `memoizee` imports
- ❌ `getOidcConfig`, `ensureStrategy`, strategia OIDC
- ❌ `/api/login` (redirect a Replit OAuth)
- ❌ `/api/callback` (callback Replit OAuth)
- ❌ `/api/logout` (Replit end-session)
- ❌ Dipendenza da `REPL_ID`
- ✅ Sostituito con `POST /api/admin-login` (email + password SHA-256)
- ✅ Sostituito con `GET /api/admin-logout` (destroy sessione)
- ✅ Frontend con form email/password

---

## 📁 FILE PRINCIPALI

### Server
- `server/index.ts` — Entry point Express, middleware, static serving
- `server/routes.ts` — Tutti gli endpoint API (~2070 righe)
- `server/replit_integrations/auth/replitAuth.ts` — Auth email/password + sessione
- `server/replit_integrations/auth/routes.ts` — Route `/api/auth/user`
- `server/replit_integrations/auth/storage.ts` — CRUD utenti DB

### Client
- `client/src/App.tsx` — Router + LoginPage con form email/password
- `client/src/hooks/use-auth.tsx` — Hook auth con login/logout
- `client/src/components/Layout.tsx` — Layout sidebar con versione

### Shared
- `shared/schema.ts` — Schema Drizzle ORM (tutors, companies, students, enrollments, courses, etc.)
- `shared/models/auth.ts` — Schema tabelle users + sessions

---

## 🗄️ TABELLE DATABASE (PostgreSQL)

| Tabella | Descrizione |
|---------|-------------|
| `users` | Utenti admin (login LMS) |
| `sessions` | Sessioni express-session |
| `tutors` | Enti formativi |
| `tutor_admins` | Admin degli enti |
| `companies` | Aziende clienti |
| `students` | Corsisti/dipendenti |
| `courses` | Corsi (sincronizzati da CMS) |
| `enrollments` | Iscrizioni ai corsi |
| `tutors_purchases` | Vendite/acquisti corsi |
| `invoices` | Fatture |
| `feedback` | Feedback corsisti |
| `modules` | Moduli dei corsi |
| `lessons` | Lezioni dei moduli |
| `course_modules` | Relazione corsi-moduli |
| `module_lessons` | Relazione moduli-lezioni |

---

## 🏷️ VERSIONING

| Tag | Data | Descrizione |
|-----|------|-------------|
| (pending) TUTOR81-LMS-v1 | 20 Feb 2026 | Prima release pulita (no OVH, no Replit) |
