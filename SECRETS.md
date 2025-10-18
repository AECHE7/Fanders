# Secrets and environment variables

Recommended secrets and environment variables to add to GitHub (Actions) and Render:

- `DB_HOST` - hostname for the production MySQL database
- `DB_PORT` - the port for MySQL (usually `3306`)
- `DB_NAME` - database name (e.g. `fanders`)
- `DB_USER` - database user
- `DB_PASS` - database password
- `APP_URL` - public application URL (optional)
- `COMPOSER_ALLOW_SUPERUSER` - set to `1` if Composer runs as root in CI

Usage
- In GitHub Actions, add repository secrets under Settings > Secrets and variables > Actions.
- In Render, set env vars in the Service > Environment section or use Render's Secret store.

CI recommendations
- Add a CI-only DB user with limited privileges for running tests or migrations in CI.
- Prefer managed DB instances with network rules allowing access from CI runners or use a separate migration job with proper credentials.
