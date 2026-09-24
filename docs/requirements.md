# Requirements

## Purpose

This document is the source of truth for the product behavior required by the test assignment.

It describes **what** the application must do.

Implementation and architectural decisions belong in `docs/architecture.md`.

---

## 1. Application Purpose

The application stores PDF and DOCX files for a limited retention period.

Users must be able to upload files through the web interface, view uploaded files, and delete them manually.

Files must also be deleted automatically after the required retention period.

---

## 2. File Upload

The application must:

* accept PDF and DOCX files;
* upload files asynchronously through the web interface;
* enforce a file-size limit of 10 MB;
* store information about uploaded files in the database.

Upload validation must be enforced server-side.

---

## 3. File Management

The application must provide a dedicated page for managing uploaded files.

Users must be able to:

* view the list of uploaded files;
* delete uploaded files manually.

---

## 4. Automatic Expiration

Uploaded files must automatically expire and be deleted 24 hours after upload.

The expiration mechanism must work without requiring user interaction.

---

## 5. Deletion Notification

Every successful file deletion must result in a notification being published through RabbitMQ.

This applies to:

* manual deletion;
* automatic deletion after the 24-hour retention period.

The notification must target the email address configured through the application environment.

The application is responsible only for publishing the required RabbitMQ message.

Direct email delivery is outside the scope of this project.

Do not implement:

* SMTP delivery;
* direct email sending;
* an external email provider integration.

---

## 6. Required Technology Stack

The required stack is:

### Backend

* PHP 8+
* Laravel
* MySQL
* RabbitMQ

### Frontend

* Bootstrap
* jQuery

Required technologies must not be replaced with alternatives unless the assignment is explicitly changed.

---

## 7. Optional Technology

Docker and Docker Compose are optional according to the assignment and may be included as an improvement to local development and project setup.

---

## 8. Expected Deliverables

The completed project must:

* be stored in a Git repository such as GitHub, GitLab, or Bitbucket;
* contain a `README.md` with instructions for running the application;
* be functional;
* satisfy the requirements documented above.

---

## 9. AI Usage Requirement

AI-assisted development is permitted.

The submission must include:

* the AI prompts used;
* an explanation of how important prompts were structured;
* the reasoning behind significant instructions given to AI;
* an explanation of how AI output contributed to the final implementation.

The project records this information in:

* `docs/ai-usage.md`

AI-generated output is not considered authoritative by itself. Final implementation decisions must be validated against this document and the application architecture.

---

## 10. Out of Scope

Unless explicitly requested, the project does not need to implement:

* direct email delivery;
* SMTP infrastructure;
* user authentication or authorization;
* file sharing;
* file editing;
* permanent file storage;
* support for file types other than PDF and DOCX;
* a frontend framework other than the required Bootstrap/jQuery stack.

---

## 11. Requirement Priority

When implementation details conflict with documented product behavior, this document takes precedence.

If a requirement is ambiguous, do not silently invent new product behavior.

Document the chosen interpretation in `docs/architecture.md` when it materially affects implementation.
