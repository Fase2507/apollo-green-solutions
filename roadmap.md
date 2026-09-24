# Apollo Green Solutions — Fullstack Roadmap

Build a fullstack web application to manage and monitor energy assets:

- **Backend**: Laravel (PHP 8.3+) REST API
- **Frontend**: React + TypeScript dashboard (Vite)
- **Database**: PostgreSQL
- **Auth**: Register / login / logout (Laravel Sanctum)
- **Features**: Users create projects, add tasks to each project

---

## 1. Architecture Overview

```
┌───────────────────────┐         ┌──────────────────────────┐         ┌──────────────┐
│  React + TS (Vite)    │  http   │  Laravel REST API (PHP)  │  SQL    │  PostgreSQL  │
│  frontend dashboard   │ ──────► │  Sanctum auth, routes,   │ ──────► │  users       │
│  (port 5173)          │  JSON   │  controllers, models      │         │  projects    │
└───────────────────────┘         └──────────────────────────┘         │  tasks       │
                                                                       └──────────────┘
```

Proposed monorepo layout:

```
apollo-green-solutions/
├── backend/                 # Laravel application
│   ├── app/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env
├── frontend/                # React + TypeScript app (Vite)
│   ├── src/
│   │   ├── context/         # auth state
│   │   ├── services/        # axios API client
│   │   ├── components/
│   │   └── pages/
│   ├── vite.config.ts
│   └── package.json
├── README.md
├── roadmap.md
└── requirements.txt
```

---

## 2. Prerequisites (checklist)

| Tool | Version | Why |
|------|---------|-----|
| PHP | 8.3+ | Laravel 13 requires >= 8.3 |
| Composer | 2.x | PHP dependency manager |
| Node.js | 20+ (LTS) | Vite/React toolchain |
| npm | 10+ | Frontend packages |
| PostgreSQL | 16+ | Database |
| pgAdmin / psql | any | Inspect DB |
| Git + GitHub account | any | Required by the assignment (public repo) |

Verify:

```bash
php -v
composer -V
node -v
npm -v
psql --version
```

---

## 3. Step-by-Step Build

### Phase 0 — Create the GitHub repository

```bash
git init
git remote add origin https://github.com/<you>/apollo-green-solutions.git
```

Create the repo on GitHub as **public** (explicitly required by the assignment).

### Phase 1 — Database schema design (design first)

Sensible schema, normalised and relational:

**`users`**
| column | type | notes |
|--------|------|-------|
| id | bigint PK | auto-increment |
| name | varchar(255) | |
| email | varchar(255) | unique index |
| email_verified_at | timestamp nullable | |
| password | varchar(255) | bcrypt hashed |
| timestamps | | |

**`projects`** (belongs to a user; deleting a user deletes their projects)
| column | type | notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK users.id | `onDelete: cascade`, indexed |
| name | varchar(255) | |
| description | text nullable | |
| status | enum('planned','in_progress','on_hold','completed') | default 'planned' |
| timestamps | | |

**`tasks`** (belongs to a project; deleting a project deletes its tasks)
| column | type | notes |
|--------|------|-------|
| id | bigint PK | |
| project_id | FK projects.id | `onDelete: cascade`, indexed |
| title | varchar(255) | |
| description | text nullable | |
| status | enum('todo','in_progress','done') | default 'todo' |
| priority | enum('low','medium','high') | default 'medium' |
| due_date | date nullable | |
| timestamps | | |

**Optional (energy monitoring dashboard):** `energy_readings`
(`project_id`, `generated_kwh` decimal, `consumed_kwh` decimal, `recorded_at`) to power the "monitor energy assets" chart. Populate with factories/seeders (mock data) — no external hardware required.

### Phase 2 — Backend: Laravel API

1. **Scaffold the app:**

   ```bash
   composer create-project laravel/laravel backend
   cd backend
   ```

   (Creates Laravel ^13 with PHP 8.3+.)

2. **Install Sanctum (API tokens / SPA auth):**

   ```bash
   composer require laravel/sanctum
   php artisan install:api        # publishes Sanctum + creates personal_access_tokens migration
   ```

