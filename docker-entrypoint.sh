#!/bin/sh
# Container entrypoint: applies the PHP memory limit, optionally starts the
# prewarmer cron, then hands off to Apache in the foreground.
set -e

# Runtime memory limit (previously set inline in the Dockerfile CMD).
echo "memory_limit=${PHP_MEMORY_LIMIT:-1024M}" > /usr/local/etc/php/conf.d/memory-limit.ini

# The durable data dir (SQLite DB, event log, install secret) lives on a
# mounted volume that comes up root-owned; make it writable by the web user so
# play.php and the dashboard can persist to it.
if [ -n "${M3U_DATA_DIR}" ]; then
    mkdir -p "${M3U_DATA_DIR}"
    chown -R www-data:www-data "${M3U_DATA_DIR}" || true
    chmod 700 "${M3U_DATA_DIR}" || true
fi

# Optional prewarmer. DISABLED by default: enabling it makes the server resolve
# titles ahead of time so the first viewer play is an instant cache hit instead
# of a ~13s AIOStreams round-trip - but each resolve pulls ~10MB through your
# debrid provider, so a large list can burn its fair-use allowance. Turn it on
# only when you have set your real config.php secrets, by setting
# PREWARM_ENABLED=true (see docker-compose.yml for the tunables).
if [ "${PREWARM_ENABLED}" = "true" ] || [ "${PREWARM_ENABLED}" = "1" ]; then
    SCHEDULE="${PREWARM_SCHEDULE:-0 */2 * * *}"
    ARGS="${PREWARM_ARGS:---expiring=150 --playlist=top --limit=400 --accounts=Unlimited,UnlimitedFR --base=http://127.0.0.1 --workers=8}"
    touch /var/log/m3u-prewarm.log
    chown www-data:www-data /var/log/m3u-prewarm.log || true
    # /etc/cron.d format: "<schedule> <user> <command>"; must be 0644 and end
    # in a newline. Runs as www-data so cache/data files stay owned correctly.
    printf '%s www-data cd /var/www/html && /usr/local/bin/php prewarm.php %s >> /var/log/m3u-prewarm.log 2>&1\n' "$SCHEDULE" "$ARGS" > /etc/cron.d/m3u-prewarm
    chmod 0644 /etc/cron.d/m3u-prewarm
    echo "[entrypoint] prewarmer ENABLED  schedule='$SCHEDULE'  args='$ARGS'"
    cron
else
    echo "[entrypoint] prewarmer disabled (set PREWARM_ENABLED=true to enable)"
fi

exec apache2-foreground
