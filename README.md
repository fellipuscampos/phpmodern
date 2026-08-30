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
  orm/                    Typed DB access: query helper, hasMany/belongsTo, migrations
  push-hub/               Standalone SSE daemon: server push, no client polling
  typing-contracts/       The check command + rule banning untyped/mixed props
  queue/                  Database-backed job queue + standalone worker daemon
  store/                  Redux-shaped state container (reducers + listeners)
  security/               CSRF (double-submit cookie) + default security headers
  auth/                   Session, PasswordHasher, and login/logout/check
  validation/             Typed Rule objects + structured per-field errors
  http/                   Request/Response objects + Middleware pipeline
  config/                 .env loader + typed environment-variable getters
  authorization/          Gate: policy registry answering "what can they do"
  mail/                   Message/Mailer (LogMailer for dev, real SmtpMailer)
packages/devtools/dev-server/  Polling file watcher that triggers hot reload via push-hub
packages/devtools/debugbar/    Request-scoped profiler: DebugBar::time() around any callable
packages/bridge/adapter/  Entry point for embedding into an existing PHP site
packages/framework/kernel/  Meta-package for greenfield projects (router, FileRouter, front controller, make:component)
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

## Relationships: hasMany / belongsTo

`phpmodern/orm` hydrates rows as plain arrays (never objects/active-record
magic — consistent with keeping data-shape explicit everywhere else in this
framework), so `Relations::hasMany()`/`belongsTo()` attach related rows
under a chosen array key rather than a magic property:

```php
$authors = $queryHelper->findMany('authors');
$authors = Relations::hasMany($queryHelper, $authors, 'id', 'books', 'author_id', 'books');
// $authors[0]['books'] is now that author's list of book rows
```

Each call issues exactly **one** extra query — `WHERE foreign_key IN (...)`
over the distinct keys — no matter how many parent rows there are, which is
the actual fix for N+1, not just a smaller constant. That claim is verified
by instrumentation, not just read from the source: a test installs a
counting `PDOStatement` subclass via `PDO::ATTR_STATEMENT_CLASS` and asserts
`hasMany()` creates exactly one statement.

Deployed for real in the showcase project: comments now store a `user_id`
foreign key instead of a denormalized author string, and the author shown
on the board is resolved via `belongsTo()` — one batched query regardless of
how many comments are on screen. Verified end to end: posting a comment
while logged in shows up with the right author name pulled through the
relation, and the `comments` table has no `author` column anymore.

## File-based routing

Kernel mode's `Router` first learned dynamic segments (`/orders/{id}`,
resolved via a compiled regex, exact-match routes still tried first) so
that `FileRouter` could sit on top of it: point it at a `pages/` directory
and it discovers GET routes the way Next.js resolves its own `pages/`
folder — `pages/about.php` → `/about`, `pages/orders/index.php` →
`/orders`, `pages/orders/[id].php` → `/orders/{id}`. A page file returns
either a plain string or a `callable(array $params): string` to receive its
dynamic segments; mutating actions stay explicit `$router->post(...)` calls
or their own script, the same page/action split the bridge-mode demos
already use.

```php
$router = new Router();
(require __DIR__ . '/../routes/web.php')($router, $connectionFactory); // manual routes, unchanged
(new FileRouter(__DIR__ . '/../pages'))->register($router);            // + everything under pages/
```

Deployed for real in `apps/starter-kernel`: added `pages/hello/[name].php`
with zero corresponding line in `routes/web.php`, and confirmed against the
running server that `/hello/Cleber` and `/hello/Mundo` both resolve through
the file's own `$params['name']`, while every pre-existing manually
registered route — including the CSRF-protected `POST /orders/42/advance`
— kept working unmodified.

## Phase 1 roadmap: complete

1. ~~CSRF protection + security headers~~ — done (`phpmodern/security`).
2. ~~Authentication & sessions~~ — done (`phpmodern/auth`).
3. ~~Input validation~~ — done (`phpmodern/validation`).
4. ~~A richer ORM (relationships, N+1 protection)~~ — done (`phpmodern/orm`).
5. ~~File-based routing for kernel mode~~ — done (`phpmodern/kernel`, see
   above).

