---

name: implement-file-lifecycle
description: >
Implement or change file upload, storage, expiration, or deletion.
Use when touching file metadata, physical files, manual deletion,
automatic 24-hour expiration, or the shared deletion workflow.
Do not use for pure RabbitMQ configuration changes or unrelated Laravel features.
---------------------------------------------------------------------------------

# Implement File Lifecycle

Use this skill for changes affecting the lifecycle of uploaded files.

## Context

Before changing lifecycle behavior:

* read the relevant requirements in `docs/requirements.md`;
* read `docs/architecture.md` when changing the shared lifecycle workflow.

Do not invent behavior that is not defined by the requirements or existing architecture.

## Core Invariants

Manual deletion and automatic expiration must use the same application-level deletion workflow.

Every successful deletion, whether manual or automatic, must trigger the required RabbitMQ notification.

The application publishes the notification only. Do not implement SMTP or direct email delivery.

Keep database metadata and physical file storage consistent.

## Workflow

1. Identify the affected lifecycle stage:

    * upload;
    * metadata persistence;
    * physical storage;
    * expiration detection;
    * deletion;
    * post-deletion notification.

2. Inspect the existing lifecycle implementation and related tests.

3. Make the smallest change required.

4. Reuse the shared deletion workflow rather than duplicating deletion logic.

5. Keep HTTP controllers and scheduled commands as entry points, not owners of deletion business logic.

6. Keep RabbitMQ publishing behind the existing application-facing boundary.

7. Add or update tests for the changed behavior.

8. Run the relevant verification commands from `AGENTS.md`.

## Upload Changes

When changing uploads:

* keep server-side validation authoritative;
* preserve asynchronous upload behavior;
* persist the required metadata;
* use Laravel filesystem abstractions where appropriate;
* keep database and physical storage state consistent.

## Deletion Changes

When changing deletion behavior:

* use the shared deletion service/workflow;
* handle both the database record and physical file;
* publish the required notification after deletion;
* do not introduce direct email delivery.

Handle relevant inconsistent-state cases deliberately, such as metadata existing when the physical file is already missing.

## Completion

For lifecycle changes, verify the relevant cases:

* upload and validation;
* persistence;
* manual deletion;
* automatic expiration;
* shared deletion behavior;
* RabbitMQ publication;
* storage/database consistency.

Do not consider the change complete until the relevant checks from `AGENTS.md` have been run.
