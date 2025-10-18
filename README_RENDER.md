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
