# Tutor81 - E-Learning Management Platform

## Overview

This is a full-stack e-learning management platform for Tutor81/TutorItalia, built to manage online and classroom courses, user enrollments, and certifications. The application is a modern rewrite of a legacy PHP system, migrated from a Vultr-hosted version to Replit.

The platform supports multiple user roles including administrators, tutors, and client companies, with features for course catalog management, sales tracking, user management, and certificate generation.

## Recent Changes (January 31, 2026)

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