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

The completed implementation will be validated against:

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

This keeps individual prompts focused on the task instead of repeatedly embedding the complete project specification.

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

*To be completed after implementation.*

### Developer Review

*To be completed after implementation.*

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

*To be completed after implementation.*

### Developer Review

*To be completed after implementation.*

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

*To be completed after implementation.*

### Developer Review

*To be completed after implementation.*

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

*To be completed after implementation.*

### Developer Review

*To be completed after implementation.*

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

*To be completed after final verification.*

### Developer Review

*To be completed after final verification.*

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

## Final AI Usage Summary

Complete this section before submission.

### AI Was Used For

* architecture assistance;
* implementation assistance;
* test generation/review;
* debugging;
* code review;
* documentation.

Remove items that were not actually used.

### Developer Responsibilities

The developer remained responsible for:

* interpreting the assignment;
* selecting the final architecture;
* reviewing generated code;
* rejecting unnecessary complexity;
* validating application behavior;
* executing tests and verification;
* ensuring the final implementation satisfies the assignment.

### Final Verification

Record the final commands and their outcomes here before submission.

```text
Command:
Result:

Command:
Result:

Command:
Result:
```
