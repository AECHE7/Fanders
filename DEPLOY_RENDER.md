# Deploying Fanders to Render (Docker)

This guide shows the minimal steps to deploy the application to Render using the included `Dockerfile` and `render.yaml`.

Prerequisites
- A Git repository (GitHub, GitLab, etc.) with this project pushed.
- A Render account.

Quick steps (GUI)
1. In Render, create a new Web Service.
   - Connect your Git repo and choose the branch you want to deploy (e.g., `main`).
   - Environment: Docker. Render will build using the `Dockerfile` in the repository root.
2. Update environment variables in the Render service settings (`Environment` tab):
   - `DB_HOST` — (required) hostname for your MySQL instance
   - `DB_NAME` — default: `fanders`
   - `DB_USER` — database user (do NOT use root in production)
   - `DB_PASS` — database password (store as a secret)
   - `DB_PORT` — typically `3306`
   - `APP_URL` — the public URL for the app (optional)
   - `COMPOSER_ALLOW_SUPERUSER` — set to `1` if needed for Composer in CI
   - `RUN_DB_INIT` — optional. Set to `true` only if you want the container to attempt to run SQL files from `/app/scripts/initdb` on first start.
      - Instead of running raw SQL from the image, prefer running Phinx migrations (see below).
3. Health check: set the Health Check Path to `/health.php` (the container's document root is `/app/public`).
4. Deploy and watch build logs.

Notes and recommendations
- Security: Do NOT use empty DB root passwords in production. Use a dedicated DB user and strong password.
- Composer: The Dockerfile now fails the image build if composer install fails (no longer suppressed). Fix dependency errors before trying to deploy.
- DB bootstrap: The entrypoint supports running SQL files from `scripts/initdb` when `RUN_DB_INIT=true`. Prefer idempotent migrations over one-off SQL to avoid accidental duplicate runs.
 - DB bootstrap: The entrypoint supports running SQL files from `scripts/initdb` when `RUN_DB_INIT=true`. Prefer idempotent migrations (Phinx) over one-off SQL to avoid accidental duplicate runs.

CLI option (useful for testing locally)
1. Build the image locally:

```bash
docker build -t fanders:local .
```

2. Run container without waiting for DB (for manual testing):

```bash
docker run -d --rm -p 8080:80 -e RUN_DB_INIT=false --name fanders_local fanders:local
curl http://127.0.0.1:8080/health.php
```

3. To run DB init scripts against a DB reachable from your environment:

```bash
docker run -d --rm -p 8080:80 \
  -e DB_HOST=your-db-host -e DB_PORT=3306 -e DB_USER=youruser -e DB_PASS=yourpass \
  -e RUN_DB_INIT=true --name fanders_local fanders:local
```

If you want help wiring this up to a managed database on Render or adding a migration tool (Phinx / Doctrine Migrations), tell me which you prefer and I can add it.
This repository now includes Phinx migrations under `db/migrations` and a `phinx.php` config. To run migrations locally or in CI, install dev dependencies and run:

```bash
composer install --prefer-dist
vendor/bin/phinx migrate -c phinx.php -e development
```

On Render, prefer running migrations from a maintenance job or CI step that has DB access rather than enabling `RUN_DB_INIT=true` in production.
