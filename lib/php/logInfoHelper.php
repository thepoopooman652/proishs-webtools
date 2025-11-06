<?php
function sendLogInfo(array $data): bool {
    $url = 'https://tests.fwh.is/Log-Info/log-info.php';

    $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}
$data = [
    'event' => 'UserLogin (test)',
    'username' => 'john_doe',
    'success' => true
];

if (sendLogInfo($data)) {
    echo "Log sent successfully!";
} else {
    echo "Failed to send log.";
}
