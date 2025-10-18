# Development setup (Docker)

This project includes a small Docker Compose configuration to provide a reproducible PHP + MySQL dev environment.

Requirements:
- Docker and docker-compose installed.

Start the dev stack:

```bash
docker-compose up --build -d
```

This will build a PHP image with pdo_mysql and start a MySQL 8.0 database mapped to host port 3307.

Wait until the MySQL container is healthy (compose healthcheck takes a few seconds).

Run syntax checks (php -l) inside the PHP container:

```bash
docker-compose exec php bash -lc "find . -name '*.php' -print0 | xargs -0 -n1 php -l"
```

Run the project's test scripts inside the PHP container (example):

```bash
docker-compose exec php php -d display_errors=1 -d error_reporting=E_ALL test_cash_blotter.php
```

Notes:
- `app/config/config.php` reads environment variables when available. When using docker-compose the internal DB host will be `db` and internal port `3306`.
- I added `.env.example`. Copy to `.env` and edit values for local dev. Consider integrating `vlucas/phpdotenv` to load `.env` in `app/config/config.php` (already required in composer.json).
- Database initialization: any SQL files placed under `scripts/initdb/` will be executed automatically by the MySQL container on first initialization. Use this folder to add migration SQL files.

