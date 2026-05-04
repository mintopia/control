# Admin API & API Keys — Design

## Summary

Add four read-only `/api/v1/*` endpoints for admin/integration use (events list/show, tickets list with optional event filter), authenticated by a new standalone `ApiKey` model rather than user-bound Sanctum tokens. Provide an admin UI under Settings to manage these keys.

## Goals

- Expose event and ticket data to trusted external integrations.
- Manage credentials independently of user accounts (revocable per integration; not coupled to a person).
- Reuse the existing fractal transformer pattern and admin CRUD scaffolding so the addition feels native to the codebase.

## Non-goals (YAGNI)

- Per-key scopes or abilities. All keys can call all admin API endpoints. Add later if a real need appears.
- Write endpoints. Read-only only.
- Listing/filtering beyond what is specified (no `?user=`, `?seated=`, etc.).
- IP allowlists, rate limiting beyond Laravel defaults, or key expiry.

## Endpoints

All under `/api/v1`, all behind `auth:apikey`. Pagination default `perPage=20`, mirroring the existing `SeatingPlanController`.

| Method | Path | Description |
|---|---|---|
| GET | `/events` | List events, ordered `starts_at desc`, paginated. |
| GET | `/events/{code}` | Show one event by `code`. |
| GET | `/tickets` | List tickets, ordered `id asc`, paginated. |
| GET | `/tickets?event={code}` | Same as above, scoped to one event. |

Authorization is fully delegated to the guard: any valid, enabled key sees everything (drafts included). No `can:see,event` policy gating.

### Response shapes

**Event** (list & show):

```
id, code, name, starts_at, ends_at, draft, seating_locked,
seating_opens_at, seating_closes_at, boxoffice_url
```

**Ticket** (list):

```
id, reference, external_id, name, created_at,
event:    { id, code, name }
type:     { id, name }
provider: { id, code, name }
user:     { id, nickname, name, email } | null
seat:     { id, label, row, number } | null
```

`qrcode` and `transfer_code` are deliberately excluded from API responses — they grant claim/transfer rights and must not be readable by integrations.

## Authentication

### `ApiKey` model

Table `api_keys`:

| column | type | notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | human label |
| `key_hash` | string, unique, indexed | `hash('sha256', $plaintext)` |
| `last_four` | string(4) | last 4 chars of plaintext, for UI identification |
| `enabled` | boolean, default true | |
| `last_used_at` | timestamp, nullable | |
| `created_at`, `updated_at` | timestamps | |

The model uses the `Illuminate\Auth\Authenticatable` trait and implements the `Illuminate\Contracts\Auth\Authenticatable` contract so a guard can return it.

### Plaintext key format

`ctrl_` + 40 random hex characters (e.g. `ctrl_a1b2c3d4...`). The `ctrl_` prefix lets secret-scanning tools (GitHub, etc.) recognise leaked keys. Plaintext is shown to the admin **once** on the create-success screen; only the SHA-256 hash and last four chars are persisted.

SHA-256 (not bcrypt) is sufficient because the plaintext is high-entropy server-generated; bcrypt's slow-by-design property is unnecessary and would make every authenticated request expensive.

### Guard

`App\Auth\ApiKeyGuard implements Illuminate\Contracts\Auth\Guard`. Behaviour:

1. Read `Authorization: Bearer <key>` from the request.
2. Hash with SHA-256.
3. Look up `ApiKey::where('key_hash', $hash)->where('enabled', true)->first()`.
4. If found, update `last_used_at = now()` via a single-column `update()` (no model events, no observer overhead).
5. Return the `ApiKey` instance, or `null`.

Registered in `app/Providers/AuthServiceProvider.php` via `Auth::extend('apikey', ...)`. `config/auth.php` adds:

```php
'guards' => [
    'apikey' => ['driver' => 'apikey', 'provider' => 'apikeys'],
    // existing guards untouched
],
'providers' => [
    'apikeys' => ['driver' => 'eloquent', 'model' => App\Models\ApiKey::class],
    // existing providers untouched
],
```

The `last_used_at` write is acceptable on every request for current traffic; if it becomes a bottleneck it can be moved to a throttled or queued listener.

### Routes

In `routes/api.php`, alongside the existing seating-plans group:

```php
Route::prefix('v1')->name('api.v1.')->middleware('auth:apikey')->group(function () {
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{event:code}', [EventController::class, 'show'])->name('events.show');
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
});
```

The existing `auth:sanctum` group for seating plans is unaffected.

## Controllers

Under `app/Http/Controllers/Api/V1/`, following the existing `SeatingPlanController` pattern (`paginate` + `fractal(...)->respond()`).

- `EventController::index` — `Event::query()->orderBy('starts_at', 'desc')->paginate($perPage)`, transformed with `EventTransformer`.
- `EventController::show(Event $event)` — direct fractal item response.
- `TicketController::index(Request)` — `Ticket::query()` with eager loads (`user.primaryEmail`, `seat`, `type`, `provider`, `event`); applies `?event={code}` filter by resolving the code to an `Event` and scoping by `event_id`; paginates and transforms with `TicketTransformer`.

## Transformers

