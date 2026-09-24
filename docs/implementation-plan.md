# Implementation Plan

The order below follows [requirements.md](requirements.md) and the boundaries in [architecture.md](architecture.md). Each stage should be independently reviewable. Only stage 0 is part of the current setup task.

## 0. Project foundation — complete

- Create a Laravel 13 application requiring PHP 8.3+.
- Install PHP dependencies, the RabbitMQ PHP client, Bootstrap, and jQuery.
- Set MySQL as the example database connection and document local setup.
- Preserve the Laravel starter application without implementing product workflows.
- Provide a Docker Compose setup for Laravel, MySQL, and RabbitMQ, with persistent data and a documented startup path.

## 1. File metadata and storage foundation

- Add a MySQL-compatible migration and Eloquent model for stored files, including original name, stored identifier/path, type, size, and upload/expiration timestamps.
- Choose a Laravel filesystem disk and keep physical files outside the public web root unless controlled access is required.
- Decide how expired or deleted records are represented so database and physical storage can be reconciled.
- Cover schema and storage behavior with focused tests.

## 2. Upload API

- Add server-side validation for PDF/DOCX and the 10 MB limit.
- Store the file through Laravel's filesystem and persist its metadata, including the 24-hour expiration time.
- Return useful validation and storage errors; handle partial failures deliberately.
- Test accepted formats, rejected formats, size boundary, metadata, and cleanup on failure.

## 3. File management interface

- Build a dedicated Blade page with Bootstrap and jQuery.
- Submit uploads asynchronously and update the file list without a full page reload.
- Show uploaded files and relevant metadata; display validation and operation errors.
- Keep server-side rules authoritative. Test the HTTP behavior and critical UI interaction.

## 4. RabbitMQ publication boundary

- Add environment-backed configuration for broker connection, destination, and recipient email address.
- Implement a small publisher boundary with `php-amqplib/php-amqplib`; define the deletion message payload and failure behavior.
- Test publication through the application-facing boundary without requiring a live broker for ordinary tests.
- Do not implement SMTP or direct email delivery.

## 5. Shared deletion workflow and manual deletion

- Add one application-level deletion workflow that coordinates the physical file, database state, and RabbitMQ publication.
- Route manual deletion from the HTTP controller through that workflow.
- Define observable handling for a missing physical file or failed publication, avoiding a misleading success response.
- Test successful deletion, repeated deletion, missing file, and publication failure.

## 6. Automatic expiration

- Add a Laravel command that finds files uploaded at least 24 hours ago and calls the same deletion workflow.
- Schedule it to run without user interaction and document the scheduler process required in an installed environment.
- Test the 24-hour boundary, non-expired files, repeated runs, and notification publication.

## 7. Final integration and delivery

- Review every requirement against the complete application and exercise the upload, manual deletion, and automatic expiration paths.
- Run formatting, application tests, and asset build; verify MySQL and RabbitMQ integration where available.
- Finish configuration and launch instructions, including scheduler operation; update the AI usage record.
- Publish the finished source code to the chosen Git repository when requested.

## Decisions to make during implementation

- The specification requires a list of files, but does not explicitly require download or in-browser preview. Decide whether either is needed before adding that behavior.
- The RabbitMQ message schema and destination type are unspecified. Define and document them when implementing publication.
- The requirement calls for notification after every successful deletion. Specify how publication failures are surfaced and retried while keeping file and metadata state consistent.