Every item identified as a gap between the Phase 0 proof of concept and a
framework someone could actually build a real app on is now built and
verified end to end against real running processes.

Deliberately not planned: an asset bundler (no-build-step is a chosen
differentiator, not a gap), a GraphQL/API layer, and i18n — none of them
were part of the "make PHP feel modern" thesis this project set out to test.

## HTTP layer: Request, Response, Middleware, Pipeline

`phpmodern/http` is intentionally not tied to `Router` — it's usable from a
bridge-mode script exactly as well as a kernel route, since it's just
`Request::fromGlobals()` in, a `Response` out:

```php
$pipeline = new Pipeline([new CsrfMiddleware()]); // from phpmodern/security

$response = $pipeline->handle(Request::fromGlobals(), function (Request $request): Response {
    $validated = Validator::validate($request->json() ?? [], [...]);

    if ($validated->fails()) {
        return Response::json(['errors' => $validated->errors()], 422);
    }

    // ...do the work...

    return Response::noContent();
});

$response->send();
```

`Pipeline` composes Middleware "onion"-style — each layer decides whether
and how to call the next one in, down to the handler at the center.
`CsrfMiddleware` (in `phpmodern/security`) is the first real middleware:
the same check `require_valid_csrf_token()` did by hand, now a composable,
swappable pipeline stage instead of a bespoke function.

Deployed for real in the showcase project's `stock-adjust.php`: CSRF moved
into the pipeline, `http_response_code()`/`echo` calls became `Response`
objects. Verified end to end against the live server — a request with no
CSRF token, one with an invalid `delta`, and a valid one all still return
exactly the status codes they did before the refactor (403, 422, 204), and
the resulting push to the browser is unchanged.

## Configuration: `.env` + typed getters

`phpmodern/config` is two small pieces: `Env::load($path)` reads `KEY=VALUE`
lines from a `.env` file into the process environment (a real environment
variable — set by the OS, a container, or a CI secret — always wins over
the file), and `Config::string()/int()/bool()/has()` are typed reads over
whatever's there. No nesting, no caching — the smallest thing that replaces
scattered `getenv('X') ?: 'default'` calls with one discoverable API.

```php
Env::load(getcwd() . '/.env');
$dsn = Config::string('DATABASE_URL');
```

Deployed for real in `console migrate`/`migrate:rollback` and
`worker.php`, replacing their raw `getenv('DATABASE_URL')` calls. Verified
end to end in a throwaway project with no `--dsn` flag and no real
environment variable set at all: `console migrate` read `DATABASE_URL`
purely from a `.env` file and ran the migration against exactly that
database.

## Authorization: Gate

`phpmodern/authorization` answers "what can they do," the half `phpmodern/
auth` explicitly leaves out. `Gate` is a registry of policies — plain
callables returning bool, no roles table or migration needed for the common
case of "can this specific user act on this specific record":

```php
Gate::define('delete-comment', fn (?int $userId, array $comment): bool =>
    $userId !== null && $comment['user_id'] === $userId);

Gate::authorize('delete-comment', Auth::id(), $comment); // 403s and exits if denied
```

Deployed for real in the showcase project: comments can now be deleted, but
only by whoever posted them. Building this surfaced a real architecture
lesson, not just a feature: `CommentBoard`'s pushed HTML is identical for
every connected viewer (push-hub broadcasts one render to a channel), so a
first attempt at baking "is this my comment" into the server-rendered
component showed the delete button to the *wrong* people on every other
open tab. Fixed by always rendering the button with the comment's owner id
as a `data-user-id` attribute and letting the client decide visibility per
viewer (re-applied via a `MutationObserver` after every push) — the actual
security boundary stays entirely server-side in `Gate::authorize()`
regardless of what the button shows. Verified end to end: deleting while
logged out returns 401, deleting someone else's comment while logged in as
a different user returns 403 ("Forbidden."), and deleting your own comment
returns 204 and it's gone — confirmed in a real headless browser too, where
the delete button rendered visible on your own comment and `none` on
someone else's.

## Mail + the full account lifecycle

