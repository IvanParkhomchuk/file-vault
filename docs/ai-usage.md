# AI Usage

## Purpose

This document records significant AI-assisted work performed during development of the project.

The goal is to provide transparency about:

* which prompts were used;
* how important prompts were structured;
* why particular instructions were included;
* how AI output contributed to the final result;
* which decisions were reviewed or changed by the developer.

AI was used as a development assistant, not as the source of truth.

The implementation was reviewed against:

* `docs/requirements.md`;
* `docs/architecture.md`;
* automated tests;
* repository verification commands.

---

## AI-Assisted Development Approach

The project uses repository-level instructions and focused reusable skills to provide stable context to the coding agent.

```text
AGENTS.md
    │
    ├── repository conventions
    ├── architecture guardrails
    ├── runnable commands
    └── verification rules

agents/skills/
    │
    ├── implement-file-lifecycle
    ├── integrate-rabbitmq
    └── verify-change

docs/
    │
    ├── requirements.md
    └── architecture.md
```

This describes the original prompt structure. The final verification recorded below used the repository instructions and documentation directly; use of those named local skills was not verified in that session.

---

## Prompt Design Principles

Significant prompts follow these principles where appropriate.

### 1. Provide the Goal

The prompt states the concrete task to perform.

Example:

```text
Implement manual file deletion.
```

### 2. Provide Relevant Context

Stable project context is primarily supplied through `AGENTS.md`, skills, and repository documentation rather than duplicated into every prompt.

### 3. Define Constraints

Important constraints are stated when they materially affect the task.

Examples:

```text
Reuse the shared deletion workflow.
Do not implement direct email delivery.
Do not introduce a new frontend framework.
```

### 4. Define Verification

Implementation prompts should request appropriate verification after the change.

Example:

```text
After implementation, verify the change using the repository verification workflow.
```

### 5. Avoid Prescribing Unnecessary Implementation

Prompts define required behavior and architectural invariants without forcing class names or abstractions unless there is a concrete reason.

This allows the coding agent to inspect the existing codebase and choose the smallest compatible implementation.

---

# Prompt Records

Only prompts that materially contributed to implementation, architecture, debugging, or verification need to be recorded.

Minor conversational prompts do not need separate entries unless they affected the final result.

---

## Prompt 1 — Project Context and Agent Instructions

### Goal

Create repository-level instructions that allow an AI coding agent to work consistently within the project.

### Prompt

```text
Analyze the project requirements and create repository-level AGENTS.md
instructions for an AI coding agent.

Keep the file concise.

Include:
- project overview;
- repository map;
- exact runnable commands;
- architecture rules;
- frontend constraints;
- testing expectations;
- safety/guardrails;
- verification commands.

Do not duplicate the complete product specification.
Treat docs/requirements.md as the source of truth for product behavior.

Prefer Laravel conventions and avoid unnecessary abstractions.
```

### Why This Prompt Was Structured This Way

The prompt separates stable repository instructions from detailed product requirements.

Exact commands were requested so the coding agent can execute verification rather than interpret vague instructions such as "run tests".

The prompt also explicitly asks for architectural guardrails without prescribing unnecessary implementation details.

### AI Contribution

AI proposed the initial structure and wording for `AGENTS.md`.

### Developer Review

The generated instructions were reviewed and reduced to project-specific rules.

Generic Laravel advice and duplicated product requirements were removed.

---

## Prompt 2 — File Lifecycle Architecture

### Goal

Design the smallest architecture that supports both manual and automatic deletion consistently.

### Prompt

```text
Analyze the file lifecycle requirements.

Design a minimal Laravel architecture for:

- asynchronous file upload;
- database metadata;
- physical file storage;
- manual deletion;
- automatic deletion after the retention period;
- RabbitMQ notification after deletion.

The key invariant is that manual deletion and automatic expiration
must use the same underlying deletion workflow.

Prefer Laravel conventions.

Do not introduce repositories, domain layers, event buses, or other
abstractions unless they solve a concrete problem in this project.

Do not implement email delivery.
```

### Why This Prompt Was Structured This Way

The shared deletion invariant was stated explicitly because it is the central architectural requirement of the implementation.

Negative instructions were included to prevent unnecessary architecture for a small test assignment.

### AI Contribution

AI proposed centralizing deletion behavior into a reusable application-level workflow used by both HTTP and scheduled entry points.

It also proposed isolating RabbitMQ publication behind a small application-facing boundary.

### Developer Review

The shared workflow approach was accepted.

