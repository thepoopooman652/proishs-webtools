<?php
function genString($length, $amount) {
    $length = (int)$length;
    $amount = (int)$amount;

    // Unrandomized character list: abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?~`
    // Randomized character list: @~A]Y<V(R}#t:hZgG$p?l,u[|d=;c>qf!P^S-K5b6D2sL&aC7wUo%eQv_iW10kFjJ3nI9mB4O8x*+E{HTXNMyz
    $charArray = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

    for ($j = 0; $j < $amount; $j++) {
        $result = "";
        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, count($charArray) - 1);
            $result .= $charArray[$randomIndex];
        }
        return $result . PHP_EOL;
    }
}
echo genString(100, 20);
?>
