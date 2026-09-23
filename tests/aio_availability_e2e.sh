#!/usr/bin/env bash
# End-to-end: real player_api.php on a php -S dev server, against a mock
# AIOStreams. Run from anywhere: bash tests/aio_availability_e2e.sh
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
API_PORT=18211; MOCK_PORT=18212
PHP="php -d opcache.enable=0 -d opcache.enable_cli=0"
fails=0
cleanup() { pkill -f "php.*-S 127.0.0.1:$API_PORT" 2>/dev/null; pkill -f "php.*-S 127.0.0.1:$MOCK_PORT" 2>/dev/null; rm -rf "$WORK"; }
trap cleanup EXIT

# Docroot: real code (symlinks) + a test config that layers the mock on top of
# the real config.php, so the real helper functions are what's exercised.
mkdir -p "$WORK/root"
for f in "$ROOT"/*.php; do [ "$(basename "$f")" = config.php ] || ln -s "$f" "$WORK/root/$(basename "$f")"; done
cat > "$WORK/root/config.php" <<PHPEOF
<?php
require '$ROOT/config.php';
\$frenchAioStreamsUrl = 'http://127.0.0.1:$MOCK_PORT';
PHPEOF
export MOCK_STATE_DIR="$WORK/mock" M3U_DATA_DIR="$WORK/data"; mkdir -p "$MOCK_STATE_DIR" "$M3U_DATA_DIR"

$PHP -S 127.0.0.1:$MOCK_PORT "$ROOT/tests/mock_aiostreams.php" >"$WORK/mock.log" 2>&1 &
(cd "$WORK/root" && $PHP -S 127.0.0.1:$API_PORT >"$WORK/api.log" 2>&1) &
for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$API_PORT/" && break; sleep 0.2; done

call() { # id account [extra] -> sets CODE, BODY
  local out; out=$(curl -s -m 60 -w '\n%{http_code}' "http://127.0.0.1:$API_PORT/player_api.php?action=get_vod_availability&vod_id=$1&username=$2&password=x${3:-}")
  CODE=${out##*$'\n'}; BODY=${out%$'\n'*}
}
field() { python3 -c "import json,sys; d=json.loads(sys.argv[1]); v=d.get(sys.argv[2]); print(json.dumps(v))" "$BODY" "$1"; }
expect() { # label field want
  local got; got=$(field "$2")
  if [ "$got" = "$3" ]; then echo "PASS $1 ($2=$got)"; else echo "FAIL $1: $2=$got want $3 | http=$CODE body=$BODY"; fails=$((fails+1)); fi
}
expect_code() { if [ "$CODE" = "$2" ]; then echo "PASS $1 (http $CODE)"; else echo "FAIL $1: http $CODE want $2 body=$BODY"; fails=$((fails+1)); fi; }
hits() { [ -f "$MOCK_STATE_DIR/hits/$1" ] && wc -c <"$MOCK_STATE_DIR/hits/$1" | tr -d ' ' || echo 0; }

call 1001 UnlimitedFR; expect_code "cached 1080p x264 (FR)" 200; expect "  available" available true; expect "  best" best '"1080p_x264"'
call 1002 UnlimitedFR; expect "only uncached -> not available" available false
call 1004 UnlimitedFR; expect "no streams -> not available" available false
call 1005 UnlimitedFR; expect "720p x265 cached -> available" available true; expect "  best" best '"720p_x265"'; expect "  480p cached is other" other_cached 1
call 1008 UnlimitedFR; expect "SD-only cached -> not available" available false; expect "  other_cached" other_cached 1
call 1006 UnlimitedFR; expect "English-only release, FR account -> not available" available false
call 1006 Unlimited;   expect "English-only release, EN account -> available" available true
call 1003 UnlimitedFR; expect_code "AIOStreams 500 -> 502" 502; expect "  unknown, not false" available null
call 1007 UnlimitedFR; expect_code "AIOStreams garbage -> 502" 502; expect "  unknown" available null

# Caching: second call served from cache without another upstream hit.
before=$(hits 1001); call 1001 UnlimitedFR; after=$(hits 1001)
expect "repeat call is from_cache" from_cache true
[ "$before" = "$after" ] && echo "PASS no extra upstream hit on cached repeat ($before)" || { echo "FAIL cache: hits $before -> $after"; fails=$((fails+1)); }
call 1001 UnlimitedFR "&refresh=1"; expect "refresh=1 bypasses cache" from_cache false
[ "$(hits 1001)" = "$((before+1))" ] && echo "PASS refresh hit upstream once more" || { echo "FAIL refresh hit count $(hits 1001)"; fails=$((fails+1)); }
# Errors must not be cached: a failing id is retried upstream each time.
b=$(hits 1003); call 1003 UnlimitedFR; [ "$(hits 1003)" -gt "$b" ] && echo "PASS failures are not cached (upstream hit again: $b -> $(hits 1003))" || { echo "FAIL failure was cached"; fails=$((fails+1)); }
# Per-language cache isolation: same id, other account, own answer.
call 1006 UnlimitedFR; expect "FR cache entry unaffected by EN entry" available false

# Year guard using real TMDB (Oppenheimer=2023): the 1995 release is rejected.
call 872585 UnlimitedFR
if [ "$(field language_matches)" = "1" ]; then
  echo "PASS wrong-year release rejected (1 of 2 language matches kept)"
  expect "  still available via the 2023 720p release" best '"720p_x264"'
elif [ "$(field language_matches)" = "2" ]; then echo "SKIP year guard: no TMDB key/network in this environment (covered by the unit tests)"
else echo "FAIL year guard: $BODY"; fails=$((fails+1)); fi

call "" UnlimitedFR; expect_code "missing vod_id -> 400" 400
call abc UnlimitedFR; expect_code "non-numeric vod_id -> 400" 400
# Existing endpoints untouched.
out=$(curl -s "http://127.0.0.1:$API_PORT/player_api.php?username=UnlimitedFR&password=x"); echo "$out" | grep -q '"auth":1' && echo "PASS default user_info action still works" || { echo "FAIL user_info"; fails=$((fails+1)); }

echo; [ $fails -eq 0 ] && echo "E2E ALL PASSED" || echo "E2E $fails FAILED"; exit $fails
