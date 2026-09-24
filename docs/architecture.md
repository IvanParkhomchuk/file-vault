# Architecture

## 1. Overview

File Lifecycle Manager is a small Laravel application responsible for temporary PDF/DOCX storage and lifecycle management.

The architecture intentionally stays close to Laravel conventions and avoids unnecessary abstraction.

The main design goal is to keep file lifecycle behavior consistent regardless of whether deletion is initiated manually or automatically.

---

## 2. High-Level Flow

### Upload

```text
Browser
   │
   │ asynchronous request
   ▼
FileController
   │
   ▼
Request Validation
   │
   ▼
File Storage + Metadata Persistence
   │
   ├── Physical file
   │
   └── MySQL metadata
```

### Deletion

```text
Manual HTTP deletion ───────┐
                            │
                            ▼
                     Shared Deletion
                        Workflow
                            │
Scheduled expiration ───────┘
                            │
                            ├── Physical file deletion
                            ├── Database state update
                            └── Notification publication
                                      │
                                      ▼
                                  RabbitMQ
```

Manual and automatic deletion must not contain separate implementations of the same business behavior.

---

## 3. Main Components

### HTTP Layer

Responsible for:

* receiving requests;
* request validation;
* returning HTTP responses;
* invoking application behavior.

Controllers should remain thin and must not own reusable file lifecycle logic.

Typical location:

```text
app/Http/
```

---

### File Model

Represents metadata about an uploaded file.

The exact schema may evolve during implementation, but metadata should support the required file lifecycle.

Potential metadata includes:

* original filename;
* stored path or storage identifier;
* MIME type;
* size;
* upload timestamp;
* expiration information where needed.

Do not store physical file contents in the database.

The `stored_files` table holds the original name, Laravel filesystem disk and relative path, MIME type, byte size, upload time, and expiry time. Eloquent assigns `uploaded_at` on creation and sets `expires_at` exactly 24 hours later. The configured upload disk defaults to a private local disk outside the public web root. Each record retains its disk name so a later configuration change does not prevent deletion or retry of an existing file.

`POST /files` validates one multipart upload through Laravel's file rules for PDF/DOCX content, matching filename extension, and a 10 MiB maximum. The controller returns JSON with HTTP 201 on success or Laravel's HTTP 422 validation response. A small upload service writes a UUID-named file through the configured disk before creating its metadata record. If metadata creation fails, it deletes the newly written file and propagates the failure. Uploads run in the request; no background job is needed for this workflow.

The record also has nullable `deleted_at` and `deletion_source` fields. They preserve the original notification details while publication is pending. A record is removed only after the broker confirms publication.

---

### File Storage

Physical files should be managed through Laravel's filesystem abstraction.

Application code should not depend on hardcoded local filesystem paths.

This allows storage behavior to remain centralized and testable.

---

## 4. Shared Deletion Workflow

File deletion is the primary business workflow in the application.

Both entry points:

```text
manual deletion
automatic expiration
```

must invoke the same application-level deletion behavior.

The shared workflow is responsible for coordinating:

1. the physical file;
2. persisted metadata;
3. deletion notification publication.

This prevents manual and scheduled deletion from evolving different behavior.

### Suggested Responsibility

A small service such as:

```text
FileDeletionService
```

may own this workflow.

The exact class name is not an architectural requirement.

The important requirement is that deletion behavior has a single application-level owner.

`DeleteStoredFile` locks the metadata row, checks the recorded disk and path, removes an existing physical file, records the deletion time and source, publishes the notification, and removes the metadata only after broker confirmation. The lock serializes requests for the same record. A second request after completion receives HTTP 404 and does not publish again.

If the physical file is already absent while metadata exists, the workflow treats it as an incomplete deletion: it publishes the notification and removes the metadata. The manual endpoint reports that the file was already missing. A filesystem check or deletion failure keeps the metadata and does not publish. After physical removal, a RabbitMQ failure returns HTTP 503 and keeps the record with `deleted_at` and `deletion_source`; a later call to the same workflow retries publication without needing the physical file. The management page keeps that record visible so the user can retry. The eventual expiration command can retry the same record through this workflow. This is a retry-on-next-invocation scheme, without a separate outbox or worker.

The database transaction commits the pending notification state even when publication fails. It cannot atomically commit a filesystem deletion and broker confirmation. If metadata persistence fails after physical removal, the record still exists and a retry can reconcile the missing file. If the broker accepted a message but its confirmation was lost, or if removing metadata fails after confirmation, a retry can publish a duplicate. The consumer should deduplicate using the file ID and deletion time when it needs exactly-once effects.

---

## 5. Manual Deletion

Manual deletion originates from the HTTP layer.

Expected flow:

```text
HTTP request
   ↓
Controller
   ↓
Shared deletion workflow
   ↓
Storage + database + notification
```

The controller should not manually delete the physical file or publish RabbitMQ messages itself.

---

## 6. Automatic Expiration

Automatic deletion is driven by Laravel's scheduling/console capabilities.

