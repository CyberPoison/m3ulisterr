<?php
error_reporting(0);
set_time_limit(0);
ob_end_clean();

require_once 'm3ulisterr_lib.php';

if (isset($_GET['data']) && !empty($_GET['data'])) {
    $decodedData = base64_decode($_GET['data']);
    $parts = explode('|', $decodedData);

    // If 'url' param is set, use it; otherwise, use first part of $parts
    if (isset($_GET['url']) && !empty($_GET['url'])) {
        $url = $_GET['url'];
        // All $parts are headers (no URL in parts)
    } else {
        $url = array_shift($parts); // first part is URL (old way)
    }

    // SSRF guard: this endpoint fetches an arbitrary caller-supplied URL, so
    // refuse anything that isn't http(s) to a public host - otherwise it can
    // be pointed at localhost services, the LAN, or a cloud metadata endpoint
    // and made to fetch them on the server's behalf.
    m3uRequireSafeRemoteUrl($url);
    $m3uProxyStart = microtime(true);

    $httpOptions = [
        'http' => [
            'method' => 'GET',
            'header' => []
        ]
    ];

    // Loop through remaining parts and add as headers
    foreach ($parts as $headerData) {
        if (strpos($headerData, '=') !== false) {
            list($header, $value) = explode('=', $headerData, 2);
            $value = trim($value, "'\"");
            $httpOptions['http']['header'][] = "$header: $value";
        }
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $httpOptions['http']['header'][] = "Range: " . $_SERVER['HTTP_RANGE'];
    }

    // Check for auto redirect flag
    $auto = isset($_GET['auto']) && (strtolower($_GET['auto']) == '1' || strtolower($_GET['auto']) === 'true');

    // ---- AUTO MODE: resolve once server-side, then stream ranges ----
    //
    // Used for AIOStreams /playback/ links (wrapWithVideoProxy() in play.php).
    // Those are locked to whichever IP first resolved them, so the bytes must
    // flow through this server rather than the player being redirected.
    //
    // This used to open the /playback/ URL twice for every player request
    // (get_headers() then fopen()), and a player makes a lot of requests: the
    // opening read, a jump to the end of the file for the Matroska cue index,
    // then one more per seek. Each went back through AIOStreams' resolver and
    // the debrid unlock behind it - dozens of resolutions for one viewing,
    // which is the pattern that gets an IP rate-limited or blocked. The
    // redirect chain is now walked once, the final CDN URL cached briefly,
    // and each player request costs a single ranged GET against that URL.
    if ($auto) {
        $upstreamHeaders = [];
        foreach ($parts as $headerData) {
            if (strpos($headerData, '=') !== false) {
                list($header, $value) = explode('=', $headerData, 2);
                $upstreamHeaders[] = trim($header) . ': ' . trim($value, "'\"");
            }
        }

        $isHead = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD';
        $clientRange = $_SERVER['HTTP_RANGE'] ?? '';

        $finalUrl = proxyResolveFinalUrl($url, $upstreamHeaders, false);
        $outcome = $finalUrl === false
            ? 'upstream_error'
            : proxyStreamUpstream($finalUrl, $upstreamHeaders, $clientRange, $isHead);

        // A cached CDN link can expire partway through a movie. One fresh
        // resolve is worth trying first - but only while nothing has reached
        // the client yet, since a half-sent response can't be restarted.
        if ($outcome === 'upstream_error') {
            $finalUrl = proxyResolveFinalUrl($url, $upstreamHeaders, true);
            if ($finalUrl !== false) {
                $outcome = proxyStreamUpstream($finalUrl, $upstreamHeaders, $clientRange, $isHead);
            }
        }

        if ($outcome === 'upstream_error') {
            http_response_code(502);
            header('Content-Type: text/plain');
            echo "Upstream link could not be streamed.";
        }

        // French-playback delivery analytics: this is the whole pipeline for a
        // ?lang=fr / UnlimitedFR stream (no HLS segments), so the byte range
        // served and how long it took is the delivery signal for those.
        if (function_exists('m3uLogEvent')) {
            m3uLogEvent('proxy', [
                'media' => m3uMediaHash($url),
                'range' => substr($clientRange, 0, 64),
                'outcome' => $outcome,
                'head' => $isHead,
                'deliverMs' => round((microtime(true) - $m3uProxyStart) * 1000),
            ]);
        }
        exit;
    }
    // ---- END AUTO MODE ----

    // ---- MANUAL REDIRECT MODE (Default/Old Style) ----
    $context = stream_context_create($httpOptions);
    $maxRedirects = 10;
    $statusCode = null;
    $contentType = null;
    for ($i = 0; $i < $maxRedirects; $i++) {
        $headers = get_headers($url, 1, $context);
        if ($headers === false) {
            http_response_code(500);
            header('Content-Type: text/plain');
            echo "Failed to fetch headers.";
            exit;
        }

        $statusLine = $headers[0];
        preg_match('{HTTP/\S+ (\d{3})}', $statusLine, $match);
        $statusCode = $match[1];

        if (isset($headers['Content-Type'])) {
            $contentType = is_array($headers['Content-Type']) ? end($headers['Content-Type']) : $headers['Content-Type'];
        }

        if (in_array($statusCode, ['301', '302'])) {
            $url = $headers['Location'];
            if (is_array($url)) {
                $url = end($url);
            }
        } else {
            break;
        }
    }

    if (!in_array($statusCode, ['200', '206'])) {

        // --- FIX: Override status code if Content-Type is video ---
        $ct = isset($headers['Content-Type']) ? (is_array($headers['Content-Type']) ? end($headers['Content-Type']) : $headers['Content-Type']) : '';
        if (stripos($ct, 'video/') === 0 || stripos($ct, 'application/octet-stream') === 0) {
            if (isset($_SERVER['HTTP_RANGE'])) {
                http_response_code(206);
            } else {
                http_response_code(200);
            }
        } else {
            http_response_code($statusCode);
        }

        header('Content-Type: ' . ($contentType ?: 'text/plain'));
        echo "Failed with status code: $statusCode";
        exit;
    }

    $headers = get_headers($url, 1, $context);

    header($headers[0]);
    if (isset($headers['Content-Type'])) {
        header('Content-Type: ' . $headers['Content-Type']);
    }
    if (isset($headers['Content-Length'])) {
        header('Content-Length: ' . $headers['Content-Length']);
    }
    if (isset($headers['Accept-Ranges'])) {
        header('Accept-Ranges: ' . $headers['Accept-Ranges']);
    }
    if (isset($headers['Content-Range'])) {
        header('Content-Range: ' . $headers['Content-Range']);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
        exit;
    }

    $fp = fopen($url, 'rb', false, $context);
    if ($fp === false) {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo "Failed to open URL.";
        exit;
    }

    while (!feof($fp)) {
        echo fread($fp, 1024 * 256);
        flush();
    }
    fclose($fp);
} else {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Missing the data parameter.";
}

