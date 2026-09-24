# File Lifecycle Manager

Laravel 13 project for temporary PDF and DOCX storage. The application features are planned in [docs/implementation-plan.md](docs/implementation-plan.md). The repository currently includes server-side upload, file metadata storage, a file management page, and a RabbitMQ deletion notification publisher. Manual deletion and expiration processing are still to be implemented.

## Docker setup

Docker Compose builds the Laravel application with PHP 8.4 and compiled Bootstrap/jQuery assets, then starts MySQL 8.4, RabbitMQ 4.3, and a Laravel Scheduler process. Local PHP, Composer, Node.js, MySQL, and RabbitMQ installations are unnecessary for this path.

1. Copy `.env.example` to `.env`.
2. Set nonempty values for `DB_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD`, and `DELETION_NOTIFICATION_EMAIL` in `.env`. You may change `APP_PORT` and `RABBITMQ_MANAGEMENT_PORT` if those host ports are occupied.
3. Run `docker compose up --build -d`.
4. Check startup with `docker compose ps` and `docker compose logs app`.

Open the file management page at `http://localhost:8000` (or the configured `APP_PORT`). RabbitMQ Management is available at `http://localhost:15672` (or the configured `RABBITMQ_MANAGEMENT_PORT`) with the credentials in `.env`.

The application waits for MySQL and RabbitMQ health checks. It creates a persistent application key when `APP_KEY` is empty and runs Laravel migrations on application startup. The existing `scheduler` service runs `php artisan schedule:work`, which invokes `files:delete-expired` every minute. MySQL data, RabbitMQ data, and Laravel storage persist in Docker volumes. Use `docker compose down` to stop the stack without removing those volumes.

## Local requirements

- PHP 8.3 or newer (PHP 8.4 is supported), Composer 2
- Node.js and npm
- MySQL
- RabbitMQ for deletion notifications once that feature is implemented

On macOS with Homebrew, start installed services when needed with `brew services start mysql` and `brew services start rabbitmq`. The setup does not require RabbitMQ to display the starter page.

## Local setup without Docker

1. Install dependencies: `composer install` and `npm install`.
2. Copy `.env.example` to `.env` and run `php artisan key:generate`. Keep `.env` local.
3. Start MySQL, create a database and user, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
4. Run `php artisan migrate`.
5. Run `npm run dev` and `php artisan serve` in separate terminals.

For automatic expiration without Docker, also run `php artisan schedule:work` in a separate terminal. You can invoke one expiration pass with `php artisan files:delete-expired`.

Uploaded file content should be written through the Laravel disk selected by `FILE_UPLOAD_DISK` (default: `uploads`). This disk stores files under `storage/app/uploads`, outside the public web root. Keep any replacement disk private; never select the `public` disk for uploaded documents. The metadata record stores the disk and relative path so later deletion attempts can locate the original object even if the configured default changes.

The file management page is available at `http://localhost:8000`. It shows saved files and uploads a selected file asynchronously with Bootstrap and jQuery. After upload, the browser refreshes the list from `GET /files`. The server accepts one multipart file at `POST /files` in the `file` field. Submit it with `Accept: application/json`; success returns HTTP 201 with the file ID, original name, MIME type, byte size, upload time, and expiration time. Invalid files return HTTP 422 with JSON validation errors. PDF and DOCX are accepted up to 10 MiB, with MIME type and extension checked on the server. The manual deletion control is disabled until the shared deletion workflow and its HTTP endpoint are implemented. Expiration is processed by the scheduler described above.

The deletion publisher uses `RABBITMQ_HOST`, `RABBITMQ_PORT`, `RABBITMQ_USER`, `RABBITMQ_PASSWORD`, and `RABBITMQ_VHOST`. It declares a durable queue named by `RABBITMQ_DELETION_QUEUE` and sends a persistent JSON message addressed to `DELETION_NOTIFICATION_EMAIL`. See [docs/architecture.md](docs/architecture.md) for the message contract and failure rule. The application does not send email.

For a production asset bundle, run `npm run build`.

## Documentation

- [Product requirements](docs/requirements.md)
- [Architecture](docs/architecture.md)
- [Implementation plan](docs/implementation-plan.md)
- [AI usage record](docs/ai-usage.md)