Expected flow:

```text
Laravel Scheduler
   ↓
Expiration command
   ↓
Find expired files
   ↓
Shared deletion workflow
```

The expiration command is responsible for discovering files that must be removed.

It should delegate actual deletion behavior to the same workflow used by manual deletion.

The expiration rule comes from `docs/requirements.md`.

---

## 7. RabbitMQ Boundary

RabbitMQ is an infrastructure concern.

Application lifecycle code should not directly depend on RabbitMQ client details throughout the codebase.

Prefer a small application-facing publishing boundary, for example:

```text
DeletionNotificationPublisher
```

with a RabbitMQ-backed implementation.

Conceptually:

```text
FileDeletionService
        │
        ▼
DeletionNotificationPublisher
        │
        ▼
RabbitMQ implementation
```

This is intentionally a small abstraction.

Do not introduce a generic event bus or messaging framework for this project.

The publisher sends one persistent JSON message to the configured durable RabbitMQ queue. The message has `type: "file.deleted"`, `version: 1`, `recipient_email`, `file_id`, `original_name`, `deleted_at` (ISO 8601), and `deletion_source` (`manual` or `expiration`). The external consumer uses these fields to address and describe the email; this application does not send it.

The publisher waits for a broker confirmation before returning. A connection, routing, or confirmation failure raises a publication exception; it is never treated as a successful deletion. The future shared deletion workflow must keep the metadata record until publication is confirmed so a failed attempt remains visible and retryable. If a confirmation is lost after the broker accepted a message, a retry can publish a duplicate. The publisher alone does not provide atomicity across storage, database, and RabbitMQ.

---

## 8. Notification Responsibility

The application publishes the required deletion notification.

It does not deliver the email.

Conceptually:

```text
Application
    │
    ▼
RabbitMQ
    │
    ▼
External consumer
    │
    ▼
Email delivery
```

Only the first two parts belong to this project.

SMTP and email provider infrastructure remain outside the application boundary.

---

## 9. Configuration

Environment-specific values must flow through Laravel configuration.

Examples include:

* database connection;
* RabbitMQ connection;
* RabbitMQ destination configuration;
* notification recipient;
* filesystem configuration.

Application services should read configuration through Laravel configuration rather than calling environment variables directly throughout business logic.

Required environment variables must be documented in `.env.example`.

Real credentials must never be committed.

---

## 10. Frontend

The required frontend stack is:

* Blade;
* Bootstrap;
* jQuery.

File upload must be asynchronous.

The frontend is responsible for interaction and presentation only.

Validation and business rules remain authoritative on the server.

Do not introduce Vue, React, Inertia, Livewire, or another frontend framework unless requirements change.

---

## 11. Database

MySQL is the required database.

Schema changes must be managed through Laravel migrations.

Database design should remain minimal and directly support required application behavior.

Do not add tables or relationships without a concrete requirement.

---

## 12. Testing Strategy

Tests should focus on observable behavior and important boundaries.

### Upload

Verify:

* supported file upload;
* unsupported file rejection;
* size validation;
* metadata persistence;
* physical storage.

### Manual Deletion

Verify:

* deletion behavior;
* storage/database consistency;
* notification publication.

### Automatic Expiration

Verify:

* expired files are discovered;
* non-expired files remain untouched;
* expired files use the shared deletion workflow;
* notification publication occurs.

### RabbitMQ Boundary

Normal application tests should not require a real RabbitMQ instance.

Use the application-facing publisher abstraction as the test boundary.

A real-broker integration test may be added only when it provides clear value.

---

## 13. Failure Handling

Failures must not be silently ignored.

Particular attention should be given to partial lifecycle states, for example:

```text
database record exists
physical file is missing
```

or:

```text
file deletion succeeds
notification publication fails
```

The implementation should handle such states deliberately and make failures observable.

The architecture does not prescribe a distributed transaction between filesystem, database, and RabbitMQ.

Avoid introducing complex reliability infrastructure unless required by the assignment.

---

## 14. Architecture Principles

Use the following principles when making implementation decisions:

* prefer Laravel conventions;
* keep controllers thin;
* keep lifecycle behavior centralized;
* avoid duplicated business rules;
* isolate infrastructure-specific code;
* prefer explicit code over premature abstraction;
* introduce abstractions only when they protect a real application boundary;
* keep the solution proportional to the size of the assignment.

When two designs satisfy the requirements, prefer the simpler design that preserves these invariants.

---

## 15. Architectural Invariants

The following must remain true as the implementation evolves:

1. Manual and automatic deletion use the same application-level deletion workflow.
2. Every required successful deletion triggers RabbitMQ publication.
3. The application does not send email directly.
4. Database metadata and physical storage are managed as one lifecycle.
5. Controllers do not own reusable deletion behavior.
6. RabbitMQ client details do not leak throughout application code.
7. Required infrastructure configuration is environment-driven.
8. Required product behavior remains defined by `docs/requirements.md`.
