# Apollo Green Solutions

> **Fullstack Developer Task** — a web application to *manage and monitor energy
> assets* (from the Apollo Green Solutions assignment brief).

## About the Project

Apollo Green Solutions is a fullstack web application for teams that plan, build
and maintain renewable-energy assets — solar farms, wind turbines, EV charging
hubs, battery storage and similar. Each asset ("project") is planned out as a
project with a clear status lifecycle, and broken down into individual tasks
that the team can track, prioritise and complete.

It was built to the assignment requirements: user authentication, projects with
tasks, a Laravel REST API backend, a React + TypeScript dashboard frontend, a
PostgreSQL database, and a README with setup instructions and design decisions.

### Key functions

- **Authentication** — register, login and logout; registration confirms the
  email by sending a 6-digit verification code via SMTP, and every user only
  ever sees their own data (token-based sessions via Laravel Sanctum).
- **Projects** — create, list, view, rename/re-status and delete projects, with a
  status workflow (`planned` → `in_progress` → `on_hold` → `completed`) and a
  live task count on the dashboard.
- **Tasks** — add tasks to a project with a title, description, priority
  (`low`/`medium`/`high`), optional due date, and move them through
  `todo` → `in_progress` → `done` inline on the project page.
- **REST API** — consistent `{ data: ... }` response envelope, validation errors
  as `422`, and ownership isolation (`404` for resources you don't own).
- **Demo data** — a seeded demo account with 3 sample projects (EV Charging Hub,
  Solar Farm Alpha, Wind Farm Beta) and 15 tasks, ready to explore.

### Where to use it

- **Project/portfolio planning** for solar, wind, EV-charging and storage
  rollouts — track site readiness from planning to commissioning.
- **Operations monitoring** — per-site task checklists for inspections,
  maintenance and compliance.
- **As a template** for any SaaS-style product needing auth plus a
  projects/tasks hierarchy: the backend and dashboard are reusable starting
  points.

### Tech Stack

| Layer        | Technology                                          |
| ------------ | --------------------------------------------------- |
| Frontend     | React 19, TypeScript 6, Vite 8, React Router 7, Axios
| Backend      | PHP 8.5, Laravel 13, Laravel Sanctum 4 (token auth)
| Database     | PostgreSQL 18 (dev), SQLite in-memory (tests)
| Email        | SMTP (Gmail) via Laravel Mail
| Tooling      | Composer 2, Node 25 / npm, PHPUnit, OXlint, Pint

## Project Structure

```
backend/    Laravel API application
frontend/   React + Vite single-page application
```

## Getting Started

### 1. Backend (http://127.0.0.1:8000)

```bash
cd backend
composer install
cp .env.example .env      # set DB_* for your PostgreSQL
php artisan key:generate
php artisan migrate --seed   # creates tables + demo data
php artisan serve --host=127.0.0.1 --port=8000
```

Default PostgreSQL config (see `backend/.env`):

```
DB_CONNECTION=pgsql
DB_DATABASE=apollo_green_solutions
DB_USERNAME=postgres
DB_PASSWORD=1234
```

### 2. Frontend (http://localhost:5173)

```bash
cd frontend
npm install
npm run dev
```

Vite proxies every `/api/*` request to the Laravel server (`http://127.0.0.1:8000`), so no CORS is needed in development. Set `VITE_API_URL` if you want to point at a different API base.

### 3. Login (demo account)

| Email             | Password |
| ----------------- | -------- |
| `demo@apollo.test` | `password` |

The seeder also creates 3 sample projects with 15 tasks. You can register a new account on the login page.

## API Structure

Base URL: `http://127.0.0.1:8000/api`

All protected endpoints require an `Authorization: Bearer <token>` header. The token is issued by `login`, or by `verify` after registration.

### Response envelope

Successful requests return JSON wrapped in a `data` object:

```json
{
  "data": {
    "id": 1,
    "name": "Solar Farm Alpha",
    "...": "..."
  }
}
```

Errors return `{ "message": "..." }` (and `errors` for validation failures) with the appropriate HTTP status:

- `401` unauthenticated / invalid token
- `404` resource not found (also returned for resources you don't own, so presence stays hidden)
- `422` validation failed

### Endpoints

| Method | URI                        | Auth | Description                                   |
| ------ | -------------------------- | ---- | --------------------------------------------- |
| POST   | `/api/register`            | No   | Register; emails a 6-digit verification code  |
| POST   | `/api/verify`              | No   | Confirm the emailed code and get a token      |
| POST   | `/api/login`               | No   | Login + get token                             |
| POST   | `/api/logout`              | Yes  | Revoke current token                          |
| GET    | `/api/user`                | Yes  | Show authenticated user                       |
| GET    | `/api/projects`            | Yes  | List own projects (with `tasks_count`)        |
| POST   | `/api/projects`            | Yes  | Create project                                |
| GET    | `/api/projects/{project}`  | Yes  | Show project incl. its full `tasks` list      |
| PUT    | `/api/projects/{project}`  | Yes  | Update project                                |
| DELETE | `/api/projects/{project}`  | Yes  | Delete project (cascades to its tasks)        |
| GET    | `/api/projects/{project}/tasks` | Yes | List tasks of a project                    |
| POST   | `/api/projects/{project}/tasks` | Yes | Create a task in a project                 |
| PUT    | `/api/tasks/{task}`        | Yes  | Update task                                  |
| DELETE | `/api/tasks/{task}`        | Yes  | Delete task                                  |

### Resources

**Project** — `id, name, description, status, tasks_count, tasks (when loaded), created_at, updated_at`

- `status`: `planned` (default) | `in_progress` | `on_hold` | `completed`

**Task** — `id, project_id, title, description, status, priority, due_date, created_at, updated_at`

- `status`: `todo` (default) | `in_progress` | `done`
- `priority`: `low` | `medium` (default) | `high`

## Testing

```bash
cd backend
php artisan test    # 25 feature tests, 71 assertions (runs on in-memory SQLite)

cd frontend
npm run build       # TypeScript type-check (tsc -b) + production bundle
npm run lint        # OXlint static analysis
```

## Seeded Demo Data

`DatabaseSeeder` creates the demo user plus:

- **EV Charging Hub** — 5 tasks (completed)
- **Solar Farm Alpha** — 5 tasks (in progress)
- **Wind Farm Beta** — 5 tasks (planned)