Generic messaging infrastructure and additional architectural layers were intentionally rejected as unnecessary.

---

## Prompt 3 — File Upload

### Goal

Implement the asynchronous file-upload workflow according to the assignment.

### Prompt

```text
Implement asynchronous file upload according to docs/requirements.md.

Use the existing Laravel architecture and repository conventions.

Requirements relevant to this task include:

- supported PDF/DOCX files;
- server-side validation;
- configured file-size limit;
- metadata persistence;
- physical file storage;
- Bootstrap + jQuery frontend;
- asynchronous request.

Use Laravel filesystem abstractions.

Do not introduce a frontend framework.

Add or update tests for the changed behavior.

After implementation, run the relevant verification workflow.
```

### Why This Prompt Was Structured This Way

Only requirements relevant to upload were repeated.

The prompt explicitly preserves the required frontend stack and asks for server-side validation so client behavior does not become authoritative.

### AI Contribution

No separate execution outcome is recorded for this earlier prompt. The implemented upload path is described under Prompt 11.

### Developer Review

No separate developer review outcome is recorded for this prompt.

---

## Prompt 4 — Manual Deletion

### Goal

Implement manual file deletion without duplicating lifecycle logic.

### Prompt

```text
Implement manual deletion for uploaded files.

Use the file lifecycle requirements and existing architecture.

The HTTP controller must delegate deletion to the shared application-level
deletion workflow.

A successful deletion must:

- keep physical storage and database state consistent;
- trigger the required RabbitMQ publication.

Do not publish directly from the controller.
Do not implement SMTP or direct email delivery.

Add or update tests.

After implementation, use the verify-change workflow.
```

### Why This Prompt Was Structured This Way

The prompt describes required behavior while preserving the central shared-deletion architectural invariant.

The controller-specific negative instruction prevents business and infrastructure logic from being placed directly in the HTTP layer.

### AI Contribution

No separate execution outcome is recorded for this earlier prompt. The implemented deletion path is described under Prompt 14.

### Developer Review

No separate developer review outcome is recorded for this prompt.

---

## Prompt 5 — Automatic Expiration

### Goal

Implement automatic deletion after the configured retention period.

### Prompt

```text
Implement automatic expiration of uploaded files according to
docs/requirements.md.

Use Laravel's scheduling/console capabilities.

The scheduled entry point should identify expired files and delegate
each deletion to the same application-level workflow used by manual deletion.

Do not duplicate file deletion or RabbitMQ publication logic inside
the scheduled command.

Add tests covering:

- expired files;
- non-expired files;
- shared deletion behavior;
- notification publication.

Run the relevant verification workflow after implementation.
```

### Why This Prompt Was Structured This Way

The prompt separates expiration detection from deletion execution.

This prevents the scheduled implementation from becoming a second independent deletion path.

### AI Contribution

No separate execution outcome is recorded for this earlier prompt. The implemented expiration path is described under Prompt 15.

### Developer Review

No separate developer review outcome is recorded for this prompt.

---

## Prompt 6 — RabbitMQ Integration

### Goal

Implement the required RabbitMQ publication without introducing unnecessary messaging infrastructure.

### Prompt

```text
Implement the RabbitMQ deletion-notification boundary.

Follow docs/requirements.md and docs/architecture.md.

Constraints:

- the application publishes deletion notifications only;
- it does not send email;
- connection and destination configuration must come from Laravel configuration;
- no credentials may be hardcoded;
- use a thin application-facing publishing abstraction;
- do not create a generic messaging framework;
- publishing failures must not be silently ignored.

Normal automated tests should not require a live RabbitMQ broker.

Update .env.example if new configuration values are introduced.

Run the relevant verification workflow.
```

### Why This Prompt Was Structured This Way

RabbitMQ is an infrastructure boundary, so the prompt explicitly separates application responsibility from broker implementation details.

The negative instruction against a generic messaging framework keeps the solution proportional to the task.

### AI Contribution

No separate execution outcome is recorded for this earlier prompt. The publisher implementation is described under Prompt 13.

### Developer Review

No separate developer review outcome is recorded for this prompt.

---

## Prompt 7 — Final Verification

### Goal

Review the completed solution against requirements and repository standards.

### Prompt

