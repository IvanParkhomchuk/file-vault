# AGENTS.md

## Project Overview

File Lifecycle Manager is a Laravel application for temporary PDF and DOCX file storage.

It provides asynchronous file uploads, file management, automatic expiration, and deletion notifications published through RabbitMQ.

## Project Documentation

Use the relevant documentation when the task requires it:

* `docs/requirements.md` — source of truth for product behavior and task requirements.
* `docs/architecture.md` — application boundaries, workflows, and architectural decisions.
* `README.md` — local setup and usage instructions.
* `docs/ai-usage.md` — record of significant AI-assisted prompts and decisions.

Do not duplicate detailed product requirements in this file.

## Language

Use English for all repository text, including interface copy, accessibility labels, tests, code comments, and documentation.

## Required Stack

* PHP 8+
* Laravel
* MySQL
* RabbitMQ
* Bootstrap
* jQuery

Do not replace required technologies with alternatives unless explicitly requested.

## Repository Map

* `app/Http/` — controllers, middleware, and request validation.
* `app/Models/` — Eloquent models.
* `app/Services/` — reusable application and business logic.
* `app/Jobs/` — queued or background jobs, when applicable.
* `app/Console/` — scheduled and CLI behavior.
* `config/` — application and service configuration.
* `database/` — migrations, factories, and seeders.
* `resources/views/` — Blade templates and Bootstrap/jQuery UI.
* `routes/` — application routes.
* `tests/Feature/` — application behavior and integration tests.
* `tests/Unit/` — isolated unit tests.
* `docs/` — requirements, architecture, and AI usage documentation.
* `docker/`, `Dockerfile`, `compose.yaml` — container startup and local service orchestration.

Keep this map aligned with the actual repository structure.

## Commands

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Run database migrations:

```bash
php artisan migrate
```

Run the application locally:

```bash
php artisan serve
```

Run the frontend development build:

```bash
npm run dev
```

Run tests:

```bash
php artisan test
```

Check PHP formatting:

```bash
./vendor/bin/pint --test
```

Apply PHP formatting:

```bash
./vendor/bin/pint
```

Build frontend assets:

```bash
npm run build
```

Use repository-provided commands instead if the project setup later defines Docker or another execution environment.

Run the application with Docker after configuring `.env` as described in `README.md`:

```bash
docker compose up --build -d
```

Stop Docker services without removing persisted data:

```bash
docker compose down
```

## Architecture Rules

* Keep controllers focused on HTTP concerns and orchestration.
* Keep reusable business behavior outside controllers.
* Manual and automatic file deletion must use the same underlying deletion workflow.
* Keep RabbitMQ infrastructure isolated from controllers and presentation code.
* Keep configuration outside business logic.
* Prefer Laravel conventions before introducing custom abstractions.
* Avoid abstractions that add complexity without solving a concrete problem.
* Do not duplicate business rules across multiple execution paths.

For changes affecting application boundaries or workflows, consult `docs/architecture.md`.

## Frontend Rules

* Use Blade, Bootstrap, and jQuery.
* File uploads must remain asynchronous.
* Do not introduce Vue, React, Inertia, Livewire, or another frontend framework unless explicitly requested.
* Keep JavaScript focused on client-side interaction; business rules belong on the server.

## Database and Storage

* Use Laravel migrations for schema changes.
* Keep migrations compatible with MySQL.
* Use Laravel filesystem abstractions for file storage where appropriate.
* Do not hardcode filesystem paths, credentials, connection details, or environment-specific values.
* Keep file metadata and physical-file lifecycle consistent.

## RabbitMQ

* The application publishes deletion notifications to RabbitMQ.
* The application does not send email itself.
* RabbitMQ connection details and destination names must come from configuration/environment values.
* Do not hardcode RabbitMQ credentials or infrastructure configuration.
* Do not silently ignore message-publishing failures.

For product-level notification requirements, consult `docs/requirements.md`.

## Testing

Add or update tests when application behavior changes.

Prioritize coverage for:

* file validation and upload behavior;
* file metadata persistence;
* manual deletion;
* automatic expiration/deletion;
* shared deletion behavior;
* RabbitMQ publication;
* failure and edge cases relevant to the changed code.

Prefer behavior-focused tests over tests coupled to implementation details.

## Guardrails

* Never commit secrets, credentials, `.env`, or machine-specific configuration.
* Do not edit `.env` as part of repository changes; update `.env.example` when configuration requirements change.
* Do not add, remove, or upgrade dependencies unless required by the task.
* Do not change required technologies without explicit instruction.
* Do not change documented product behavior without explicit instruction.
* Do not implement SMTP or direct email delivery.
* Do not perform unrelated refactors while completing a focused task.
* Do not delete or rewrite existing tests merely to make a failing change pass.
* Do not weaken validation or error handling to bypass a failure.

## Verification

Before considering a code change complete, run the checks relevant to that change.

For backend changes:

```bash
./vendor/bin/pint --test
php artisan test
```

For frontend changes:

```bash
npm run build
```

For changes affecting both:

```bash
./vendor/bin/pint --test
php artisan test
npm run build
```

If a relevant verification command cannot be run, report the reason rather than assuming the change is valid.

## Documentation

Update documentation when a change affects:

* setup or execution;
* environment/configuration requirements;
* architecture or application boundaries;
* documented product behavior.

Keep `README.md`, `.env.example`, and relevant files under `docs/` consistent with the implementation.

## AI Usage

Record significant AI-assisted prompts and decisions in `docs/ai-usage.md` when they materially contribute to the implementation or architecture.
