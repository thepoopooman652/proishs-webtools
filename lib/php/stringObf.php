<?php

/**
 * Inserts a specified number of random characters between each character of an input string.
 *
 * This function is useful for light obfuscation, making a string less human-readable
 * at a glance without affecting the original characters' order.
 *
 * @param int $charAmount The number of random characters to insert between each character. Must be a non-negative integer.
 * @param string $input The string to obfuscate.
 * @return string The obfuscated string. Returns the original string if $charAmount is zero or negative.
 */
function stringObf(int $charAmount, string $input): string
{
    if ($charAmount <= 0) {
        return $input;
    }

    // A comprehensive pool of characters for random generation.
    $charPool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?~`';
    $poolLength = strlen($charPool) - 1;

    $result = '';
    $inputChars = str_split($input);
    $inputLength = count($inputChars);

    foreach ($inputChars as $index => $char) {
        $result .= $char;

        // Only add random characters if it's not the last character of the input string.
        if ($index < $inputLength - 1) {
            for ($i = 0; $i < $charAmount; $i++) {
                $result .= $charPool[random_int(0, $poolLength)];
            }
        }
    }

    return $result;
}

/**
 * De-obfuscates a string that was obfuscated with stringObf.
 *
 * This function extracts the original characters from a string that has had
 * a fixed number of random characters inserted between each original character.
 *
 * @param int $charAmount The number of random characters that were inserted between each original character. Must be a non-negative integer.
 * @param string $input The obfuscated string.
 * @return string The de-obfuscated (original) string. Returns the input string if $charAmount is zero or negative.
 */
function stringDeobf(int $charAmount, string $input): string
{
    if ($charAmount <= 0) {
        return $input;
    }

    $result = '';
    $step = 1 + $charAmount;
    $inputLength = strlen($input);

    for ($i = 0; $i < $inputLength; $i += $step) {
        $result .= $input[$i];
    }

    return $result;
}
// --- Example Usage ---
/*
$originalString = "top_secret_message";
$charAmount = 5;

$obfuscatedString = stringObf($charAmount, $originalString);
$deobfuscatedString = stringDeobf($charAmount, $obfuscatedString);

echo "Original: " . $originalString . PHP_EOL;
echo "Obfuscated: " . $obfuscatedString . PHP_EOL;
echo "De-obfuscated: " . $deobfuscatedString . PHP_EOL;
*/