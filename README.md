# phpmodern (working name)

A PHP framework whose only goal is to bring PHP the developer experience that
modern front-end (React, Vue, Svelte, Next.js) and back-end (Node, Go,
Elixir/Phoenix) ecosystems are known for — component-based UI, reactivity
without client-side polling, strict typing, and modern tooling — while still
being adoptable inside a PHP codebase that already exists.

## Why

PHP is widely (and often unfairly) seen as a dated technology. This project's
premise: most of what makes modern stacks feel good is achievable in PHP
today — it's just not been packaged as a coherent, incrementally-adoptable
toolkit. See `docs/` for the architecture rationale as the project grows.

## Two modes, one engine

- **Kernel mode** — a project built from scratch on top of this framework.
  The framework owns the bootstrap/router and every feature is available.
- **Bridge mode** — install one or more of the core packages into an
  *existing* PHP site via Composer, without giving up control of routing or
  bootstrap. Incremental adoption for real legacy codebases.

Both modes run the exact same underlying packages — bridge mode is not a
parallel, lesser reimplementation of kernel mode.

## Repository layout

```
packages/core/          Independently publishable engine packages
  component-engine/      Server-rendered components: typed props, lifecycle
  orm/                    Minimal typed DB access (PDO wrapper, query helper, migrations)
  push-hub/               Standalone SSE daemon: server push, no client polling
  typing-contracts/       The check command + rule banning untyped/mixed props
  queue/                  Database-backed job queue + standalone worker daemon
  store/                  Redux-shaped state container (reducers + listeners)
  security/               CSRF (double-submit cookie) + default security headers
  auth/                   Session, PasswordHasher, and login/logout/check
  validation/             Typed Rule objects + structured per-field errors
packages/devtools/dev-server/  Polling file watcher that triggers hot reload via push-hub
packages/devtools/debugbar/    Request-scoped profiler: DebugBar::time() around any callable
packages/bridge/adapter/  Entry point for embedding into an existing PHP site
packages/framework/kernel/  Meta-package for greenfield projects (router, front controller, make:component)
apps/legacy-demo/         A simulated pre-existing PHP site, using bridge mode only
apps/starter-kernel/      A project built from scratch on kernel mode
docs/                     Architecture notes and verification checklists
```

Every package under `packages/` has its own `composer.json` and is meant to
be split out and published independently once the API stabilizes.

## Status: Phase 0 proof of concept

The current milestone proves the core thesis end to end: a stateful
component (`OrderStatusBadge`) renders on the server; a user action updates a
row in SQLite; the change is pushed to the browser over Server-Sent Events
— **no client-side polling** — and this works identically whether the
component is mounted from a simulated legacy script (bridge mode) or from a
route in a from-scratch app (kernel mode), reusing the exact same PHP class.