function proxyResolveCachePath($url) {
    $dir = sys_get_temp_dir() . '/video_proxy_resolved';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . '/' . md5($url) . '.json';
}

// Walks $url's redirect chain once and returns where it lands, cached for 20
// minutes - well inside the lifetime of the debrid links involved, while
// still bounding how long a dead one can be served. A streaming failure
// forces a refresh regardless ($forceRefresh).
function proxyResolveFinalUrl($url, array $headers, $forceRefresh) {
    $cachePath = proxyResolveCachePath($url);
    if (!$forceRefresh && is_file($cachePath)) {
        $cached = json_decode((string) @file_get_contents($cachePath), true);
        if (!empty($cached['finalUrl']) && (time() - (int) $cached['time']) < 1200) {
            return $cached['finalUrl'];
        }
    }

    $ch = curl_init($url);
    m3uRestrictCurlProtocols($ch);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_RANGE => '0-0',
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => $headers,
        // Stop after the first chunk: only the landing URL is wanted, and a
        // server that ignores Range would otherwise send the whole movie.
        CURLOPT_WRITEFUNCTION => function ($ch, $chunk) {
            return 0;
        },
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    // AIOStreams reports a failed resolve as a perfectly valid 200 video from
    // slate.elfhosted.com ("Couldn't start this download", "Wrong IP", ...).
    // Streaming it would show the viewer an error clip that looks like a hung
    // movie, so it counts as a hard failure.
    if ($status >= 400 || $status === 0 || $finalUrl === '' || stripos($finalUrl, 'slate.elfhosted.com') !== false) {
        @unlink($cachePath);
        return false;
    }

    $tmpPath = $cachePath . '.' . getmypid();
    @file_put_contents($tmpPath, json_encode(['finalUrl' => $finalUrl, 'time' => time()]));
    @rename($tmpPath, $cachePath);

    return $finalUrl;
}

// Streams one request from $finalUrl to the client, passing the player's Range
// through untouched. Returns 'ok', or 'upstream_error' when the upstream
// refused before a single byte was sent (the only case safe to retry).
function proxyStreamUpstream($finalUrl, array $headers, $clientRange, $isHead) {
    $requestHeaders = $headers;
    if ($clientRange !== '') {
        $requestHeaders[] = 'Range: ' . $clientRange;
    }

    $status = 0;
    $responseHeaders = [];
    $headersSent = false;
    $refused = false;

    $sendHeaders = function () use (&$status, &$responseHeaders, &$headersSent) {
        http_response_code($status);
        foreach (['content-type', 'content-length', 'content-range', 'accept-ranges', 'last-modified', 'etag'] as $name) {
            if (isset($responseHeaders[$name])) {
                header($responseHeaders[$name]);
            }
        }
        if (!isset($responseHeaders['accept-ranges'])) {
            header('Accept-Ranges: bytes');
        }
        $headersSent = true;
    };

    $ch = curl_init($finalUrl);
    m3uRestrictCurlProtocols($ch);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 15,
        // No overall timeout - a movie streams for hours. A transfer that
        // truly stalls is cut instead, and the player reconnects with a Range.
        CURLOPT_LOW_SPEED_LIMIT => 1,
        CURLOPT_LOW_SPEED_TIME => 120,
        CURLOPT_BUFFERSIZE => 262144,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_NOBODY => $isHead,
        CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$status, &$responseHeaders) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                // Each hop of a redirect chain starts a fresh header block.
                $status = (int) $m[1];
                $responseHeaders = [];
            } elseif (strpos($line, ':') !== false) {
                list($name) = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($line);
            }
            return strlen($line);
        },
        CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$status, &$headersSent, &$refused, $sendHeaders) {
            if (!$headersSent) {
                if ($status >= 400) {
                    $refused = true;
                    return 0;
                }
                $sendHeaders();
            }
            echo $chunk;
            flush();
            return connection_aborted() ? 0 : strlen($chunk);
        },
    ]);
    curl_exec($ch);
    curl_close($ch);

    if ($refused) {
        return 'upstream_error';
    }
    if (!$headersSent) {
        // HEAD, or a response with no body at all.
        if ($status === 0 || $status >= 400) {
            return 'upstream_error';
        }
        $sendHeaders();
    }
    return 'ok';
}
?>