```text
Perform a final verification of the implementation.

Use the verify-change skill.

Review the complete diff and compare behavior with docs/requirements.md
and architecture with docs/architecture.md.

Pay particular attention to:

- asynchronous PDF/DOCX upload;
- file-size validation;
- metadata persistence;
- manual deletion;
- automatic 24-hour expiration;
- shared deletion workflow;
- RabbitMQ publication after both deletion paths;
- absence of direct email delivery;
- Bootstrap/jQuery frontend requirement;
- configuration and secret handling;
- test coverage.

Run the exact verification commands from AGENTS.md.

Do not report success for any check that was not actually executed.

Report:
- commands executed;
- failures found;
- changes made;
- checks that could not be completed.
```

### Why This Prompt Was Structured This Way

The final prompt intentionally focuses on observable requirements and project invariants rather than code style alone.

It explicitly prevents the AI from claiming verification that was not performed.

### AI Contribution

The final review and documentation updates are recorded under Prompt 16.

### Developer Review

No independent developer review outcome was provided in the final verification session.

---

# Recording Additional Prompts

For additional significant prompts, use the following format.

## Prompt N — Title

### Goal

What problem was being solved?

### Prompt

```text
Exact prompt used.
```

### Why This Prompt Was Structured This Way

Explain:

* why the context was included;
* why particular constraints were added;
* what failure or ambiguity the instructions were intended to prevent.

### AI Contribution

Describe what the AI proposed, generated, identified, or changed.

### Developer Review

Describe:

* what was accepted;
* what was changed;
* what was rejected;
* how the result was verified.

---

## Prompt 8 — Laravel 13 Project Setup and Delivery Plan

### Goal

Create the project foundation and plan feature delivery without implementing the features yet.

### Prompt

```text
Analyze the docs specification in detail. Set up a Laravel 13 project with
the specified dependencies, without Docker. Prepare an ordered plan from
an empty project to the complete assignment. Do not implement features yet.
```

### Why This Prompt Was Structured This Way

The prompt limits code changes to setup and asks for the feature sequence separately. It explicitly selects Laravel 13 and excludes Docker for this stage.

### AI Contribution

AI checked the requirements against the original assignment, created a Laravel 13 project, installed `php-amqplib/php-amqplib`, Bootstrap, jQuery, and the local RabbitMQ server, set MySQL defaults in `.env.example`, replaced the unused Tailwind starter page with a minimal Bootstrap page, and wrote the implementation plan and setup instructions.

### Developer Review

Pending developer review. Dependency installation, Composer validation, PHP formatting check, and the asset build completed successfully. MySQL is installed but its local server is not running. Product workflows remain unimplemented.

---

## Prompt 9 — Docker Setup

### Goal

Make the existing Laravel foundation runnable through Docker Compose.

### Prompt

```text
Add Docker so the project can be deployed and started with it.
```

### Why This Prompt Was Structured This Way

The prompt extends the previously completed project setup. The documented stack and feature boundaries remain the source of truth.

### AI Contribution

AI added a PHP 8.4 application image with Vite assets, MySQL, RabbitMQ, and Laravel Scheduler Compose services, persistent volumes, health checks, startup migrations, and Docker setup instructions.

### Developer Review

Pending developer review. Compose configuration, image build, service health, database migration, scheduler startup, persistent application key sharing, and an HTTP 200 response were checked with temporary local configuration. The temporary containers were stopped afterward.

---

## Prompt 10 — File Metadata Foundation

### Goal

Add the smallest private-storage metadata foundation for PDF/DOCX uploads.

### Prompt

```text
Read AGENTS.md, docs/requirements.md, and docs/architecture.md, then inspect the current repository state.

Prepare the minimum foundation for storing PDF/DOCX files:
- add a MySQL-compatible migration and an Eloquent metadata model;
- include the original name, Laravel Filesystem identifier or path, type, size, upload time, and end of the 24-hour retention period;
- choose the storage disk through configuration; files must not be directly accessible from the public web root;
- do not store file contents in the database.

First, check which fields are needed for upload, listing, deletion, and retrying failures. Do not add tables, statuses, or abstractions without a concrete need. Do not implement HTTP endpoints or an interface at this stage.

Add meaningful model and schema tests if they verify behavior rather than repeat the migration structure. Run the relevant checks from AGENTS.md. Summarize decisions, changed files, command results, and open risks.
```

### Why This Prompt Was Structured This Way

The prompt bounded the work to persistence and required a review of retry needs before adding state or tables.

### AI Contribution

AI added the metadata schema, automatic expiration timestamps, a private upload disk setting, and tests for persistence, expiration boundary, and storage configuration.

### Developer Review

Pending developer review. The schema omits deletion status and stores the original disk to support later retries when configuration changes. Verification results are reported with this change.

