# OMDb Movie Search App

A full-stack movie search application built on top of the [OMDB API](http://www.omdbapi.com).

Search movies by title, view detailed information including ratings and plot, and repeat your last five distinct search queries - all from a SPA backed by a Laravel REST API.

---

## Stack

**Backend**
- PHP 8.3
- Laravel 13
- SQLite (application data + sessions)
- Pest 4 (testing)

**Frontend**
- Vue 3 (Composition API, `<script setup>`)
- TypeScript
- Vue Router 4
- vue-i18n 11 (English + Latvian)
- Axios
- Tailwind CSS v4
- Vite 8

**Infrastructure**
- Docker (PHP-FPM + Nginx)
- pnpm

---

## Setup

### Prerequisites

- Docker
- An OMDB API key - available at [omdbapi.com/apikey.aspx](http://www.omdbapi.com/apikey.aspx)

### Quick start

```bash
# 1. Build and start the containers
docker compose up -d --build

# 2. Enter the app container
docker exec -it lvo-app bash

# 3. Install PHP dependencies, generate key, run migrations, install JS dependencies
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
pnpm install

# 4. Set your OMDB API key in .env
# OMDB_API_KEY=your_key_here

# 5. Start the Vite dev server (inside the container)
pnpm run dev
```

The application is now available at **http://localhost**.

### Useful commands inside the container

```bash
art migrate:fresh       # rebuild the database
art test                # run the Pest test suite
art tinker              # interactive Laravel REPL
pnpm run build          # production build
pnpm run type-check     # TypeScript type-check without emitting
```

(`art` is a shell alias for `php artisan`, set in the container's `.bashrc`.)

---

## Tests

Run the full Pest suite:

```bash
php artisan test
# or
./vendor/bin/pest
```

The suite covers:

- **`tests/Feature/Services/OmdbServiceTest.php`** - HTTP parameter shape, caching behavior, error propagation, distinct cache keys per operation, no-cache on failure
- **`tests/Feature/Models/SearchTest.php`** - Eloquent scopes, default and custom limits, `updateOrCreate` + `touch` deduplication
- **`tests/Feature/Api/MovieSearchTest.php`** - Endpoint contract for both search and detail: validation, success and empty payloads, status codes, history side effects
- **`tests/Feature/Api/SearchHistoryTest.php`** - Empty state, session scoping, ordering, limit

Tests use an in-memory SQLite database (`:memory:`) and the `database` session driver against the same in-memory connection - fast, isolated, and self-contained.

Frontend tests were deliberately omitted; see [Decisions](#architecture-decisions) below for the rationale.

---

## Architecture decisions

This section explains *why* the code looks the way it does, not just *what* it does.

### REST API + Vue SPA, served from a single Laravel project

The frontend is a Vue 3 SPA mounted onto a single Blade template; the backend exposes a REST API under `/api/*`. A catch-all web route returns the SPA shell for any non-API path, letting Vue Router own client-side routing.

**Why a single Laravel project, not separate frontend/backend repos?** Scope. One repo, one container, one `pnpm install` - no CORS, no duplicate package management. The conceptual separation between API layer and SPA layer is preserved without the infrastructure overhead.

### SQLite for application data and sessions

- Zero infrastructure to spin up
- A single file to inspect or delete
- Fast tests via `:memory:`
- The data volumes here are tiny - search history is per-session and bounded at five entries

Switching to MySQL or PostgreSQL is a one-line change in `.env` if needed later.

### OMDB integration design

`App\Services\OmdbService` is a thin layer that:

- Returns plain arrays (not `JsonResponse`) - keeps the service reusable from any context (controllers, console commands, queue jobs, tests)
- Wraps requests with a 10-second timeout - OMDB hanging would otherwise block the user for Guzzle's 60-second default
- Caches successful responses for one hour - protects the free-tier API daily quota and speeds up repeat requests
- **Throws** on non-2xx responses - failures propagate out of `Cache::remember` without poisoning the cache with an empty payload
- Uses distinct cache key prefixes (`omdb.search.*` vs `omdb.find.*`) - debuggable from the outside

The controller layer handles the HTTP shape and interprets OMDB's payload semantics (e.g. `Response: "False"` with `Error: "Movie not found!"` becomes a real `404`).

### Search history deduplication at the database level

The `searches` table has a unique constraint on `(session_id, query)`. The controller records a search via:

```php
Search::updateOrCreate([
    'session_id' => $sessionId,
    'query' => $searchQuery,
])->touch();
```

`updateOrCreate` either finds the existing row or creates one. `touch()` then bumps `updated_at` to "now" regardless. As a result:

- The table never accumulates duplicate `(session, query)` pairs
- The history query reduces to `ORDER BY updated_at DESC LIMIT 5` - no `GROUP BY`, no `DISTINCT`, no aggregation
- A composite index on `(session_id, updated_at)` directly supports that access pattern

This pushes the deduplication invariant down to the schema, where it can't be bypassed by other callers.

### Search results returned with status 200 + empty array

When OMDB has no matches, the API responds with `200 OK` and an empty `data` array, not `404`. The search *endpoint* exists; the *results* happen to be empty. This is the more REST-conventional behavior - `404` is reserved for the detail endpoint when a specific `imdbId` doesn't exist.

### URL query persistence

The search query is reflected in the URL (`/?q=spider`) via `router.replace`. This means:

- A back-navigation from `MovieDetails` returns the user to their populated search state
- Searches are bookmarkable and shareable
- Browser refresh preserves the user's state

### Frontend tests intentionally skipped

The Pest suite covers all meaningful backend logic - service integration with the external API, business rules around dedup, validation, controller behavior, and error paths. The frontend is largely a presentation layer over that backend, and the marginal value of component tests within the time budget didn't justify the setup cost.

### Direct controller invocation in two session-dependent tests

Two `SearchHistoryTest` tests need the controller's `$request->session()->getId()` to match a seeded `session_id`. The Laravel test client's cookie handling doesn't reliably allow pinning a session ID via cookies on API routes (cookies get dropped, encrypted, or overridden somewhere in the pipeline depending on the Laravel version). Rather than working around the test framework's quirks, those two tests invoke `SearchHistoryController::index()` directly with a manually-constructed `Request` and `Session`. The other tests in the same file still exercise the full HTTP path. A docblock in the test file documents the rationale.

---

## Project structure

```
.
├── .docker/                       # Dockerfile, nginx config, php.ini
├── docker-compose.yml
└── app/                           # Laravel application
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/Api/   # MovieSearchController, SearchHistoryController
    │   │   ├── Requests/          # SearchMoviesRequest (validation)
    │   │   └── Resources/         # API response shaping (Movie, MovieListItem, MovieRating)
    │   ├── Models/
    │   │   └── Search.php         # session_id + query + scopes
    │   └── Services/
    │       └── OmdbService.php    # OMDB integration + caching
    ├── database/migrations/
    ├── resources/
    │   ├── css/app.css
    │   ├── js/
    │   │   ├── api.ts             # axios instance + interceptor + typed methods
    │   │   ├── app.ts             # Vue app entry
    │   │   ├── App.vue
    │   │   ├── router.ts
    │   │   ├── i18n.ts
    │   │   ├── constants.ts
    │   │   ├── components/        # LanguageSwitch
    │   │   ├── views/             # Home, MovieDetails
    │   │   ├── i18n/              # en.json, lv.json
    │   │   └── types/             # api.ts, errors.ts
    │   └── views/app.blade.php    # SPA shell
    ├── routes/
    │   ├── api.php                # REST endpoints
    │   └── web.php                # SPA catch-all
    └── tests/Feature/             # Pest tests (Services, Models, Api)
```

---

## API reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/movies/search?query={term}` | Search movies by title (max 100 chars). Returns up to 10 results from OMDB |
| `GET` | `/api/movies/{imdbId}` | Get full details for a specific movie. `imdbId` must match `tt\d+` |
| `GET` | `/api/searches` | Last 5 distinct search queries for the current session |

All responses use a consistent envelope:

```json
{ "status": "success", "data": ... }
```

or

```json
{ "status": "error", "message": "..." }
```

---

## What I would add with more time

In rough priority order:

- **Frontend unit tests** with Vitest - at minimum, the axios interceptor's error translation logic
- **End-to-end test** with Playwright covering the happy path (search → result → detail → back → history)
- **PHPStan / Larastan** static analysis at level 6+ - the array shape annotations in `OmdbService` are already PHPStan-compatible
- **Pagination** on search results - OMDB returns up to 10 per page with `totalResults`; the UI currently shows only the first page
- **Rate limiting** on the search endpoint via Laravel's built-in throttle middleware
- **Persistent search history via authenticated user** instead of session - survives sign-out, syncs across devices
- **Structured logging** for OMDB failures with request context, hooked into something like Sentry
- **Accessibility pass** - semantic `<button>` elements where I'm currently using `<a role="button">`, keyboard handlers, focus management on view transitions

---

## Bilingual UI

The interface supports English (default) and Latvian, switchable via the toggle in the upper-right corner. The choice is held in `vue-i18n` runtime state; not currently persisted across sessions.
