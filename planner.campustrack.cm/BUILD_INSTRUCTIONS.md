# Build & Deployment Instructions

CampusTrack Planner is made of two independent Git repositories that must be deployed in a specific order:

| Repository | Path | Stack | Deployed at |
|---|---|---|---|
| `planner-api.campustrack.cm` | `../planner-api.campustrack.cm` | Laravel 11 + Sanctum 4 (PHP 8.2) | `https://planner-api.campustrack.cm` |
| `planner.campustrack.cm` | this repository | React 19 + Vite 6 (Node 20) | `https://planner.campustrack.cm` |

The front-end is a static bundle: it needs the **API URL at build time** (`VITE_API_BASE_URL`). Deploy the API first, then build the front-end with the final API URL.

Authentication is a **Sanctum session cookie** (`httpOnly`), not a Bearer token. The deployment rules in [Session and cookies](#session-and-cookies) are mandatory, not optional.

---

## 1. Prerequisites

| Tool | Version |
|---|---|
| Node.js | 20 (CI uses 20) |
| npm | 10+ |
| PHP | 8.2+ with `mbstring`, `openssl`, `pdo`, `pdo_mysql` (or `pdo_sqlite`), `intl`, `fileinfo` |
| Composer | 2.x |
| Database | MySQL 8 in production, SQLite is enough for a local trial |
| Web server | nginx or Apache with HTTPS (Let's Encrypt) |

---

## 2. Local development

### 2.1 API

```bash
cd ../planner-api.campustrack.cm

composer install
cp .env.example .env
php artisan key:generate

# SQLite (default in .env.example). For MySQL, set DB_* in .env first.
touch database/database.sqlite

php artisan migrate
php artisan module:migrate
php artisan db:seed          # departments, roles/permissions, demo data
php artisan module:seed Planning

php artisan serve            # http://127.0.0.1:8000
```

Optional: `composer run dev` starts the API, the queue worker, the log viewer and the API's own Vite assets in one command (requires `npx concurrently`).

### 2.2 Front-end

```bash
npm ci
```

`.env.local` (git-ignored):

```dotenv
VITE_API_BASE_URL=http://localhost:8000
VITE_APP_NAME="Campus Planner"
```

```bash
npm run dev                  # http://localhost:3000
```

### 2.3 Rules for local cookie sessions

- **Use the same host name on both sides.** `http://localhost:3000` + `http://localhost:8000` works. Mixing `localhost` and `127.0.0.1` breaks the session cookie (`document.cookie` on one host cannot read cookies set for the other).
- Never build with `VITE_API_BASE_URL=https://...` while testing locally: cookies would be sent to the wrong place.
- The API trusts `SANCTUM_STATEFUL_DOMAINS` (in the API `.env`). A request from an unlisted origin is treated as stateless, so login returns 500 and writes return 401.

### 2.4 Demo accounts (after `db:seed`)

| Account | Password | Role |
|---|---|---|
| `superadmin@campustrack.com` | `password` | super-admin |
| `admin.<code>@campustrack.com` | `password` | administrateur (one per department) |
| `prof.<code>@campustrack.com` | `password` | professeur |

`<code>` is a department code: `INFO`, `MATH`, `PHYS`, `LANG`, `ECO`. Change every password before exposing the instance.

New accounts created through the front-end stay pending (`is_approved = false`) and get a 403 until a super-admin approves them from the Users screen.

---

## 3. Production deployment

### 3.1 Environment variables (API)

```dotenv
APP_NAME="Campus Planner"
APP_ENV=production
APP_KEY=base64:...                  # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://planner-api.campustrack.cm

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=campus_planner
DB_USERNAME=campus_planner
DB_PASSWORD=...

# Sanctum SPA : le cookie de session fait foi
SESSION_DRIVER=database             # table `sessions` (migration users)
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.campustrack.cm
SANCTUM_STATEFUL_DOMAINS=planner.campustrack.cm

CACHE_STORE=database
QUEUE_CONNECTION=database

GEMINI_API_KEY=...                  # serveur uniquement, jamais côté front
```

Then add the front-end origin to `config/cors.php` → `allowed_origins` (the list is hard-coded, not read from the environment) and keep `supports_credentials => true`.

### 3.2 Release (API)

```bash
cd /var/www/planner-api
composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force
php artisan module:migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear          # run only if you need to drop stale caches
```

Seed the roles/permissions once on a fresh instance (this also creates the super-admin):

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan route:list --path=api           # sanity check: the app boots and /api routes exist
```

Warm the dashboard cache after the first deploy (optional, the scheduler keeps it fresh afterwards):

```bash
php artisan dashboard:warm-cache all --period=7d
```

Web server: document root must be `public/`, HTTPS mandatory (`SESSION_SECURE_COOKIE=true` would otherwise make the browser drop the session), and no `Authorization: Bearer` support is needed anymore.

### 3.3 Background jobs (API)

Only needed if you dispatch queued work; harmless otherwise.

```cron
* * * * * cd /var/www/planner-api && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/planner-api && php artisan queue:work --tries=3 >> /dev/null 2>&1
```

The scheduler warms the dashboard cache every 5 minutes (realtime) and hourly (24h / 7d / 30d). `onOneServer()` is used, so the shared cache store must be reachable by every node.

### 3.4 Release (front-end)

`VITE_API_BASE_URL` is inlined at build time, so the API must already be deployed and HTTPS-valid.

```bash
cd /var/www/planner
git pull
npm ci

# .env.production.local or an exported variable
export VITE_API_BASE_URL=https://planner-api.campustrack.cm
export VITE_APP_NAME="Campus Planner"

npm run build                     # tsc --noEmit + vite build -> dist/
```

Serve `dist/` as a static SPA:

- fallback: every unknown path must return `index.html` (TanStack Router file routes, e.g. `/login`, `/teachers`);
- `/assets/*`: `Cache-Control: public, max-age=31536000, immutable` (hashed filenames);
- `index.html`: `Cache-Control: no-cache`;
- gzip/brotli enabled; the largest chunk is ~380 kB (124 kB gzip).

---

## 4. Session and cookies

Sanctum's SPA mode forces `SameSite=Lax` on stateful requests, so the front-end and the API must be **same-site** (subdomains of the same registrable domain) and must agree on the cookie domain, otherwise the front-end cannot read the `XSRF-TOKEN` cookie and every write fails with 419.

| Requirement | Value | Why |
|---|---|---|
| Cookie domain | `SESSION_DOMAIN=.campustrack.cm` | the front-end JS can only read a cookie whose domain covers `planner.campustrack.cm` |
| Front origin in `SANCTUM_STATEFUL_DOMAINS` | `planner.campustrack.cm` (no scheme, no path) | decides which requests get a session + CSRF protection |
| CORS `allowed_origins` | `https://planner.campustrack.cm` | preflight for every request, with credentials |
| HTTPS on both hosts | required | `SESSION_SECURE_COOKIE=true` and `SameSite=None` are not used |
| CSRF cookie | `XSRF-TOKEN` (readable by JS, not `httpOnly`) | sent back as `X-XSRF-TOKEN` by axios on every write |

How the flow works in the app (no manual step required):

1. `GET /api/csrf-cookie` is called automatically before the first write (shared between parallel requests).
2. axios sends `X-XSRF-TOKEN` from the cookie (`withXSRFToken`) and the session cookie (`withCredentials`).
3. A 419 (expired CSRF token) is retried **once** after refreshing the cookie.
4. `GET /api/auth-user` is the single source of truth on page load and after a hard reload; a 401 on a data endpoint redirects to `/login`.
5. `POST /api/logout` invalidates the session server-side; `EnsureUserIsApproved` logs out and returns 403 for an account whose approval was revoked.

After changing any of the variables above on the server, run `php artisan config:cache` (or `optimize:clear`) or the change stays invisible.

---

## 5. Post-deploy verification

```bash
# 1. CSRF cookie
curl -i -c /tmp/c.txt https://planner-api.campustrack.cm/api/csrf-cookie

# 2. Login (cookie jar)
curl -i -b /tmp/c.txt -c /tmp/c.txt -X POST https://planner-api.campustrack.cm/api/login \
  -H "Content-Type: application/json" -H "X-XSRF-TOKEN: <valeur du cookie XSRF-TOKEN>" \
  -d '{"email":"superadmin@campustrack.com","password":"<mot-de-passe>"}'

# 3. Session reused
curl -b /tmp/c.txt https://planner-api.campustrack.cm/api/auth-user
```

In the browser (`https://planner.campustrack.cm`):

- [ ] login works, and a **hard reload** (F5) keeps the user logged in (session cookie, no localStorage token);
- [ ] creating/editing a record works (no 419 in the network tab);
- [ ] logout returns to `/login`, and the back button does not restore the session;
- [ ] registering a new account, then approving it as super-admin, unlocks the login;
- [ ] a pending or suspended account gets the French 403 message, not a silent failure.

---

## 6. Quality gates (same as CI)

API:

```bash
vendor/bin/pint --test
php artisan test
```

Front-end:

```bash
npm run lint
npm run format:check
npx tsc --noEmit
npm run build
```

Both repositories run these checks in GitHub Actions (`.github/workflows/ci.yml`). The workflows only run once a remote is configured on the repositories.

---

## 7. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| 500 on `POST /api/login` | the request is not treated as stateful (origin absent from `SANCTUM_STATEFUL_DOMAINS`) | add the front origin (no scheme), then `php artisan config:clear` |
| 419 on every write | the front-end cannot read `XSRF-TOKEN` (cookie domain mismatch) | set `SESSION_DOMAIN=.campustrack.cm`, clear config cache |
| 401 right after login | cookies not stored: mixed `localhost` / `127.0.0.1`, or HTTP with `SESSION_SECURE_COOKIE=true` | use one host name; `SESSION_SECURE_COOKIE=false` locally |
| CORS error in the console | front origin missing from `config/cors.php` → `allowed_origins` | add it, then `php artisan config:clear` |
| 403 "en attente de validation" | `is_approved = false` | approve the account as super-admin |
| `API_BASE_URL` is `undefined` at runtime | `VITE_API_BASE_URL` missing at build time | export it before `npm run build` |
| Login succeeds but the UI has no permissions | the API does not return roles/permissions in the login payload; the UI falls back to `admin` and the API policies remain authoritative | known limitation, see `api-docs.md` |
