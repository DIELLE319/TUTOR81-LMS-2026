# Tutor81 - E-Learning Management Platform

## Overview

This is a full-stack e-learning management platform for Tutor81/TutorItalia, built to manage online and classroom courses, user enrollments, and certifications. The application is a modern rewrite of a legacy PHP system, migrated from a Vultr-hosted version to Replit.

## IMPORTANT: Repository Git OVH (NON PERDERE!)

**Repository codice sorgente OVH:**
```
admin6d05a9d5@amm.tutor81.com:~/git/amm.tutor81.com.git
```

**Credenziali SSH OVH:**
- Username: admin6d05a9d5
- Password: Q3MxD!WMp@4n

**Database OVH:**
- Host: 135.125.205.19:3306
- Database: pro_tutor81
- User: pro_tutor81
- Password: hpm0?7C3

The platform supports multiple user roles including administrators, tutors, and client companies, with features for course catalog management, sales tracking, user management, and certificate generation.

## Recent Changes (February 2, 2026)

- **Admin Tutor Data Filtering Implemented (con sicurezza lato server)**:
  - All pages now filter data by tutorId for admin tutors (role=1)
  - Pages updated: Clients, Sales, ActivatedCourses, Users, Certificates
  - **SICUREZZA LATO SERVER**: Le API forzano il filtro tutorId basandosi sull'utente autenticato
    - Helper `getAuthenticatedUserTutorId(req)` recupera tutorId dal database
    - Per role=1, il tutorId è forzato dal server (ignora parametri client)
    - Per altri ruoli, il filtro è opzionale
  - APIs updated: `/api/clients`, `/api/sales`, `/api/enrollments`, `/api/students`, `/api/attestati`
  - Admin tutors only see data from their own training organization (ente formativo)
  - User auth now includes tutorId and tutorName from tutor_admins table
  - **Login Admin Tutor**: username = nome.cognome (minuscolo), password = codice fiscale (maiuscolo)

## Previous Changes (February 1, 2026)

- **Complete Data Import from OVH**:
  - **14 Enti formativi (tutors)** - IDs aligned with OVH
  - **30 Admin** - IDs aligned with OVH (tutor_admins table)
  - **2077 Aziende clienti (companies)** 
  - **14039 Corsisti (students)**
  - **7699 Vendite (tutors_purchases)**

- **OVH Database Structure CONFIRMED**:
  - `users.company_id` = Cliente dell'ente formativo (NOT tutor)
  - `users.creator_id` = Admin che ha creato l'utente
  - `users.code` = Licenza del corso venduto
  - `users.role` = Ruolo (0=corsista, 1=admin tutor, 2=referente aziendale, 1000=super admin)
  - `learning_project_users.company_id` = Admin user ID (who created enrollment)
  - `learning_project_users.id_company` = Client company ID
  - `learning_project_users.user_id` = Corsista (student)

