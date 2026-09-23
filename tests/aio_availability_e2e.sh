#!/usr/bin/env bash
# End-to-end: real player_api.php on php -S dev servers against a mock
# AIOStreams. Run from anywhere: bash tests/aio_availability_e2e.sh
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
FAST_PORT=18211; MOCK_PORT=18212; TIGHT_PORT=18213
PHP="php -d opcache.enable=0 -d opcache.enable_cli=0"
fails=0
cleanup() { pkill -f "php.*-S 127.0.0.1:$FAST_PORT" 2>/dev/null; pkill -f "php.*-S 127.0.0.1:$MOCK_PORT" 2>/dev/null; pkill -f "php.*-S 127.0.0.1:$TIGHT_PORT" 2>/dev/null; rm -rf "$WORK"; }
trap cleanup EXIT

# Docroot per server: real code (symlinks) + a test config layered on the real
# config.php so the real helper functions are what is exercised.
make_root() { # name extra-php-config
  local d="$WORK/$1"; mkdir -p "$d"
  for f in "$ROOT"/*.php; do [ "$(basename "$f")" = config.php ] || ln -s "$f" "$d/$(basename "$f")"; done
  printf '<?php\nrequire "%s/config.php";\n$frenchAioStreamsUrl = "http://127.0.0.1:%s";\n%s\n' "$ROOT" "$MOCK_PORT" "$2" > "$d/config.php"
}
start_api() { # name port datadir
  (cd "$WORK/$1" && M3U_DATA_DIR="$3" $PHP -S 127.0.0.1:$2 >"$WORK/$1.log" 2>&1) &
  for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$2/" && break; sleep 0.2; done
}
export MOCK_STATE_DIR="$WORK/mock"; mkdir -p "$MOCK_STATE_DIR"
PHP_CLI_SERVER_WORKERS=64 $PHP -S 127.0.0.1:$MOCK_PORT "$ROOT/tests/mock_aiostreams.php" >"$WORK/mock.log" 2>&1 &

# FAST: generous limits, to test the logic. TIGHT: no overrides = the shared-
# instance defaults (tiny bucket, parallel 4) that protect playback.
make_root fast '$availabilityMaxPerMinute = 1000000; $availabilityBurst = 1000000; $availabilityParallel = 64;'
make_root tight ''
start_api fast $FAST_PORT "$WORK/data_fast"
start_api tight $TIGHT_PORT "$WORK/data_tight"

CODE=""; BODY=""; HDR=""
call() { # port id account [extra]
  local out; out=$(curl -s -m 60 -D "$WORK/h.txt" -w '\n%{http_code}' "http://127.0.0.1:$1/player_api.php?action=get_vod_availability&vod_id=$2&username=$3&password=x${4:-}")
  CODE=${out##*$'\n'}; BODY=${out%$'\n'*}
}
batch() { # port type items account [extra]
  local out; out=$(curl -s -m 120 -D "$WORK/h.txt" -w '\n%{http_code}' "http://127.0.0.1:$1/player_api.php?action=get_availability_batch&type=$2&items=$3&username=$4&password=x${5:-}")
  CODE=${out##*$'\n'}; BODY=${out%$'\n'*}
}
jq_() { python3 -c "
import json,sys
d=json.loads(sys.argv[1]); v=d
for k in sys.argv[2].split('.'):
    v = v[int(k)] if k.isdigit() else v.get(k)
print(json.dumps(v))" "$BODY" "$1"; }
ok()   { echo "PASS $1"; }
bad()  { echo "FAIL $1 | http=$CODE body=$BODY"; fails=$((fails+1)); }
expect() { local got; got=$(jq_ "$2"); [ "$got" = "$3" ] && ok "$1 ($2=$got)" || { echo "FAIL $1: $2=$got want $3 | http=$CODE body=$BODY"; fails=$((fails+1)); }; }
expect_code() { [ "$CODE" = "$2" ] && ok "$1 (http $CODE)" || bad "$1 want http $2"; }
hits() { [ -f "$MOCK_STATE_DIR/hits/$1" ] && wc -c <"$MOCK_STATE_DIR/hits/$1" | tr -d ' ' || echo 0; }
F=$FAST_PORT

echo "--- single-title endpoint (fast instance)"
call $F 1001 UnlimitedFR; expect_code "cached 1080p x264 (FR)" 200; expect "  available" available true; expect "  best" best '"1080p_x264"'
call $F 1002 UnlimitedFR; expect "only uncached -> not available" available false
call $F 1004 UnlimitedFR; expect "no streams -> not available" available false
call $F 1005 UnlimitedFR; expect "720p x265 cached -> available" available true; expect "  best" best '"720p_x265"'; expect "  480p cached is other" other_cached 1
call $F 1008 UnlimitedFR; expect "SD-only cached -> not available" available false
call $F 1006 UnlimitedFR; expect "English-only release, FR account -> not available" available false
call $F 1006 Unlimited;   expect "English-only release, EN account -> available" available true
call $F 1003 UnlimitedFR; expect_code "AIOStreams 500 -> 502" 502; expect "  unknown, not false" available null
call $F 1007 UnlimitedFR; expect_code "AIOStreams garbage -> 502" 502; expect "  unknown" available null
call "" "" "" 2>/dev/null; call $F "" UnlimitedFR; expect_code "missing vod_id -> 400" 400
call $F abc UnlimitedFR; expect_code "non-numeric vod_id -> 400" 400

echo "--- one upstream lookup serves every language"
b=$(hits 1001); call $F 1001 Unlimited; expect "EN account answered from the FR account's cached lookup" from_cache null
[ "$(hits 1001)" = "$b" ] && ok "no second upstream hit for the other language ($b)" || bad "second language re-hit upstream"
b=$(hits 1001); call $F 1001 UnlimitedFR "&refresh=1"; [ "$(hits 1001)" = "$((b+1))" ] && ok "refresh=1 bypasses the cache (one more upstream hit)" || bad "refresh hit count"
b=$(hits 1003); call $F 1003 UnlimitedFR; [ "$(hits 1003)" -gt "$b" ] && ok "failures are not cached ($b -> $(hits 1003))" || bad "failure was cached"

echo "--- throttling and degradation are UNKNOWN, never 'none'"
call $F 2001 UnlimitedFR; expect_code "rate-limit stub -> 429" 429; expect "  unknown, not false" available null; expect "  reason" error '"aiostreams_rate_limited"'
grep -qi '^Retry-After:' "$WORK/h.txt" && ok "  Retry-After header present" || bad "no Retry-After"
b=$(hits 1001); call $F 1002 UnlimitedFR "&refresh=1"; expect_code "during cooldown no upstream call is made (429)" 429
[ "$(hits 1002)" -le 2 ] && ok "  and 1002 was not re-fetched during cooldown" || bad "cooldown did not stop upstream calls"
rm -f "$WORK"/data_fast/availability/cooldown   # end the cooldown for the remaining tests
call $F 2002 UnlimitedFR; expect "addon failure, no candidates -> unknown" available null
call $F 2003 UnlimitedFR; expect "metadata-only errors -> definitive none" available false
call $F 2004 UnlimitedFR; expect "cached candidate beats an addon failure" available true
b=$(hits 2001); call $F 2001 UnlimitedFR; b2=$(hits 2001); rm -f "$WORK"/data_fast/availability/cooldown; call $F 2001 UnlimitedFR "&refresh=1"
[ "$(hits 2001)" -gt "$b2" ] && ok "a throttled answer is never cached (re-asked once the cooldown ended)" || bad "throttle answer cached"
rm -f "$WORK"/data_fast/availability/cooldown

echo "--- batch endpoint"
batch $F movie 1001:2020,1002:2020,1004:2020,1005:2020 UnlimitedFR
expect_code "batch ok" 200; expect "  1001 available" results.0.available true; expect "  1002 none" results.1.available false; expect "  1004 none" results.2.available false; expect "  1005 available" results.3.available true
batch $F movie 1001,1001,1002 UnlimitedFR; python3 -c "import json,sys; sys.exit(0 if len(json.loads(sys.argv[1])['results'])==2 else 1)" "$BODY" && ok "duplicate ids collapse" || bad "duplicates"
batch $F movie "" UnlimitedFR; expect_code "empty items -> 400" 400
big=$(python3 -c "print(','.join(str(i) for i in range(1,102)))"); batch $F movie "$big" UnlimitedFR; expect_code "over 100 items -> 400" 400
batch $F movie 2001:2020,1001:2020 UnlimitedFR "&refresh=1"; expect "throttle mid-batch: throttled flag" throttled true; expect "  a title answered in the same batch keeps its answer" results.1.available true
rm -f "$WORK"/data_fast/availability/cooldown

echo "--- series probes (S01E01, then S02E01)"
batch $F series 3001,3002,3003,3004 UnlimitedFR
expect "S01E01 cached" results.0.available true
expect "only S02E01 cached -> still available" results.1.available true
expect "nothing cached -> none" results.2.available false
expect "S01E01 uncached only -> none" results.3.available false
b=$(hits 3003); batch $F series 3003 Unlimited; [ "$(hits 3003)" = "$b" ] && ok "series lookups are shared across languages too (no re-fetch for the EN account)" || bad "series re-fetched"

echo "--- parallelism: 40 titles with 1s upstream latency each"
ids=$(python3 -c "print(','.join(f'{4000+i}:2020' for i in range(40)))")
t0=$(python3 -c "import time; print(time.time())"); batch $F movie "$ids" UnlimitedFR; t1=$(python3 -c "import time; print(time.time())")
el=$(python3 -c "print(round($t1-$t0,1))")
python3 -c "import sys; sys.exit(0 if float('$el') < 8 else 1)" && ok "40 x 1s lookups finished in ${el}s (parallel; serial would be ~40s)" || bad "batch was not parallel: ${el}s"
expect "  all 40 answered" results.39.available true

echo "--- shared-instance defaults protect playback (tight instance: burst 5, parallel 4)"
batch $TIGHT_PORT movie 1001,1002,1004,1005,1006,1008,2003,4001,4002,4003 UnlimitedFR
python3 - "$BODY" <<'EOF' && ok "only the 5-token burst reached upstream; the rest are unknown with a retry hint" || bad "tight budget not enforced"
import json,sys
d=json.loads(sys.argv[1]); r=d['results']
answered=[x for x in r if x['available'] is not None]; unknown=[x for x in r if x['available'] is None]
assert len(answered)==5, len(answered)
assert len(unknown)==5 and all(x['error']=='availability_budget' for x in unknown), unknown
assert d['retry_after'] and d['retry_after']>=1
EOF
batch $TIGHT_PORT movie 1001,1002,1004,1005,1006,1008,2003,4001,4002,4003 UnlimitedFR; python3 - "$BODY" <<'EOF' && ok "answered titles come from cache and cost no tokens on the next call" || bad "cache did not spare the budget"
import json,sys
d=json.loads(sys.argv[1]); assert sum(1 for x in d['results'] if x['available'] is not None)>=5
EOF

echo "--- untouched"
out=$(curl -s "http://127.0.0.1:$F/player_api.php?username=UnlimitedFR&password=x"); echo "$out" | grep -q '"auth":1' && ok "default user_info action still works" || bad "user_info"

echo; [ $fails -eq 0 ] && echo "E2E ALL PASSED" || echo "E2E $fails FAILED"; exit $fails
