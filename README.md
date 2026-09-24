# File Lifecycle Manager

Laravel 13 project for temporary PDF and DOCX storage. The application features are planned in [docs/implementation-plan.md](docs/implementation-plan.md); the current repository contains the Laravel foundation and required client libraries only.

## Requirements

- PHP 8.3 or newer (PHP 8.4 is supported), Composer 2
- Node.js and npm
- MySQL
- RabbitMQ for deletion notifications once that feature is implemented

Docker is not required.

On macOS with Homebrew, start installed services when needed with `brew services start mysql` and `brew services start rabbitmq`. The setup does not require RabbitMQ to display the starter page.

## Local setup

1. Install dependencies: `composer install` and `npm install`.
2. Copy `.env.example` to `.env` and run `php artisan key:generate`. Keep `.env` local.
3. Start MySQL, create a database and user, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
4. Run `php artisan migrate`.
5. Run `npm run dev` and `php artisan serve` in separate terminals.

The default Laravel page is available at `http://localhost:8000`. Bootstrap and jQuery are installed through Vite. File upload, management, expiration, and RabbitMQ publication are not implemented yet.

For a production asset bundle, run `npm run build`.

## Documentation

- [Product requirements](docs/requirements.md)
- [Architecture](docs/architecture.md)
- [Implementation plan](docs/implementation-plan.md)
- [AI usage record](docs/ai-usage.md)
