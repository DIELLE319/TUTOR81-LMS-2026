# Tutor81 LMS — Specifica Funzionale Definitiva

> Versione 1.0 — 20/02/2026
> Stato: DA APPROVARE

---

## 1. Architettura Generale

| Area | Dominio | Funzione |
|------|---------|----------|
| **LMS** | tutor81.com | Gestione tutor, aziende, corsi, iscrizioni, attestati, fatturazione |
| **Media Content (CMS)** | cm.tutor81.com | Creazione e pubblicazione contenuti corsi (server separato 107) |
| **Avvia Corso (Player)** | avviacorso.tutor81.com | Player dove il corsista svolge il corso |

---

## 2. Gerarchia Utenti

```
Super Admin
  ├── Tutor Plus (Certificato) — sconto 70%, licenza annuale
  │     ├── Admin Tutor (accede all'LMS, COMPRA i corsi)
  │     └── Aziende associate (N)
  │           └── Corsisti (N)
  │
  ├── Tutor Basic — sconto 60%, licenza annuale
  │     ├── Admin Tutor (accede all'LMS, COMPRA i corsi)
  │     └── Aziende associate (N)
  │           └── Corsisti (N)
  │
  └── Company (mono-azienda)
        ├── Admin Company (accede all'LMS, COMPRA i corsi)
        └── Corsisti (N)
```

**REGOLA**: chi compra i corsi è SEMPRE l'Admin (del Tutor o della Company). I corsisti NON accedono all'LMS.

### Ruoli e permessi

| Ruolo | Accede all'LMS | Cosa vede | Cosa può fare |
|-------|---------------|-----------|---------------|
| Super Admin | Sì | Tutto | Crea/modifica Tutor, Company, vede tutto |
| Admin Tutor Plus/Basic | Sì | Le sue aziende e corsisti | Compra corsi, gestisce aziende e corsisti |
| Admin Company | Sì | La sua azienda e corsisti | Compra corsi, gestisce i suoi corsisti |
| Corsista | NO (solo Avvia Corso) | — | Svolge i corsi assegnati |

### Licenze e Attestati

| Tipo | Sconto catalogo | Durata | Attestato |
|------|----------------|--------|-----------|
| Tutor Plus (Certificato) | 70% | 1 anno | Template attestato Plus |
| Tutor Basic | 60% | 1 anno | Template attestato Basic |

I due tipi di licenza producono **attestati diversi**.

---

## 3. Pannello LMS — Pagine (sidebar)

### 3.1 Elenco Clienti (Aziende)
- Tabella con le aziende associate al tutor
- Crea / modifica azienda
- **Visibile solo per**: Super Admin, Admin Tutor Plus/Basic
- **NON visibile per**: Admin Company (mono-azienda)

### 3.2 Catalogo Corsi
- Lista corsi pubblicati dal CMS (ogni volta che si pubblica un corso nel CMS, appare qui)
- Visualizzazione a **righe** (tabella chiara)
- Colonne: ID, Tipo (Base/Agg.), Rischio, Settore, Nome Corso, Ore, Prezzo Listino, Costo Tutor
- Pulsante **"Vendi"** per ogni corso → apre finestra iscrizione

### 3.3 Finestra Iscrizione (Vendi Corso)
Quando l'admin clicca "Vendi":
1. **Seleziona l'azienda** destinataria
2. **Sceglie modalità**:
   - **Nuovo utente** → compila: Email, Cognome, Nome, Codice Fiscale, Tipo Utente, Date
     - Il CF viene **verificato su OVH** per controllare se l'utente esiste già
   - **Utente esistente** → seleziona dalla lista corsisti dell'azienda
3. **Conferma** → iscrizioni create nel DB
4. **Email automatica** al corsista con:
   - Link a avviacorso.tutor81.com
   - Username: `NOME.COGNOME`
   - La password NON viene inviata (il corsista si autentica rispondendo a domande sul proprio CF)

### 3.4 Corsi Attivati (in Attività)
- Tabella corsi attualmente attivi (dati aggiornati dal Player)
- Filtro per azienda
- Colonne: Azienda, Utente, Corso, Email, Progresso, Stato, Date, Tutor, ecc.
- Azioni: Invia email, Invia promemoria, Modifica data fine, Elimina

### 3.5 Corsi Completati / Attestati
- Filtro/selezione azienda
- Tabella corsi completati
- Scarica attestato PDF (template diverso per Plus e Basic)
- Dati completi dal Player:
  - Tracciato entrate/uscite del corsista in ogni istante
  - Domande e risposte a cui è stato sottoposto
  - Oggetti didattici visionati

### 3.6 Utenti (Corsisti)
- Tabella corsisti per ogni azienda
- Ricerca e filtri
- Modifica dati utente

