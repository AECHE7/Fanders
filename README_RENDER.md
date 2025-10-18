Render deployment guide
=======================

This document describes exactly what to set in the Render dashboard to deploy this project using the included `render.yaml` manifest and Dockerfile.

Repository used by manifest
- Repo: https://github.com/AECHE7/Fanders
- Branch: feature/dev-docker-ci

Render service configuration (in the Render UI)
1. Create a new "Web Service" and connect your GitHub repo above.
2. Choose "Docker" as the Environment and let Render use the `Dockerfile` in the repo root.
3. In the "Advanced" section, ensure the health check path is set to `/health.php` (or leave to manifest).

Environment variables (set these under the Service > Environment or Secrets for sensitive values)
- DB_HOST — required — the hostname for your MySQL or Postgres instance. Example: `your-db-host` or managed DB host.
- DB_PORT — required — e.g. `3306` (MySQL) or `5432` (Postgres)
- DB_NAME — required — e.g. `fanders`
- DB_USER — required — DB username
- DB_PASS — required — DB password (mark as secret in Render)
- DATABASE_URL — optional — full connection string (e.g. `postgres://user:pass@host:5432/dbname`). If set, it will be parsed and override the individual DB_* envs.
- SUPABASE_URL — optional — set if using Supabase platform for storage/auth.
- SUPABASE_KEY — optional and sensitive — set as a secret if using Supabase features.
- DB_SSLMODE — optional — defaults to `require` for Postgres in the container. Use `disable` if not using TLS.
- APP_URL — recommended — set to the public Render URL for your service (e.g. `https://your-service.onrender.com`).
- COMPOSER_ALLOW_SUPERUSER — optional — set to `1` to allow composer in the image build.
- RUN_DB_INIT — optional — if `true`, the image entrypoint will attempt to run SQL files under `/app/scripts/initdb` using the `mysql` client in the container. Use with caution in production.

Health check
- Path: `/health.php`
- Protocol: HTTP
- Port: 80

Deployment flow (GUI)
1. Push branch `feature/dev-docker-ci` to GitHub.
2. On Render, create a Web Service and connect to the repo & branch above.
3. Add the environment variables & secrets listed above in the Render dashboard (do NOT add secrets to the repo).
4. Deploy. Watch the build logs. The Docker build runs `composer install` as part of the image build.

Notes & troubleshooting
- Do not commit secrets to the repo. Use Render's secrets for DB passwords and SUPABASE keys.
- If you use Supabase as your production DB, set `DATABASE_URL` to the Supabase-provided connection string or set `SUPABASE_DB_URL`.
- If your app fails to connect to the DB during startup, check logs for the entrypoint message; it waits for the DB but will continue if DB is unavailable. Use the `RUN_DB_INIT` flag only during first-time provisioning.

CI / Automated checks
- Consider adding a GitHub Action to run the test suite and build the image on PRs. I can add a workflow that uses Docker-in-Docker or uses the repo's tests inside the PHP image.

If you'd like, I can:
- Create a Render service for you (I can't access your Render account; I'll prepare everything and you only need to click deploy), or
- Add a GitHub Actions workflow that builds the image and runs PHPUnit on PRs.
Deploying to Render (Docker)
=================================

This project is prepared to deploy to Render using the Dockerfile in the repo root.

What I added
- `render.yaml` — an example Render service manifest (update OWNER/REPO and secrets in the Render dashboard before deploy).
- `public/health.php` — a simple health-check endpoint Render can probe.
- Dockerfile improvements — uses `php:8.1-apache`, sets DocumentRoot to `/app/public`, runs `composer install` during build, and sets a default `ServerName` to silence Apache warnings.
- `app/config/config.php` — DB_PORT default changed to `3306` and `APP_URL` supports env override.

Quick Render steps (GUI)
1. Push this repo to GitHub (or use your existing remote).
2. On Render dashboard, create a new "Web Service".
   - Connect your Git repo and select the `main` branch.
   - Environment: Docker (Render will build using this repo's `Dockerfile`).
3. Add environment variables in the Render Service settings:
   - `DB_HOST` — your MySQL host
   - `DB_NAME` — `fanders`
   - `DB_USER`, `DB_PASS`, `DB_PORT` (defaults to `3306`)
   - `APP_URL` — e.g. `https://your-app.onrender.com`
   - `COMPOSER_ALLOW_SUPERUSER` — `1` (optional)
4. Set the Health Check Path to `/health.php` (the container will serve `public/health.php`).
5. Deploy and watch build logs. The app should start and be reachable at the Render URL.

Notes
- If you need a managed MySQL instance, create one with a cloud provider (RDS, PlanetScale, ClearDB, etc.) and set `DB_HOST` accordingly in Render.
- For local testing, you can still use `docker-compose` (the repo includes a `docker-compose.yml` for dev).

Entrypoint and DB initialization
- The image includes a small entrypoint `docker-entrypoint.sh` which will wait for the database host/port if `DB_HOST` is set. It also supports running SQL init scripts from `/app/scripts/initdb` when the environment variable `RUN_DB_INIT=true` is set. This is optional — use with caution in production.

Composer and image builds
- The Dockerfile runs `composer install` during build with `--prefer-dist --no-dev --optimize-autoloader` to produce deterministic builds. Composer failures will now fail the build (no longer suppressed).
