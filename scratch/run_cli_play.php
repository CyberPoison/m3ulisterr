<?php
/**
 * CLI Test Harness to execute play.php offline using a custom stream wrapper
 * to mock watchpornx.com and embedseek.online API responses.
 */

// Unregister real https wrapper and register our mock wrapper
stream_wrapper_unregister("https");

class MockHttpStreamWrapper {
    public $context;
    private $position = 0;
    private $data = '';

    public function stream_open($path, $mode, $options, &$opened_path) {
        $path = strtolower($path);
        
        // 1. Mock watchpornx.com page containing SeekStream embed link
        if (strpos($path, 'watchpornx.com') !== false) {
            $this->data = '<html><body>
                <div id="pettabs">
                    <a href="https://my.embedseek.online/#9gxbc">SeekStream Server</a>
                </div>
            </body></html>';
            $this->position = 0;
            return true;
        }
        
        // 2. Mock SeekStream /api/v1/video endpoint returning the hex payload
        if (strpos($path, '/api/v1/video') !== false) {
            $hexFile = __DIR__ . '/encrypted_payload.hex';
            if (file_exists($hexFile)) {
                $this->data = trim(file_get_contents($hexFile));
            } else {
                $this->data = 'error';
            }
            $this->position = 0;
            return true;
        }
        
        // 3. Fallback for other requests (e.g. checkLinkStatusCode or proxy checks)
        $this->data = 'OK';
        $this->position = 0;
        return true;
    }

    public function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof() {
        return $this->position >= strlen($this->data);
    }

    public function stream_stat() {
        return [];
    }
}

stream_wrapper_register("https", "MockHttpStreamWrapper");

// Mock GET and SERVER parameters for play.php
$_GET['movieId'] = '350';
$_GET['type'] = 'movies';
$_GET['dev'] = 'true'; // Enable debug mode

$_SERVER['REQUEST_URI'] = '/movie/Unlimited/vtRFuaSlij0bZIT/350.mp4';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0';

// Clear cache for 1000000000 to force a fresh extraction run
$cacheFile = __DIR__ . '/../cache.json';
if (file_exists($cacheFile)) {
    $cache = json_decode(file_get_contents($cacheFile), true);
    if (isset($cache['1000000000_adult_url'])) {
        unset($cache['1000000000_adult_url']);
        $file_to_write = file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
    }
}

echo "=== STARTING play.php CLI TEST RUN ===\n";
require_once __DIR__ . '/../play.php';
echo "\n=== END play.php CLI TEST RUN ===\n";
?>
