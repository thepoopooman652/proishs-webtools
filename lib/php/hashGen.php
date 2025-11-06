<?php
function genHash(string $input): string {
    $salt = random_bytes(32);
    $hash = hash_hmac('sha512', $input, $salt, true);
    $final = base64_encode($salt . $hash);
    return $final;
}
?>