### 3.7 Feedback
- Tabella feedback dallo svolgimento corsi
- Solo visualizzazione

### 3.8 Tracciamento
- Tabella sessioni di accesso ai corsi
- Dati dal Player: ogni ingresso, uscita, oggetto visionato
- Solo visualizzazione

### 3.9 Fatturazione
- Seleziona Tutor o Company
- Seleziona mese
- Genera fattura → viene archiviata

### 3.10 Videoconferenza
- Integrazione Jitsi (API già ottenute)
- Loghi e branding personalizzati

---

## 4. Flusso Principale: Vendita Corso

```
1. Admin Tutor/Company accede all'LMS
2. Va su Catalogo Corsi
3. Clicca "Vendi" su un corso
4. Seleziona l'azienda
5. Aggiunge corsisti (nuovi con verifica CF su OVH, oppure esistenti)
6. Conferma → iscrizioni create nel DB
7. Email automatica ai corsisti con:
   - Link a avviacorso.tutor81.com
   - Username: NOME.COGNOME
8. Corsista riceve email → clicca "Avvia Corso"
9. Il Player propone gli oggetti didattici in automatico
10. Tutto viene tracciato: ingressi, uscite, domande, risposte
11. Completamento → attestato PDF disponibile nell'LMS
```

**PRIORITÀ ASSOLUTA**: creazione utente → invio email → tracciamento continuo → attestato finale

---

## 5. Stack Tecnologico (Best-in-class)

| Componente | Tecnologia | Perché |
|-----------|-----------|--------|
| **Frontend** | React 18 + TypeScript + TailwindCSS | Stabile, performante, ecosistema maturo |
| **UI Components** | Solo HTML nativo dentro modali (NO Radix nested) | Evita bug di focus loop |
| **Backend** | Express.js + TypeScript | Già in uso, affidabile |
| **Database** | PostgreSQL (locale su Vultr) | Robusto, NO OVH |
| **ORM** | Drizzle ORM | Type-safe, leggero, già in uso |
| **Auth** | Sessioni con cookie (express-session + connect-pg-simple) | Sicuro, server-side |
| **Email** | **Resend** ($20/mese, 50k email) | Migliore deliverability, API semplice, monitoring |
| **PDF Attestati** | PDFKit (già installato) | Generazione server-side |
| **Videoconferenza** | Jitsi Meet API | Open source, API già ottenute |
| **Deploy** | PM2 + Caddy | Già configurato |
| **Server** | 45.32.154.126 (Vultr) | Già attivo |

### Perché Resend per le email
- **Deliverability** al 99%+ (non finisci in spam come con Gmail SMTP)
- Dashboard per vedere email inviate, aperte, rimbalzate
- API semplicissima (3 righe di codice)
- $20/mese per 50.000 email (più che sufficiente)
- Alternativa: SendGrid (simile, ma Resend è più moderno e semplice)

---

## 6. Database — Tabelle

| Tabella | Funzione |
|---------|----------|
| `tutors` | Enti formativi (Plus/Basic) |
| `tutor_admins` | Amministratori dei tutor (accedono all'LMS) |
| `companies` | Aziende associate ai tutor |
| `students` | Corsisti delle aziende |
| `courses` | Catalogo corsi (dal CMS) |
| `enrollments` | Iscrizioni (corso + studente + date + progresso + stato) |
| `enrollment_progress` | Progresso dettagliato per singolo oggetto didattico |
| `session_logs` | Log entrate/uscite dal Player, con timestamp preciso |
| `quiz_responses` | Domande e risposte del corsista |
| `certificates` | Attestati generati (PDF) |
| `tutors_purchases` | Vendite/ordini |
| `invoices` | Fatture generate e archiviate |
| `feedback` | Feedback dai corsisti |
| `email_logs` | Log email inviate (via Resend) |
| `users` + `sessions` | Autenticazione |

---

## 7. Risposte Confermate

- [x] **SMTP** → Resend ($20/mese) — migliore affidabilità e monitoring
- [x] **Tutor Basic** → Scadenza annuale
- [x] **Company** → Stesso pannello del Tutor senza "Elenco Clienti"
- [x] **Super Admin** → Può creare/modificare Tutor e Company
- [x] **Sincronizzazione CMS → LMS** → Ogni volta che si pubblica un corso nel CMS
- [x] **Attestati** → PDF con template specifico (diverso per Plus e Basic)
- [x] **Fatturazione** → Sì, seleziona tutor/company + mese → genera fattura archiviata
- [x] **Videoconferenza** → Jitsi, API ottenute, loghi personalizzati
- [x] **Verifica CF** → Si controlla su OVH se il corsista esiste già (unico uso OVH rimasto)

---

*Documento v1.0 — 20/02/2026. Approvare per iniziare lo sviluppo.*