`phpmodern/mail` is `Message` + a `Mailer` interface, with two
implementations: `LogMailer` writes to a file instead of actually sending
(the sensible default for local development — the same idea as Laravel's
"log" driver), and `SmtpMailer` is a real minimal SMTP client over a plain
socket (EHLO, optional AUTH LOGIN, MAIL FROM/RCPT TO/DATA, no external
library, no STARTTLS/attachments). Swapping one for the other is a
one-line change; nothing else in a password-reset flow needs to know which
one is in use.

`SmtpMailer` was verified for real, not mocked: a local SMTP debug server
(Python's `aiosmtpd`) received a message sent through it with byte-correct
UTF-8 content and properly formed headers — confirmed by hex-dumping the
body before sending and comparing. A mock would only have proven the class
agrees with itself.

Deployed for real in the showcase project: `forgot-password.php` issues a
random single-use token (expires in 1 hour) and emails it via `LogMailer`
to `var/mail.log`, without revealing whether the address has an account
either way (a deliberate defense against user enumeration).
`reset-password.php` consumes the token to set a new password. Verified
end to end with curl, including edge cases: a fake token is rejected (400),
the real token succeeds (204) and the old password stops working (401)
while the new one works (204), and reusing the same (now-consumed) token
fails (400) — the single-use guarantee actually holds.

Registration and email verification round out the lifecycle on the same
foundation: `register.php` rejects a taken username/email (422, structured
per-field errors), otherwise creates the account, emails a 24-hour
verification token via the same `Mailer`, and logs the user in immediately
— verification is tracked and surfaced in the UI, not a gate that blocks
using the account. `verify-email.php` consumes that token the same way
password reset does. Verified end to end: a duplicate username is rejected,
a fresh registration auto-logs-in with `email_verified_at` still `NULL`
and an "unverified" banner showing, and submitting the emailed token clears
`email_verified_at` and makes the banner disappear on the next load. Login
rate-limiting is the one sub-item still open (needs a cache/counter store —
see the ORM/cache items below).

## Phase 2 roadmap: toward a complete framework

Phase 1 closed the gap between "proof of concept" and "an app could be built
on this." Phase 2 is the larger, longer gap between that and "a complete
framework" in the sense Laravel/Symfony/Rails are — this is realistically
months of work for a team, not something to build in one pass. Grouped by
what unblocks what:

1. **CI** — nothing runs the test suite or PHPStan automatically on a push;
   every green run so far has been manual. Building everything else on top
   of an unverified baseline compounds risk. Done first, before anything
   else in this phase.
2. ~~HTTP layer maturity~~ — partially done: `phpmodern/http` (Request,
   Response, Middleware, Pipeline — see below) exists and is used for real.
   Router/Kernel still use their original `callable(array $params): string`
   signature rather than Request/Response natively, and there are no named
   routes or route groups yet — migrating Router itself is follow-up work,
   not done in this pass.
3. ~~Configuration~~ — done (`phpmodern/config`, see below).
4. ~~Authorization~~ — done (`phpmodern/authorization`, see below).
5. ~~Full account lifecycle~~ — done, except login rate-limiting (left
   open, tracked separately below): registration, password reset, and
   email verification are all built and verified end to end in the
   showcase project on top of `phpmodern/mail` (see below).
6. **A more capable ORM** — transactions, soft deletes, automatic
   timestamps, pagination, richer queries than `WHERE ... IN (...)`, seeders.
7. **Observability** — a PSR-3 logger, a central exception handler (an
   uncaught exception today just breaks the page with no record of it
   anywhere), and a cache abstraction.
8. **A real CLI framework** — `bin/console` is still an if-chain; needs
   argument parsing, `--help`, and command registration as more commands
   accumulate.
9. **In-process application testing** — there's no HTTP test client; testing
   a route today means curling a real running server. A complete framework
   lets a test simulate a request in memory.
10. **Distribution** — no package has actually been published to Packagist
    or installed by anyone outside this project; no deployment story for
    running the hub/worker daemons as real supervised system services.

None of this is committed to yet beyond item 1 (CI), which is being built
now.

## Requirements

PHP 8.2+. No async runtime (Swoole/RoadRunner) is required — the push hub is
a plain PHP CLI daemon that talks HTTP to a proxy-friendly port, kept
deliberately separate from your existing PHP-FPM/Apache/Nginx setup.