---

## Prompt 11 — Server-Side File Upload

### Goal

Implement the HTTP upload endpoint using the existing metadata model and private disk.

### Prompt

```text
Implement an HTTP endpoint for one PDF or DOCX upload. Validate content type and
the 10 MB limit on the server, store it under a safe generated name, persist
metadata with 24-hour expiration, return asynchronous-friendly HTTP responses,
and clean up the physical file if metadata persistence fails. Keep the controller
short, do not add an upload queue or unrelated file operations, and test the
required success and failure paths. Run pint --test and php artisan test.
```

### Why This Prompt Was Structured This Way

It limits the work to server upload behavior and calls out the partial-write case, which spans filesystem and database operations.

### AI Contribution

AI added a thin upload controller, a service coordinating storage and metadata, content-aware Laravel validation, and behavioral tests for accepted files, rejected content and size, persistence, and cleanup.

### Developer Review

Pending developer review. Verification outcomes are reported with the implementation.

---

## Prompt 12 — File Management Page

### Goal

Build one file management page with asynchronous upload and a list of saved files.

### Prompt

```text
Read AGENTS.md and docs/requirements.md, then inspect the existing routes, Blade, CSS, and JavaScript.
Build one dedicated file management page with Blade, Bootstrap, and jQuery:
show stored files with useful metadata; upload files asynchronously without reloading the page; refresh the list after success; show understandable validation and network errors; prepare manual deletion for an endpoint in the next step, or connect it if the endpoint already exists. The server remains the source of validation rules. Do not add a frontend framework, authorization, editing, preview, or download. Add useful HTTP behavior tests and run the relevant checks.
```

### Why This Prompt Was Structured This Way

The prompt limits the interface to the required stack and preserves server validation. It explicitly allows a disabled deletion control because the deletion workflow belongs to a later stage.

### AI Contribution

AI added the Blade management page, a server-rendered list endpoint for refresh after upload, jQuery upload and error handling, HTTP tests, and updated usage instructions.

### Developer Review

Pending developer review. The deletion endpoint did not exist, so the control remains disabled. Verification results accompany this change.

---

## Prompt 13 — RabbitMQ Deletion Publication Boundary

### Goal

Implement only deletion notification publication, leaving deletion workflows and email delivery for their respective owners.

### Prompt

```text
Read AGENTS.md, requirements, architecture, and current code. Document the
message format and publication-failure rule first. Add a small testable
php-amqplib publisher, Laravel configuration and .env.example settings,
and broker-free tests for configuration, payload, and failure. Run Pint and tests.
```

### Why This Prompt Was Structured This Way

It keeps the RabbitMQ integration narrow and makes broker confirmation and failure behavior explicit before implementation.

### AI Contribution

AI documented the JSON contract and failure rule, added the publisher interface and RabbitMQ implementation, configured its queue and recipient, and added broker-free tests.

### Developer Review

Pending developer review. Manual and scheduled deletion have not yet been implemented, so the publisher is not called by those paths yet.

---

## Prompt 14 — Shared Deletion and Manual Action

### Goal

Implement one deletion workflow and connect it to the management page.

### Prompt

```text
Read the requirements, architecture, and RabbitMQ decision. Implement one
workflow for filesystem removal, metadata, and publication. Add manual HTTP
deletion and UI interaction. Handle repeat requests, missing physical files,
filesystem failure, and RabbitMQ failure after removal. Preserve recovery
details, test those paths, and run formatting, tests, and the frontend build.
```

### Why This Prompt Was Structured This Way

It makes the partial failure behavior explicit and keeps the controller thin while the future expiration path can reuse the same service.

### AI Contribution

AI selected a metadata-backed retry scheme, implemented the shared service, endpoint, interface interaction, tests, and architecture notes. The metadata row remains until publication is confirmed, with the original deletion time and source saved for retries.

### Developer Review

Pending developer review.

---

## Prompt 15 — Automatic Expiration

### Goal

Run the shared deletion workflow after the stored 24-hour expiration time and recover pending publications.

### Prompt

```text
Read AGENTS.md, requirements, architecture, and the shared deletion implementation.
Add a Laravel command that finds expired files by their stored expiration time,
uses the manual endpoint's deletion workflow, and runs through Scheduler.
Make repeated runs safe, retry unfinished publications, process records in batches,
and keep going when one record fails. Test the time boundary, retries, failures,
and publication. Run Pint and the test suite.
```

### Why This Prompt Was Structured This Way