The client patches the DOM in place with [Idiomorph](https://github.com/bigskysoftware/idiomorph)
(vendored under `packages/core/push-hub/resources/vendor/idiomorph/`) rather
than replacing a component's `outerHTML` wholesale, so focus, scroll position
and untouched child state survive an update.

Explicit non-goals for this phase: ORM relations, file-based routing, hot
module reload, authentication, and background jobs.

### Running it

```bash
composer install
composer install -d apps/legacy-demo
composer install -d apps/starter-kernel

# terminal 1 — the push hub (a standalone daemon, never inside PHP-FPM)
php packages/core/push-hub/bin/hub.php

# terminal 2 — the simulated legacy site (bridge mode)
php -S 127.0.0.1:8000 -t apps/legacy-demo

# terminal 3 — the from-scratch app (kernel mode)
php -S 127.0.0.1:8001 -t apps/starter-kernel/public
```

Open both `http://127.0.0.1:8000/` and `http://127.0.0.1:8001/` — they show
the same order, because they share `var/demo.sqlite`. Click "Avançar status"
on either page and watch the badge update live on both, with no refresh.

Full manual verification checklist: [docs/phase-0-proof-of-concept.md](docs/phase-0-proof-of-concept.md).

### Quality gates

```bash
composer test      # PHPUnit across component-engine, orm, push-hub, typing-contracts
composer analyse   # PHPStan, level 8, on the framework's own source
```

## The TypeScript-equivalent pillar: `check`

Any project that requires `phpmodern/typing-contracts` gets a zero-config
strict-typing command — the phpmodern equivalent of `tsc --noEmit`:

```bash
vendor/bin/phpmodern-check path/to/your/components
```

It runs PHPStan at level 9 plus a custom rule
(`ComponentPropsMustBeTypedRule`) that bans untyped and `mixed` props on any
`Component` subclass — the direct analogue of forbidding `any` in strict
TypeScript — without requiring the consuming project to write any PHPStan
config of its own.

## Scaffolding: `make:component`

Kernel-mode projects (only — this is a dev-time convenience, not something
bridge mode needs to own) get a scaffolding command:

```bash
php vendor/bin/console make:component ProductCard
# or nested: php vendor/bin/console make:component Orders/StatusBadge
```

Generates a typed, escaping-by-default `Component` subclass under
`app/Components/` (subdirectories become sub-namespaces), refuses to
overwrite an existing file, and the result passes `phpmodern-check`
out of the box.

## Migrations

`phpmodern/orm` ships a minimal migration runner (`MigrationRunner`) plus two
console commands. A migration file just returns an anonymous class
implementing `Migration` (the same convention Laravel uses) — no separate
registry to maintain:

```bash
php vendor/bin/console migrate --dsn=sqlite:var/app.sqlite
php vendor/bin/console migrate:rollback --dsn=sqlite:var/app.sqlite
```

`--dsn` can also come from the `DATABASE_URL` environment variable; `--dir`
defaults to `database/migrations`. Applied migrations are tracked in a
`phpmodern_migrations` table; rollback reverts only the most recent one.

## Queues

`phpmodern/queue` is a database-backed job queue: push a job from any
request (no persistent connection needed on that side), a standalone worker
daemon processes it — the same "daemon lives outside PHP-FPM" pattern as
push-hub.

```bash
php vendor/bin/worker.php sqlite:var/app.sqlite   # or set DATABASE_URL
```

A job is a plain class implementing `Job` (`handle(): void`), hydrated via
named-argument unpacking from whatever payload was pushed — no serialization
format to configure. Failed jobs are marked `failed` with the exception
message for inspection; there is no retry/backoff policy in this version.

## Repository-wide gotcha this project hit (and fixed)

Every console script under `bin/` (hub, worker, check, kernel's console)
resolves `vendor/autoload.php` by searching upward from the **caller's**
current directory first, not from the script's own `__DIR__`. A Composer
path-repository install junctions these files into a consumer's `vendor/`
tree, so `__DIR__` can resolve to the file's physical location inside this
monorepo instead — an upward search from there would silently load the
monorepo's own autoloader instead of the consuming app's, hiding that app's
own classes (its job classes, its migrations). Caught by actually running
`worker.php` against a separate project instead of only unit-testing it.

## Hot reload

`phpmodern/dev-server` doesn't reinvent push — it reuses push-hub. A polling
file watcher (`FileWatcher`, no OS filesystem-events extension required)
detects a change and tells the hub to broadcast a `reload: true` signal
instead of a component morph; the same `client.js` already loaded on the
page calls `location.reload()` when it sees that flag.

```bash
php vendor/bin/watch.php app/Components 127.0.0.1:8081 __hmr__
```

Then any page that calls `connectPushChannel('__hmr__')` reloads the moment
a watched file changes. Verified with a real (headless) browser: the page's
`load` event count went from 1 to 2 the instant a watched file was touched,
with no manual refresh.

## State management

`phpmodern/store` is a Redux-shaped container sized for one PHP request
(there's no long-lived process to hold state across requests): seed it from
a DB read, register a reducer per action, dispatch, let listeners react.

```php
/** @var Store<array{quantity: int}> $store */
$store = new Store(['quantity' => $row['quantity']]);
$store->on('adjust', fn (array $s, array $p): array => ['quantity' => max(0, $s['quantity'] + $p['delta'])]);
$store->subscribe(fn (array $s) => $queryHelper->update('stock', $s, ['id' => 1])); // persist
$store->subscribe(fn (array $s) => $publisher->publish($channel, $id, mount(Counter::class, $s))); // push
$store->dispatch('adjust', ['delta' => 1]);
```

The reducer is a pure function (trivially unit-testable on its own); each
side effect of a dispatch — persisting, pushing — is a separate listener
instead of being hand-inlined one after another. Deployed for real in
`stock-adjust.php` in the showcase project, replacing the hand-rolled
"compute, persist, publish" sequence every other action in this repo still
uses; verified end to end that it produces byte-identical push output.

## Debug bar

`phpmodern/debugbar` is deliberately dependency-free — it doesn't know
`Component`, `Connection`, or `Store` exist. `DebugBar::time($label,
$callback)` wraps any callable and records how long it took; `DebugBar::note()`
logs an arbitrary line. Nothing elsewhere in the framework calls into it, so
instrumenting core packages never makes them depend on this one, and leaving
it disabled in production costs one boolean check.

```php
DebugBar::enable();
$html = DebugBar::time(OrderStatusBadge::class, fn () => mount(OrderStatusBadge::class, $props));
DebugBar::note(sprintf('%d comment(s) loaded', count($comments)));
echo DebugBar::render(); // a floating bar, or '' when disabled
```

Wired into the showcase project's `index.php` for real: the rendered bar
shows actual per-query and per-component timings (verified against the live
server — e.g. `StockCounter (Camiseta Azul)` at 0.47ms, 2.04ms total request
time), not placeholder numbers.

## Security: CSRF + headers

`phpmodern/security` delivers the "secure by default" promise from the
original pillar that shipped without it. There's no session yet (see the
roadmap below), so CSRF uses the double-submit-cookie pattern instead: a
random token goes out as a cookie, the page echoes it back as an
`X-CSRF-Token` header or form field, and a cross-origin attacker's page
can't read the cookie to forge a match.

```php
$nonce = SecurityHeaders::apply(['http://127.0.0.1:8081']); // the push-hub origin, for connect-src
$csrfToken = CsrfToken::issue();
// ...
<script nonce="<?= $nonce ?>">...</script>          // CSP has no 'unsafe-inline' for scripts
```

```php
if (!CsrfToken::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    exit;
}
```

The CSP nonces scripts strictly (no `unsafe-inline`) but allows
`'unsafe-inline'` for styles — every component in this repo uses inline
`style=""` — and explicitly lists the push-hub's origin in `connect-src`,
since it's a different port and CSP's `default-src 'self'` would otherwise
silently block the EventSource connection reactivity depends on. Fixing
that required removing `DebugBar`'s inline `onclick` (nonce-based CSP
doesn't cover inline event-handler attributes, only `<script>` tags) in
favor of a nonced `<script>` that attaches the listener instead.

Wired into all three demos (`legacy-demo`, `starter-kernel`, and the
showcase project) and verified for real: curl confirms a request with no
token or the wrong one gets 403 while the right one gets through, and a
headless-browser run of the showcase page found zero CSP violations on load
or on the click → fetch → push → morph round trip.

## Where this leaves Phase 0

Every item from the original roadmap sketch is now built and verified end to
end against real running processes (not just unit tests): dual-mode
reactivity with one engine, client-side DOM patching, strict typing with no
consumer-side config, scaffolding that produces already-compliant code,
migrations, a database-backed queue, hot reload built on the same push
primitive as component updates, Redux-shaped state management, and a
dependency-free debug bar. Phase 0 was explicitly a proof of concept, not a
production-ready framework — the gaps below are what stand between it and
that, in priority order.

## Authentication & sessions

`phpmodern/auth` is three small pieces, composed rather than one class doing
everything: `Session` (a testable `$_SESSION` wrapper), `PasswordHasher` (a
typed face on `password_hash`/`password_verify`), and `Auth` (login/logout/
id/check, regenerating the session id on every privilege change to prevent
session fixation). `Auth` never fetches a user record — that's the caller's
job, same as `Store`'s listeners.

```php
if (!Auth::check()) {
    // show a login form
}

// actions/login.php
if (PasswordHasher::verify($password, $user['password_hash'])) {
    Auth::login((int) $user['id']);
}

// a protected action
Auth::requireLogin(); // 401s and exits if nobody is logged in
```

Deployed for real in the showcase project: posting a comment now requires
login, and the author name is read server-side from the logged-in user's
own record — never trusted from client input, so nobody can post as someone
else. Verified end to end, including in a real headless browser: the login
form disappears and the comment form appears after logging in, a posted
comment shows up on the board via push, and curl confirms 401 both before
login and again after logout, with 204 in between.

## Input validation

`phpmodern/validation` replaces every action script's hand-rolled
`if ($x === '')` checks with composable, typed `Rule` objects — no
magic-string DSL ("`required|max:280`"), consistent with how this framework
treats strings-standing-in-for-types as a first principle everywhere else
(see `typing-contracts`). `Validator::validate()` runs each field's rules in
order, stopping at the first failure per field, and returns a
`ValidationResult` with structured `array<field, list<message>>` errors
instead of a bare status code.

```php
$validated = Validator::validate($input, [
    'product' => [new Required(), new StringType()],
    'delta' => [new Required(), new In([1, -1])],
]);
abort_if_invalid($validated); // 422 + {"errors": {...}} — the caller's own helper

$product = (string) $validated->get('product');
```

Deployed for real across all three of the showcase project's mutating
actions (stock adjustment, login, comment posting), replacing their ad hoc
checks. Verified end to end with curl: an out-of-range `delta` and an
over-length `message` both come back as `422` with the specific field and
reason (`"delta must be one of: 1, -1."`, `"message must be at most 280
characters."`) instead of a bare `400`.

## Phase 1 roadmap

1. ~~CSRF protection + security headers~~ — done (`phpmodern/security`).
2. ~~Authentication & sessions~~ — done (`phpmodern/auth`).
3. ~~Input validation~~ — done (`phpmodern/validation`, see above).
4. **A richer ORM** — no relationships (hasMany/belongsTo), no eager
   loading; anything beyond a single-table lookup means dropping to raw
   PDO today, with the classic N+1-query risk and no protection against it.
   Highest remaining priority.
5. **File-based routing for kernel mode** — routes are still a manual list
   in `routes/web.php`. A directory-convention router (the way Next.js
   resolves `pages/`) would remove that boilerplate as an app's route count
   grows. Lower priority — it's ergonomics, not a missing capability.

Deliberately not planned: an asset bundler (no-build-step is a chosen
differentiator, not a gap), a GraphQL/API layer, and i18n — none of them
were part of the "make PHP feel modern" thesis this project set out to test.

## Requirements

PHP 8.2+. No async runtime (Swoole/RoadRunner) is required — the push hub is
a plain PHP CLI daemon that talks HTTP to a proxy-friendly port, kept
deliberately separate from your existing PHP-FPM/Apache/Nginx setup.