3. **Configure PostgreSQL** in `.env`:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=apollo_green_solutions
   DB_USERNAME=postgres
   DB_PASSWORD=secret
   ```

4. **Create the database:**

   ```sql
   CREATE DATABASE apollo_green_solutions;
   ```

   ```bash
   php artisan migrate
   ```

5. **Models + relationships:**

   - `User` → `hasMany(Project::class)`
   - `Project` → `belongsTo(User)`, `hasMany(Task::class)`
   - `Task` → `belongsTo(Project)`
   - `$fillable`, casts for status/priority enums, hidden the password.

6. **API routes** (`routes/api.php`, all except register/login behind `auth:sanctum`):

   | Method | URI | Description |
   |--------|-----|-------------|
   | POST | `/api/register` | Create user, return token |
   | POST | `/api/login` | Login, return token |
   | POST | `/api/logout` | Revoke token |
   | GET | `/api/user` | Current user |
   | GET/POST | `/api/projects` | List / create projects |
   | GET/PUT/DELETE | `/api/projects/{project}` | Read / update / delete |
   | GET/POST | `/api/projects/{project}/tasks` | List / create tasks |
   | PUT/DELETE | `/api/tasks/{task}` | Update / delete task |

7. **Controllers** (`app/Http/Controllers/API/`):

   - `AuthController@register | login | logout | me`
   - `ProjectController@index | store | show | update | destroy`
   - `TaskController@index | store | update | destroy`

8. **Validation rules:** name/email/password on register, email format + unique, password `confirmed` with `min:8`; project name `required|string|max:255`; task title required. Return `422` with errors.

9. **Authorization:** scope everything to the authenticated user:

   ```php
   $projects = $request->user()->projects()->latest()->get();
   ```

   Route-model binding for project/task should verify ownership (`abort_unless`, or a `Policy`). This prevents a user reading/modifying another user's data.

10. **API responses:** consistent JSON envelope, e.g.

    ```json
    { "data": { "id": 1, "name": "Solar Plant A", "status": "in_progress" } }
    ```

    Use `resources/` (API Resources) for consistent output.

11. **Factories + seeders:** `UserFactory`, `ProjectFactory`, `TaskFactory`; seeder creates a demo user + 3 projects + tasks each.

12. **Tests (PHPUnit / Pest):** auth flow, CRUD, validation, ownership checks.

    ```bash
    php artisan test
    ```

### Phase 3 — Frontend: React + TypeScript dashboard

1. **Scaffold:**

   ```bash
   npm create vite@latest frontend -- --template react-ts
   cd frontend
   npm install
   npm install axios react-router-dom
   ```

   (Optionally `npm install @tanstack/react-query` for data fetching and `tailwindcss` for styling.)

2. **Project structure:**

   ```
   frontend/src/
   ├── types/            # Project, Task, User interfaces
   ├── services/api.ts   # axios instance, baseURL + interceptor
   ├── context/AuthContext.tsx
   ├── components/       # Navbar, ProjectCard, TaskItem, forms
   └── pages/
       ├── LoginPage.tsx
       ├── RegisterPage.tsx
       ├── DashboardPage.tsx   # project list
       ├── ProjectDetailPage.tsx # tasks for a project
       └── CreateProjectPage.tsx
   ```

3. **Vite proxy** so the SPA can call the API without CORS pain:

   ```ts
   // vite.config.ts
   server: { proxy: { '/api': 'http://localhost:8000' } }
   ```

4. **Auth flow:**

   - `AuthContext` stores `token` + `user`.
   - `axios.interceptors.request` → attach `Authorization: Bearer <token>`.
   - `axios.interceptors.response` → on `401`, clear auth + redirect to `/login`.
   - Persist token in `localStorage` so a refresh keeps the session.

5. **Pages / components:**

   - **Register/Login:** forms posting to `/api/register` / `/api/login`; on success store token, navigate to dashboard.
   - **Protected route wrapper:** `<Route>` guarded by `AuthContext` — redirect to `/login` if not authenticated.
   - **Dashboard:** fetch `GET /api/projects`; render cards; "New project" button.
   - **Project detail:** fetch `GET /api/projects/{id}`; render tasks; add/update/delete tasks.
   - **Navbar:** shows user name + logout button (`POST /api/logout`, clear token).

6. **Types** shared with the API:

   ```ts
   type Project = { id: number; name: string; description: string | null;
                    status: 'planned'|'in_progress'|'on_hold'|'completed';
                    tasks_count: number; created_at: string };
   type Task = { id: number; project_id: number; title: string;
                 status: 'todo'|'in_progress'|'done';
                 priority: 'low'|'medium'|'high'; due_date: string | null };
   ```

7. **Optional energy-monitoring dashboard page:** chart (`recharts`) rendering `energy_readings` from the demo seeder.

### Phase 4 — README + delivery

1. Write `README.md` with: project description, tech stack, prerequisites, install/run steps for backend + frontend, seeded demo credentials, API summary.
2. Verify everything from a clean clone:

   ```bash
   # backend
   composer install && cp .env.example .env && php artisan key:generate
   php artisan migrate --seed && php artisan serve
   # frontend
   npm install && npm run dev
   ```

3. Commit with clear messages, push to the **public** GitHub repo.
4. Add unit/feature tests so `php artisan test` is green.

---

## 4. Algorithm (logic flow)

### 4.1 Register

```
POST /api/register {name, email, password, password_confirmation}
 1. validate input (email unique, format; password min 8 + confirmed)
 2. if invalid -> 422 Unprocessable Entity (field errors)
 3. create User with password hashed (bcrypt)
 4. create api token via Sanctum
 5. return 201 {user, token}