- **tutors_purchases Table Structure**:
  - `tutor_id` = Admin ID (chi effettua l'acquisto)
  - `user_company_ref` = Tutor di riferimento
  - `customer_company_id` = Cliente
  - `learning_project_id` = Corso
  - `qta`, `price`, `creation_date` = Dettagli vendita

## Previous Changes (January 31, 2026)

- **CRITICAL FIX: LP → Course → Module Mapping Corrected**
  - **Root Cause**: OVH has separate tables: `learning_project` → `unities_lo` → `course` → `course_course_modules` → `modules`
  - **Solution**: Script `scripts/fix_lp_course_mapping.cjs` uses `unities_lo` to find which `course_id` belongs to each `learning_project_id`, then maps modules via `relations.course_course_modules`
  - **Final Junction Tables**: `course_modules` (548), `module_lessons` (2505), `lesson_learning_objects` (6769)
  - **OVH Data Structure**:
    - `unities_lo.json`: Maps LP → Course (e.g., LP 263 → Course 324)
    - `relations.course_course_modules`: Maps Course → Modules (e.g., Course 324 → Module 593)
  - **View Page**: `/course-structure/:id` - shows modules > lessons > learning objects tree
  
- **Legacy Course Structure Display** (from old system):
  - MODULI INSERITI: Each module shows duration, description, "Modifica modulo" button
  - LEZIONI INSERITE: Each lesson shows ID, title, calculated duration
  - Learning Objects: Icon by type (video, slide, document), ID, title, duration in parentheses
  - Durations shown as "durata calc.: X min" or "X ora Y min"
  
- **Database Restructure**: Merged `courses` table into `learning_projects`
  - Unified 293 records (1:1 relationship previously)
  - `modules` and `tests` now reference `learning_project_id` instead of `course_id`
  - Added new columns to `learning_projects`: `subcategory`, `course_type`, `destinatario`, `destination`, `course_validity`, `external_integration`, `law_reference`, `total_elearning`, `max_execution_time`, `percentage_to_pass`, `producers`, `professors`, `didactics`, `objectives`, `target_audience`, `prerequisites`, `course_program`, `owner_user_id`
  - Removed `courses` table entirely
- **API Updates**: Endpoints changed from `/api/courses/:id/publish` to `/api/learning-projects/:id/publish`
- **Attestati (Certificates)**: Integrated FTP access to download PDF certificates from Vultr server
  - FTP Path: `/media/media/attestati/` (24,584 PDF files)
  - File naming: `attestato_licenza_{legacy_id}.pdf`
  - API endpoints: `/api/attestati`, `/api/attestato/:legacyId/download`
- **Enrollments Table**: Added `legacy_id`, `legacy_user_id`, `accreditation_code`, `days_to_alert` columns
- **Imported learning_project_users data**: 33,216 records updated with legacy IDs

## Previous Changes (January 30, 2026)

- **Migrated from Vultr**: Imported all components from the Vultr server (React Router → wouter)
- **Auth System**: Replaced localStorage auth with Replit OIDC Auth
- **Data Fetching**: Converted from direct fetch to TanStack Query
- **Database Schema**: Added companies, learning_projects, tutors_purchases, company_users, certificates tables
- **UI Theme**: Dark mode with yellow accent (#EAB308)
- **Sample Data**: Seeded 5 sample courses for testing

## User Preferences

Preferred communication style: Simple, everyday language.

**UI/UX Style Preferences:**
- Tema scuro (dark mode) con accento giallo (#EAB308)
- Stile tabelle: sfondo scuro, bordi sottili, hover evidenziato
- Badge colorati per stati (giallo=attivo, verde=completato, grigio=pending)
- Layout pulito con spacing consistente
- Questo stile va applicato OVUNQUE nell'applicazione

## System Architecture

### Frontend Architecture
- **Framework**: React 18 with TypeScript
- **Routing**: Wouter (lightweight alternative to React Router)
- **State Management**: TanStack React Query for server state
- **UI Components**: Shadcn/ui component library with Radix UI primitives
- **Styling**: Tailwind CSS with custom design tokens (CSS variables for theming)
- **Build Tool**: Vite with hot module replacement
- **Form Handling**: React Hook Form with Zod validation

### Backend Architecture
- **Runtime**: Node.js with Express
- **Language**: TypeScript (ESM modules)
- **API Design**: RESTful endpoints under `/api/*`
- **Authentication**: Replit Auth integration (`server/replit_integrations/auth.ts`)
- **Database ORM**: Drizzle ORM with PostgreSQL dialect

### Data Storage
- **Database**: PostgreSQL (configured via `DATABASE_URL` environment variable)
- **Schema Location**: `shared/schema.ts` - contains all table definitions
- **Migrations**: Drizzle Kit (`drizzle.config.ts`) with `db:push` command

### Key Database Entities
- **companies**: Stores both tutors (training providers) and client companies
- **users**: User accounts with authentication data
- **learningProjects**: Unified course catalog with pricing, content metadata, and course details (merged from courses table)
- **tutorsPurchases**: Sales/purchase records between tutors and clients
- **modules/lessons**: Hierarchical course content structure (modules reference learning_project_id)
- **enrollments/progress**: Student enrollment and progress tracking

### Project Structure
```
├── client/src/          # React frontend application
│   ├── components/      # Reusable UI components
│   ├── pages/           # Route page components
│   ├── hooks/           # Custom React hooks
│   └── lib/             # Utilities and query client
├── server/              # Express backend
│   ├── routes.ts        # API route definitions
│   ├── storage.ts       # Database access layer
│   └── db.ts            # Database connection
├── shared/              # Shared code between client/server
│   ├── schema.ts        # Drizzle database schema
│   └── routes.ts        # API contract definitions
└── old_platform/        # Legacy PHP system (reference only)
```

### Build & Development
- **Development**: `npm run dev` - runs both Vite dev server and Express with HMR
- **Production Build**: `npm run build` - uses custom build script (`script/build.ts`)
  - Frontend: Vite builds to `dist/public`
  - Backend: esbuild bundles server to `dist/index.cjs`
- **Database**: `npm run db:push` - pushes schema changes to PostgreSQL

## External Dependencies

### Database
- **PostgreSQL**: Primary data store, connection via `DATABASE_URL` environment variable
- **connect-pg-simple**: Session storage in PostgreSQL

### Authentication
- **Replit Auth**: Primary authentication mechanism via `@replit/repl-auth`

### UI/UX Libraries
- **Radix UI**: Accessible component primitives (dialogs, dropdowns, forms, etc.)
- **Shadcn/ui**: Pre-built component library using Radix + Tailwind
- **Lucide React**: Icon library
- **Embla Carousel**: Carousel/slider component
- **cmdk**: Command palette component

### Data & Forms
- **Drizzle ORM**: Type-safe database queries
- **Drizzle Zod**: Schema-to-validation integration
- **Zod**: Runtime type validation
- **TanStack React Query**: Server state management and caching
- **React Hook Form**: Form state management

### Utilities
- **date-fns**: Date formatting and manipulation
- **nanoid**: Unique ID generation
- **xlsx**: Excel file processing (for imports/exports)

### Development Tools
- **Vite**: Frontend build tool with HMR
- **esbuild**: Server bundling for production
- **TypeScript**: Type safety across the stack