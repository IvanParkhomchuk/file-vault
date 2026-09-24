# File Lifecycle Manager

Laravel 13 project for temporary PDF and DOCX storage. The application includes asynchronous uploads, a file management page, manual deletion, automatic expiration, and RabbitMQ deletion notifications. See [docs/implementation-plan.md](docs/implementation-plan.md) for the delivery sequence.

## Docker setup

Docker Compose builds the Laravel application with PHP 8.4 and compiled Bootstrap/jQuery assets, then starts MySQL 8.4, RabbitMQ 4.3, and a Laravel Scheduler process. Local PHP, Composer, Node.js, MySQL, and RabbitMQ installations are unnecessary for this path.

1. Create a local environment file: `cp .env.example .env`.
2. Set `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `RABBITMQ_USER`, and `RABBITMQ_PASSWORD` to nonempty values in `.env`. Set `DELETION_NOTIFICATION_EMAIL` to a valid email address; publication fails without it. You may change `APP_PORT` and `RABBITMQ_MANAGEMENT_PORT` if those host ports are occupied.
3. Build and start the services: `docker compose up --build -d`.
4. Confirm startup with `docker compose ps`; the `app`, `mysql`, and `rabbitmq` services should be healthy, and `scheduler` should be running. If startup fails, inspect `docker compose logs app scheduler mysql rabbitmq`.

Open the upload page at `http://localhost:8000/` and the file management page at `http://localhost:8000/files` (replace `8000` if you changed `APP_PORT`). RabbitMQ Management is available at `http://localhost:15672/` (or the configured `RABBITMQ_MANAGEMENT_PORT`) with the RabbitMQ credentials from `.env`.

The application waits for MySQL and RabbitMQ health checks. It creates a persistent application key when `APP_KEY` is empty and runs Laravel migrations on application startup. The `scheduler` service runs `php artisan schedule:work`, which invokes `files:delete-expired` every minute. MySQL data, RabbitMQ data, and Laravel storage persist in Docker volumes. Use `docker compose down` to stop the stack without removing those volumes.

After changing application code or frontend assets, run `docker compose up --build -d` again. The Dockerfile copies the source and builds assets into the image, so running `npm run build` on the host alone does not update a running container. After changing `DELETION_NOTIFICATION_EMAIL` or other application environment values, run `docker compose up -d --force-recreate app scheduler`. If you change a published port, run `docker compose up -d --force-recreate` so its service receives the new port mapping. MySQL and RabbitMQ initialize their users and passwords only when their data volumes are first created; changing those credentials later in `.env` does not update existing users.

## Local requirements

- PHP 8.3 or newer (PHP 8.4 is supported) with `bcmath`, `mbstring`, `pdo_mysql`, `sockets`, and `zip`; Composer 2
- Node.js and npm
- MySQL
- RabbitMQ for deletion notifications

On macOS with Homebrew, start installed services when needed with `brew services start mysql` and `brew services start rabbitmq`. RabbitMQ is required when a file is deleted.

## Local setup without Docker

1. Install dependencies: `composer install` and `npm install`.
2. Copy `.env.example` to `.env` and run `php artisan key:generate`. Keep `.env` local.
3. Start MySQL, create a database and user, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Set the RabbitMQ connection values and a valid `DELETION_NOTIFICATION_EMAIL` for deletion notifications.
4. Run `php artisan migrate`.
5. Run `npm run dev` and `php artisan serve` in separate terminals.

Set `upload_max_filesize` to at least `10M` and `post_max_size` above `10M` in the PHP configuration used by the web server (for example, `12M`). The Docker image sets these values to `10M` and `12M`. PHP rejects larger requests before Laravel can return its file validation error.

For automatic expiration without Docker, also run `php artisan schedule:work` in a separate terminal. You can invoke one expiration pass with `php artisan files:delete-expired`.

Uploaded file content should be written through the Laravel disk selected by `FILE_UPLOAD_DISK` (default: `uploads`). This disk stores files under `storage/app/uploads`, outside the public web root. Keep any replacement disk private; never select the `public` disk for uploaded documents. The metadata record stores the disk and relative path so later deletion attempts can locate the original object even if the configured default changes.

At upload time, the application rejects a disk unless it is a private local disk without a served URL and its root is outside `public/`, Laravel's public storage directory, and configured public storage-link targets. A misconfigured upload disk therefore fails the upload instead of writing a document to a web accessible location.

The upload page is available at `http://localhost:8000/`. It uploads a selected file asynchronously with Bootstrap and jQuery, then opens the dedicated management page at `GET /files`. The management page lists saved files and supports manual deletion; it refreshes its list from `GET /files/list`. The server accepts one multipart file at `POST /files` in the `file` field. Submit it with `Accept: application/json`; success returns HTTP 201 with the file ID, original name, MIME type, byte size, upload time, and expiration time. Invalid files return HTTP 422 with JSON validation errors. PDF and DOCX are accepted up to 10 MiB, with MIME type and extension checked on the server. After confirmation, the Delete button sends an asynchronous `DELETE /files/{id}` request. A completed deletion removes the physical file and metadata after RabbitMQ confirms its notification. If publication fails, the endpoint returns HTTP 503 and retains the metadata for retry through the same button or the scheduled command. Expiration is processed by the scheduler described above.

The deletion publisher uses `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD`, and `RABBITMQ_VHOST`. It declares a durable queue named by `RABBITMQ_DELETION_QUEUE` and sends a persistent JSON message addressed to `DELETION_NOTIFICATION_EMAIL`. See [docs/architecture.md](docs/architecture.md) for the message contract and failure rule. The application does not send email.

For a local production asset bundle outside Docker, run `npm run build`. Docker builds this bundle automatically during `docker compose up --build -d`.

## Documentation

- [Product requirements](docs/requirements.md)
- [Architecture](docs/architecture.md)
- [Implementation plan](docs/implementation-plan.md)
- [AI usage record](docs/ai-usage.md)
