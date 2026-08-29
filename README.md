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

## Roadmap (not yet built)

A broader map of "modern-stack feature → PHP equivalent → feasibility" lives
in the project's planning notes and guides what gets built next, including:
a hot-reload dev server, state management, queues, and a debug bar. None of
this is committed to yet beyond Phase 0, which exists to validate the
riskiest assumptions (dual-mode reactivity with one engine, patched
efficiently on the client, strict typing enforced with no consumer-side
config, scaffolding that produces already-compliant code, and migrations
with no registry to maintain) before investing further.

## Requirements

PHP 8.2+. No async runtime (Swoole/RoadRunner) is required — the push hub is
a plain PHP CLI daemon that talks HTTP to a proxy-friendly port, kept
deliberately separate from your existing PHP-FPM/Apache/Nginx setup.
