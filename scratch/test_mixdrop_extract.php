<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change working directory to project root so require_once works
chdir(dirname(__DIR__));

require_once 'libs/JavaScriptUnpacker.php';

function getLastRedirectUrl($url) {
    $timeOut = 15;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0");
    curl_exec($ch);
    if (!curl_errno($ch)) {
        $lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $lastUrl;
    }
    curl_close($ch);
    return false;
}

function checkLinkStatusCode($url) {
    // Return true for testing purposes
    return true;
}

function MixdropExtract($url, $tSite, $referer)
{
    $timeOut = 15;
    $unpacker = new JavaScriptUnpacker();

    echo "Initial URL: $url\n";
    $resolvedUrl = getLastRedirectUrl($url);
    if ($resolvedUrl) {
        $url = $resolvedUrl;
        echo "Resolved Redirect URL: $url\n";
    }

    // Use /e/ instead of /f/
    $url = str_replace('/f/', '/e/', $url);

    $parsedUrl = parse_url($url);
    $pReferer = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . "/";

    try {
        $contextOptions = ['http' => ['timeout' => $timeOut, 'header' =>
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0\r\n" .
            "Referer: " . $referer]];
        $context = stream_context_create($contextOptions);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception('HTTP Error: MixdropExtract');
        }

        echo "HTML response length: " . strlen($response) . "\n";

        // Unpack the JS
        if (preg_match('/eval\(function\(p,a,c,k,e,d\)[\s\S]*?(?=<\/script>)/s', $response, $matches)) {
            echo "Found packed JS, length: " . strlen($matches[0]) . "\n";
            $unpackedCode = $unpacker->unpack($matches[0]);
            echo "Unpacked code length: " . strlen($unpackedCode) . "\n";
        } else {
            throw new Exception('Couldn\'t find javascript code for MixdropExtract');
        }

        // Find MDCore.wurl (prefer double-quoted, fallback to single-quoted)
        $found = false;
        if (!empty($unpackedCode) && preg_match('/MDCore\.wurl\s*=\s*"([^"]+)"/', $unpackedCode, $wurlMatch)) {
            $DirectLink = 'https:' . $wurlMatch[1];
            $found = true;
        } elseif (!empty($unpackedCode) && preg_match("/MDCore\.wurl\s*=\s*'([^']+)'/", $unpackedCode, $wurlMatch)) {
            $DirectLink = 'https:' . $wurlMatch[1];
            $found = true;
        }

        if ($found) {
            echo "Extracted Direct Link: $DirectLink\n";
            
            $urlData = $DirectLink . "|Referer='" . $pReferer .
                "'|Origin='" . $pReferer .
                "'|User-Agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0'";

            $base64 = base64_encode($urlData);
            echo "Success! Result data: video_proxy.php?data=" . $base64 . "\n";
            return 'video_proxy.php?data=' . $base64;
        } else {
            throw new Exception('Couldn\'t extract the source links on Mixdrop');
        }

    } catch (Exception $error) {
        echo 'Error: ' . $error->getMessage() . "\n";
        return false;
    }
}

$embedUrl = "https://mixdrop.my/e/mknx9n81ixxx37l";
$referer = "https://watchpornx.com/";
MixdropExtract($embedUrl, "MixdropTest", $referer);
