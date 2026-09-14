<?php

function seekStreamDeriveKey($protocol) {
    $R = "10";
    $O = 110;
    $U = 1;
    $cpVal = mb_ord("ᵟ", 'UTF-8');
    $B = str_split((string)$cpVal);
    $N = "";
    foreach ($B as $digit) {
        $N .= mb_chr(intval($R . $digit), 'UTF-8');
    }
    $N .= mb_chr(mb_ord(mb_substr($protocol, 1, 1, 'UTF-8'), 'UTF-8'), 'UTF-8');
    $N .= mb_substr($N, 1, 2, 'UTF-8');
    $N .= mb_chr($O, 'UTF-8') . mb_chr($O - 1, 'UTF-8') . mb_chr($O + 7, 'UTF-8');
    $re = str_split("3579");
    $N .= mb_chr(intval($re[3] . $re[2]), 'UTF-8');
    $N .= mb_chr(intval($re[1] . $re[2]), 'UTF-8');
    $innerVal = intval(intval($re[0]) * $U + $U . $re[3]);
    $N .= mb_chr($innerVal, 'UTF-8') . mb_chr($innerVal, 'UTF-8');
    $val1 = intval($re[3]) * intval($R) + intval($re[3]) * $U;
    $val2 = 97;
    $N .= mb_chr($val1, 'UTF-8') . mb_chr($val2, 'UTF-8');
    return mb_convert_encoding($N, 'UTF-8');
}

function seekStreamDeriveIV($protocol, $hash) {
    $v = $protocol;
    $R2 = $v . "//";
    $O = $hash;
    $U = mb_strlen($v, 'UTF-8') * mb_strlen($R2, 'UTF-8');
    $Nn = 1;
    $B = "";
    for ($Pe = $Nn; $Pe < 10; $Pe++) {
        $B .= mb_chr($Pe + $U, 'UTF-8');
    }
    $re = $Nn . "" . $Nn . "" . $Nn;
    $xe = strlen($re) * mb_ord(mb_substr($O, 0, 1, 'UTF-8'), 'UTF-8');
    $Ze = intval($re) * $Nn + mb_strlen($v, 'UTF-8');
    $k = $Ze + 4;
    $ie = mb_ord(mb_substr($v, $Nn, 1, 'UTF-8'), 'UTF-8');
    $Le = $ie * $Nn - 2;
    $B .= mb_chr($U, 'UTF-8');
    $B .= mb_chr(intval($re), 'UTF-8');
    $B .= mb_chr($xe, 'UTF-8');
    $B .= mb_chr($Ze, 'UTF-8');
    $B .= mb_chr($k, 'UTF-8');
    $B .= mb_chr($ie, 'UTF-8');
    $B .= mb_chr($Le, 'UTF-8');
    return mb_convert_encoding($B, 'UTF-8');
}

$hexPayloadFile = __DIR__ . '/encrypted_payload.hex';
if (!file_exists($hexPayloadFile)) {
    die("Error: encrypted_payload.hex not found!\n");
}

$hexPayload = trim(file_get_contents($hexPayloadFile));
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

$decrypted = rtrim($decrypted, "\0");

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

// Clean up trailing whitespace and any control characters by finding the last closing brace '}'
$lastBrace = strrpos($jsonStr, '}');
if ($lastBrace !== false) {
    $jsonStr = substr($jsonStr, 0, $lastBrace + 1);
}

$data = json_decode($jsonStr, true);

if ($data === null) {
    echo "Decrypted slice first 100:\n" . substr($jsonStr, 0, 100) . "\n";
    die("Error: Failed to parse JSON! Last error: " . json_last_error_msg() . "\n");
}

echo "\nSUCCESS! Decrypted JSON details:\n";
echo "Title: " . ($data['title'] ?? 'N/A') . "\n";
echo "Source: " . ($data['source'] ?? 'N/A') . "\n";
echo "CF: " . ($data['cf'] ?? 'N/A') . "\n";
?>
