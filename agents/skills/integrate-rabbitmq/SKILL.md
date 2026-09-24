---

name: integrate-rabbitmq
description: >
Implement, modify, or debug RabbitMQ publishing for deletion notifications.
Use when changing RabbitMQ configuration, the publisher abstraction,
notification payloads, connection handling, or publishing failures.
Do not use for general file lifecycle changes unless RabbitMQ behavior itself changes.
--------------------------------------------------------------------------------------

# Integrate RabbitMQ

Use this skill for RabbitMQ-specific changes.

## Responsibility

The application publishes file-deletion notifications to RabbitMQ.

It does not send email directly.

Do not add:

* SMTP;
* Laravel Mail;
* external email providers.

## Rules

* Keep RabbitMQ-specific code outside controllers and presentation code.
* Read connection details and destination names from configuration/environment values.
* Never hardcode credentials or environment-specific infrastructure values.
* Prefer a thin application-facing publishing abstraction.
* Do not build a generic messaging framework for this project.
* Do not silently ignore publishing failures.

## Workflow

1. Read the relevant notification requirements in `docs/requirements.md`.
2. Inspect the existing publisher and configuration.
3. Make the smallest RabbitMQ-specific change required.
4. Keep application code independent of client-library details where practical.
5. Update `.env.example` when new configuration values are required.
6. Add or update tests.
7. Run the relevant verification commands from `AGENTS.md`.

## Testing

Do not require a real RabbitMQ server for normal application tests.

Mock or fake the application-facing publishing boundary and verify:

* publication is requested when required;
* the expected payload is produced;
* publishing failures are handled rather than silently ignored.

Use a real broker only when an explicit integration test requires it.
