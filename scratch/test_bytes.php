<?php

$key = hex2bin('6b69656d7469656e6d75613931316303');
$iv = hex2bin('313233343536373839306f6975797472');

$hexPayload = trim(file_get_contents(__DIR__ . '/encrypted_payload.hex'));
$encryptedData = hex2bin($hexPayload);

$decrypted = openssl_decrypt($encryptedData, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

echo "Decrypted First 32 Bytes Hex:\n" . bin2hex(substr($decrypted, 0, 32)) . "\n";
echo "Decrypted First 32 Bytes Characters:\n" . substr($decrypted, 0, 32) . "\n";
?>
