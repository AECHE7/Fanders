#!/bin/sh
set -e

# Simple entrypoint to wait for DB availability and optionally run init SQL scripts
# Environment variables respected:
# - DB_HOST, DB_PORT (default 3306)
# - RUN_DB_INIT (if "true", will attempt to run SQL files from /app/scripts/initdb using mysql client)

DB_HOST=${DB_HOST:-}
DB_PORT=${DB_PORT:-3306}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
RUN_DB_INIT=${RUN_DB_INIT:-false}

wait_for_port() {
  host="$1"; port="$2"; timeout=30; echo "Waiting for $host:$port..."
  if command -v nc >/dev/null 2>&1; then
    while ! nc -z "$host" "$port"; do
      timeout=$((timeout-1))
      if [ "$timeout" -le 0 ]; then
        echo "Timeout waiting for $host:$port" >&2
        return 1
      fi
      sleep 1
    done
    echo "$host:$port is available (nc)"
    return 0
  fi

  if command -v mysqladmin >/dev/null 2>&1; then
    # Use mysqladmin ping as a fallback
    while ! mysqladmin ping -h "$host" -P "$port" --silent >/dev/null 2>&1; do
      timeout=$((timeout-1))
      if [ "$timeout" -le 0 ]; then
        echo "Timeout waiting for $host:$port (mysqladmin)" >&2
        return 1
      fi
      sleep 1
    done
    echo "$host:$port is available (mysqladmin)"
    return 0
  fi

  # Last resort: attempt a TCP connection using /dev/tcp (may not be available)
  if [ -e /dev/tcp/127.0.0.1/80 ] 2>/dev/null; then
    echo "/dev/tcp is available but no checker found; assuming $host:$port is reachable"
    return 0
  fi

  echo "No port-check utility available (nc/mysqladmin); skipping wait." >&2
  return 1
}

if [ -n "$DB_HOST" ]; then
  # Wait for DB to be ready
  if ! wait_for_port "$DB_HOST" "$DB_PORT"; then
    echo "Warning: DB not reachable at $DB_HOST:$DB_PORT, continuing anyway" >&2
  else
    if [ "$RUN_DB_INIT" = "true" ]; then
      echo "RUN_DB_INIT=true; attempting to run SQL init scripts in /app/scripts/initdb"
      if command -v mysql >/dev/null 2>&1; then
        for f in /app/scripts/initdb/*.sql; do
          [ -f "$f" ] || continue
          echo "Running $f"
          mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" ${DB_PASS:+-p$DB_PASS} < "$f" || echo "Failed to run $f"
        done
      else
        echo "mysql client not found in image; skipping DB init" >&2
      fi
    fi
  fi
fi

# Execute the command
exec "$@"
