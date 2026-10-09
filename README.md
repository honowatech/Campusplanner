# CampusTrack Planner

Web application for school schedule planning (timetables, teachers, classes, rooms and
academic resources). CampusTrack Planner is being built as a **multi-tenant SaaS** — one
database, with subscription packs, per-school SMS and payments, and a persistent demo
tenant.

The project is split into two independent Git repositories:

| Repository | Path | Stack | Deployed at |
|---|---|---|---|
| **API** | [`planner-api.campustrack.cm`](planner-api.campustrack.cm) | Laravel 11 + Sanctum 4 (PHP 8.2) | `https://planner-api.campustrack.cm` |
| **Frontend** | [`planner.campustrack.cm`](planner.campustrack.cm) | React 19 + Vite 6 (Node 20) | `https://planner.campustrack.cm` |

---

## Features

- Academic resources: departments, courses, teachers, classes, students and rooms.
- Schedule planning (emploi du temps) with room and teacher blocking/unavailability.
- Role-based access control (`super-admin`, `administrateur`, `responsable-departement`,
  `personnel-administratif`, `professeur`, `etudiant`).
- Admin dashboard with cached statistics.
- Account registration with super-admin approval workflow.
- AI-assisted scheduling via a server-side Gemini proxy (the API key never reaches the browser).
- **In progress** — multi-tenancy, subscription packs, Nexah SMS and MamoniPay (PayMe)
  payments. See [the implementation plan](PLAN_IMPLEMENTATION_SAAS_MULTITENANT.md).

---

## Tech stack

**API** — Laravel 11, PHP 8.2, Laravel Sanctum (SPA session cookies),
`spatie/laravel-permission`, `nwidart/laravel-modules` (module `Planning`), MySQL 8.

**Frontend** — React 19, TypeScript, Vite 6, TanStack Router + Query, axios, Tailwind CSS 4,
shadcn/ui (Radix UI), Recharts.

---

## Repository structure

```
campustrack planner/
├── planner-api.campustrack.cm/   # Laravel API (planner-api.campustrack.cm)
│   ├── Modules/Planning/         # nwidart module for scheduling
│   ├── app/                      # controllers, models, middleware
│   ├── routes/                   # api.php + web.php
│   └── docs/ + api-docs.md       # API reference
├── planner.campustrack.cm/       # React frontend (planner.campustrack.cm)
│   └── src/                      # routes, components, services, hooks
├── PLAN_IMPLEMENTATION_SAAS_MULTITENANT.md   # SaaS multi-tenant implementation plan
└── README.md
```

---

## Prerequisites

| Tool | Version |
|---|---|
| Node.js | 20 |
| npm | 10+ |
| PHP | 8.2+ (`mbstring`, `openssl`, `pdo`, `pdo_mysql`/`pdo_sqlite`, `intl`, `fileinfo`) |
| Composer | 2.x |
| Database | MySQL 8 (production) or SQLite (local trial) |

---

## Local development

### 1. API

```bash
cd planner-api.campustrack.cm

composer install
cp .env.example .env
php artisan key:generate

# SQLite by default. For MySQL, set the DB_* values in .env first.
touch database/database.sqlite

php artisan migrate
php artisan module:migrate
php artisan db:seed          # departments, roles/permissions, demo data
php artisan module:seed Planning

php artisan serve            # http://127.0.0.1:8000
```

### 2. Frontend

```bash
cd planner.campustrack.cm
npm ci
```

Create `.env.local` (git-ignored):

```dotenv
VITE_API_BASE_URL=http://localhost:8000
VITE_APP_NAME="Campus Planner"
```

```bash
npm run dev                  # http://localhost:3000
```

### Demo accounts (after `db:seed`)

| Account | Password | Role |
|---|---|---|
| `superadmin@campustrack.com` | `password` | super-admin |
| `admin.<code>@campustrack.com` | `password` | administrateur |
| `prof.<code>@campustrack.com` | `password` | professeur |

`<code>` is a department code: `INFO`, `MATH`, `PHYS`, `LANG`, `ECO`.
**Change every password before exposing the instance.**

---

## Authentication

Authentication uses a Laravel Sanctum **`httpOnly` session cookie** — no bearer token is
stored in the browser. This means the frontend and the API must be same-site:

- Locally, use `http://localhost:3000` + `http://localhost:8000`. Mixing `localhost` and
  `127.0.0.1` breaks the cookie.
- In production, both live on subdomains of `campustrack.cm`, and the API sets
  `SESSION_DOMAIN=.campustrack.cm`.

`GET /api/csrf-cookie` is called automatically before the first write; a 419 (expired CSRF
token) is retried once. See
[`planner.campustrack.cm/BUILD_INSTRUCTIONS.md`](planner.campustrack.cm/BUILD_INSTRUCTIONS.md)
for the full session/cookie rules.

---

## Deployment

The frontend is a static bundle and needs the API URL **at build time**
(`VITE_API_BASE_URL`). Deploy the API first, then build the frontend with the final URL.

```bash
# API
cd planner-api.campustrack.cm
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan module:migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Frontend
cd planner.campustrack.cm
npm ci
VITE_API_BASE_URL=https://planner-api.campustrack.cm VITE_APP_NAME="Campus Planner" npm run build
```

Serve `dist/` as a static SPA (fallback to `index.html`, HTTPS mandatory). Full details,
including environment variables, CORS and background jobs, are in
[`planner.campustrack.cm/BUILD_INSTRUCTIONS.md`](planner.campustrack.cm/BUILD_INSTRUCTIONS.md).

---

## Quality gates (same as CI)

**API**

```bash
vendor/bin/pint --test
php artisan test
```

**Frontend**

```bash
npm run lint
npm run format:check
npx tsc --noEmit
npm run build
```

---