```

### 4.2 Login

```
POST /api/login {email, password}
 1. validate (email + password required)
 2. attempt auth credentials
 3. if fail -> 401 Unauthorized
 4. create new Sanctum token
 5. return 200 {user, token}
```

### 4.3 Logout

```
POST /api/logout   (Authorization: Bearer <token>)
 1. resolve current token from request
 2. revoke/delete it (currentAccessToken()->delete())
 3. return 204/200
```

### 4.4 Create a project (authenticated)

```
POST /api/projects {name, description?, status?}
 1. authenticate via Sanctum middleware -> 401 if no/invalid token
 2. validate payload -> 422 on error
 3. create Project with user_id = auth()->id()
 4. return 201 {data: project}
```

### 4.5 List projects (authenticated, ownership-scoped)

```
GET /api/projects
 1. authenticate
 2. projects = User.projects (latest first), with tasks_count (withCount)
 3. return 200 {data: [project...]}
```

### 4.6 Update / delete a project

```
PUT /api/projects/{id}  |  DELETE /api/projects/{id}
 1. authenticate
 2. fetch project where id = {id} AND user_id = auth id
    - not found or not owned -> 404/403
 3. update validated fields / delete (cascade removes tasks)
 4. return 200 updated / 204
```

### 4.7 Task sub-resource (nested under project)

```
POST /api/projects/{id}/tasks {title, description?, status?, priority?, due_date?}
 1. authenticate
 2. resolve project (scoped to owner) -> else 404
 3. validate title/others -> 422 on error
 4. task.project_id = project.id
 5. return 201

GET  /api/projects/{id}/tasks  -> only tasks of that project (ownership check applies)
PUT  /api/tasks/{tid}          -> load task -> verify task.project.user_id == auth id -> update
DELETE /api/tasks/{tid}        -> same ownership check -> delete
```

### 4.8 Frontend auth guard (SPA)

```
on app load:
  if token in localStorage -> validate via GET /api/user
    - ok    : set user, allow protected routes
    - 401   : clear token, redirect /login
  else -> redirect /login (protected routes only)
login success -> save token+user, redirect /dashboard
logout        -> POST /api/logout, clear token+user, redirect /login
```

---

## 5. Design decisions (summary for README)

1. **Laravel + Sanctum** — native Laravel auth, simple token-based auth for a decoupled SPA, no third-party auth package needed.
2. **React + TypeScript + Vite** — fast dev server, typed contract with the API.
3. **PostgreSQL** — relational, ACID, appropriate for projects/tasks hierarchy; enums validate status/priority.
4. **Nested-task routes** (`/api/projects/{project}/tasks`) — keeps data hierarchy explicit and enforces ownership at the parent level; direct task routes (`/api/tasks/{task}`) still ownership-checked via the parent.
5. **Cascade deletes** — removing a user/project cleans up children; keeps DB consistent.
6. **Bearer tokens (not cookies by default)** — simplest cross-origin setup for a standalone SPA; switch to Sanctum cookie SPA mode if same-domain deployment.