The prompt ties expiration to persisted metadata and the existing retry scheme. It explicitly requires per-record failure isolation and reuse of the deletion workflow.

### AI Contribution

AI added a batched expiration command, scheduled it every minute, and added behavioral tests for boundary timing, publication, retries, and failure isolation.

### Developer Review

Pending developer review. Verification outcomes accompany this change.

---

## Prompt 16 — Final End-to-End Verification and Documentation

### Goal

Check the finished implementation against the assignment and project documents, run the required commands, exercise live infrastructure where available, and correct stale documentation.

### Prompt

```text
Наскрізна перевірка і документація

Проведи завершальну перевірку реалізації за Test_task__Middle_PHP.docx, AGENTS.md, docs/requirements.md і docs/architecture.md. Не змінюй вимоги та не роби сторонніх
рефакторингів.

Перевір повний сценарій:
1. асинхронне завантаження PDF і DOCX;
2. відхилення недопустимого типу та файлу понад 10 МБ;
3. відображення списку;
4. ручне видалення і RabbitMQ повідомлення;
5. автоматичне видалення після 24 годин і RabbitMQ повідомлення;
6. відновлення після помилки публікації;
7. відсутність прямого надсилання email.

Перевір узгодженість README.md, .env.example, docs/architecture.md, docs/implementation-plan.md і docs/ai-usage.md з реальною реалізацією. Запиши в docs/ai-usage.md
суттєві використані промпти, рішення, внесок AI і результат перевірки розробником — без вигаданих тверджень.

Запусти ./vendor/bin/pint --test, php artisan test і npm run build. Якщо середовище дозволяє, окремо перевір інтеграцію з MySQL і RabbitMQ через Docker Compose. Не
позначай перевірку успішною, якщо її не запускав. Наприкінці дай коротку матрицю «вимога → реалізація → доказ перевірки», перелік змінених файлів і залишкових
обмежень.
```

### Why This Prompt Was Structured This Way

It names the assignment and product documents, specifies every lifecycle path, requires the repository verification commands, and separates live integration evidence from ordinary automated tests. The prohibition on invented results keeps the review record factual.

### AI Contribution

AI compared the assignment and current code, identified stale setup and plan text, updated `README.md`, `.env.example`, `docs/architecture.md`, and `docs/implementation-plan.md`, and ran the checks recorded below. It rebuilt the stale Docker application image before running an isolated integration probe. The probe created temporary records on MySQL and used a unique RabbitMQ queue for manual deletion, failed publication followed by retry, and an expired-file command run. It consumed the probe messages and removed its records, files, and queue.

### Developer Review

No independent developer review result was provided in this session. The checks below were executed by the AI agent; they should not be described as developer-executed verification.

---

## Final AI Usage Summary

### AI Was Used For

* architecture and implementation assistance recorded in Prompts 1–15;
* test generation and review recorded in the implementation history;
* debugging the stale Docker image and reviewing the final code and documentation;
* documentation updates and final verification recorded in Prompt 16.

### Developer Responsibilities

The developer remains responsible for:

* interpreting the assignment;
* selecting the final architecture;
* reviewing generated code;
* accepting or rejecting implementation decisions;
* independently validating application behavior and verification evidence;
* ensuring the final implementation satisfies the assignment.

### Final Verification

On 2026-09-24, the AI agent ran:

| Check | Result |
| --- | --- |
| `./vendor/bin/pint --test` | Passed. |
| `php artisan test` | Passed: 25 tests, 156 assertions. The test suite uses SQLite and a mocked RabbitMQ publishing boundary. |
| `npm run build` | Passed; Vite generated the production assets. |
| `docker compose up --build -d` | Passed after discovering the previous container image lacked the current deletion code. The app, MySQL, and RabbitMQ reported healthy; scheduler was running. |
| Isolated probe inside the rebuilt app container | Passed against MySQL and RabbitMQ: manual deletion, broker message payload, retained metadata after a deliberately invalid recipient, successful retry with the original deletion time, and `files:delete-expired` with an expired test record and broker message. |
| Source review | The Blade/jQuery interface submits upload and deletion requests asynchronously; server validation rejects unsupported content and files over the 10 MiB limit; application code has no direct mail sending call. This is code review, not a browser interaction or SMTP test. |

The live probe used a temporary queue and cleaned up its records and files. The scheduler's minute-by-minute timing and an actual broker outage were not exercised. The failure probe used an invalid recipient to trigger the publication error path. No independent developer review outcome is recorded.
