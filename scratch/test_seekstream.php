<?php
// Set up required GET parameters so play.php doesn't exit prematurely
$_GET['movieId'] = '1000000000';
$_GET['type'] = 'movies';

// Set up server parameters to prevent warnings
$_SERVER['REQUEST_URI'] = '/play.php?movieId=1000000000';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_USER_AGENT'] = 'TestAgent';

// Capture output to prevent play.php from echoing anything during require
ob_start();
require_once __DIR__ . '/../play.php';
ob_end_clean();

$hexPayloadFile = __DIR__ . '/encrypted_payload.hex';
if (!file_exists($hexPayloadFile)) {
    die("Error: encrypted_payload.hex not found!\n");
}

$hexPayload = trim(file_get_contents($hexPayloadFile));
echo "Loaded hex payload length: " . strlen($hexPayload) . "\n";

$protocol = "https:";
$videoId = "9gxbc";

$key = seekStreamDeriveKey($protocol);
$iv = seekStreamDeriveIV($protocol, "#" . $videoId);

echo "Key (hex): " . bin2hex($key) . "\n";
echo "IV (hex): " . bin2hex($iv) . "\n";

$encryptedData = hex2bin($hexPayload);
$decrypted = openssl_decrypt($encryptedData, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

if ($decrypted === false) {
    die("Error: AES decryption failed!\n");
}

// Remove null padding
$decrypted = rtrim($decrypted, "\0");

echo "Decrypted length: " . strlen($decrypted) . "\n";

// Try to find the JSON start
$jsonStart = strpos($decrypted, '{"swarmId"');
if ($jsonStart === false) {
    $jsonStart = strpos($decrypted, '{"source"');
}
if ($jsonStart === false) {
    $jsonStart = strrpos($decrypted, '{');
}

if ($jsonStart === false) {
    die("Error: No JSON start found in decrypted output!\n");
}

$jsonStr = substr($decrypted, $jsonStart);
$data = json_decode($jsonStr, true);

if ($data === null) {
    die("Error: Failed to parse JSON! Last error: " . json_last_error_msg() . "\n");
}

echo "SUCCESS! Decrypted JSON details:\n";
echo "Title: " . ($data['title'] ?? 'N/A') . "\n";
echo "Source: " . ($data['source'] ?? 'N/A') . "\n";
echo "CF: " . ($data['cf'] ?? 'N/A') . "\n";
?>