In `app/Transformers/V1/`. The existing `AbstractTransformer::modifyForUser()` already gates "admin extras" (id, timestamps, `getAdminProperties()`) behind a passed-in admin `User`. API key requests have no User, so we extend the abstract minimally:

- Add an `?ApiKey $apiKey = null` second constructor parameter alongside the existing `?User $user`. Rename `modifyForUser` to `modifyForViewer` (or add a parallel branch) so the admin-equivalent payload is returned when **either** an admin user **or** any `ApiKey` is set. Intent: an API key sees the same payload an admin user would see.
- The existing `EventTransformer` and `SeatingPlanTransformer` continue working unchanged for the existing seating-plans endpoint when called without an `ApiKey`.

New transformers (all extending `AbstractTransformer`):

- `TicketTransformer` — default-includes `event`, `type`, `provider`, `user`, `seat`. Null user/seat handled with `$this->null()`.
- `AbridgedUserTransformer` — `{ id, nickname, name, email }`. `email` resolved via `User::email` accessor.
- `AbridgedSeatTransformer` — `{ id, label, row, number }`.
- `AbridgedTicketTypeTransformer` — `{ id, name }`.
- `AbridgedTicketProviderTransformer` — `{ id, code, name }`.

The existing `EventTransformer` is reused for the API; we adjust it (or its `getAdminProperties`) to expose the agreed full set (`draft`, `seating_opens_at`, `seating_closes_at`, `boxoffice_url`) when in the admin/API-key path. Public (non-admin) callers' payload is unchanged.

Why dedicated abridged transformers rather than reusing one "full" transformer with includes? The API contract is fixed and minimal; dedicated abridged transformers make it impossible to leak more by toggling an include. Full versions can be added later if needed.

## Admin UI

### Routes

Inside the existing admin `settings` prefix and `can:admin` middleware:

```php
Route::prefix('settings')->name('settings.')->group(function () {
    // ... existing settings routes ...
    Route::resource('apikeys', ApiKeyController::class)->except(['show']);
    Route::get('apikeys/{apikey}/delete', [ApiKeyController::class, 'delete'])
        ->name('apikeys.delete');
});
```

Following the existing admin pattern (separate `delete` GET confirm screen → `destroy` DELETE).

### Controller — `App\Http\Controllers\Admin\ApiKeyController`

- `index()` — paginated list. Columns: `name`, `last_four` rendered as `ctrl_…XXXX`, enabled status, `last_used_at`, `created_at`, edit/delete actions.
- `create()` / `store(ApiKeyStoreRequest)` — form takes only `name`. Generates plaintext, hashes it, stores `key_hash` + `last_four`, then redirects to a one-time success view with the plaintext flashed in session (`apiKeyPlaintext`). Plaintext is never persisted.
- `edit()` / `update(ApiKeyUpdateRequest, ApiKey)` — only `name` and `enabled` are editable. Rotation = revoke + create new.
- `delete(ApiKey)` / `destroy(DeleteRequest, ApiKey)` — confirm screen + delete.

### Form requests

- `ApiKeyStoreRequest` — `name` required string, max length (e.g. 255).
- `ApiKeyUpdateRequest` — `name` required string; `enabled` boolean.

### Views

New directory `resources/views/admin/apikeys/`:

- `index.blade.php`, `create.blade.php`, `edit.blade.php`, `delete.blade.php`, `_form.blade.php` — mirroring sibling admin resources (e.g. themes).
- `created.blade.php` — one-time plaintext display. Shows the key in a copy-to-clipboard control with a clear warning that this is the only time it will ever be visible. Reachable only after a successful `store` (reads from flash).

### Settings index link

Small edit to `resources/views/admin/settings/index.blade.php` to add an "API Keys" link.

## Testing

Per project rule: every change is programmatically tested.

- `tests/Feature/Api/V1/EventControllerTest` — 401 without key, 401 with disabled/unknown key, 200 with valid key; payload shape (including `draft`, `seating_*`); pagination; show by code.
- `tests/Feature/Api/V1/TicketControllerTest` — auth as above; payload shape including null `user` and null `seat`; `?event={code}` filter; pagination; eager-loading verified via query count assertion.
- `tests/Feature/Admin/ApiKeyControllerTest` — admin CRUD; plaintext shown once on create (and not again on subsequent visits); edit limited to `name` + `enabled`; non-admin users blocked (403); delete flow.
- `tests/Unit/Auth/ApiKeyGuardTest` — resolves enabled keys, ignores disabled keys, ignores unknown keys, updates `last_used_at`.
- `database/factories/ApiKeyFactory.php` — for test setup.

## Out-of-scope changes touched

- `config/auth.php` — adds `apikey` guard and `apikeys` provider entries. No removal or change to existing entries.
- `app/Providers/AuthServiceProvider.php` — registers the `apikey` driver via `Auth::extend`.
- `app/Transformers/V1/AbstractTransformer.php` — small addition for the API-key path; existing `modifyForUser` behaviour preserved.
- `app/Transformers/V1/EventTransformer.php` — exposes the full agreed admin set on the admin/API-key path.
- `resources/views/admin/settings/index.blade.php` — adds a link to the new sub-page.
