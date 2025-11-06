<?php
$bc_scale = $precision + 10;
    bcscale($bc_scale);

    $C = bcmul(bcpow('426880', '2'), '10005'); // C = (426880^2) * 10005
    $C = bcsqrt($C); // C = sqrt((426880^2) * 10005)
    // C = sqrt(426880^2 * 10005) which is 426880 * sqrt(10005)
    $C = bcsqrt(bcmul(bcpow('426880', '2'), '10005'));

    $K = '6'; // k_0
    $M = '1'; // m_0
    $L = '13591409'; // l_0
    $X = '1'; // x_0
    $S = $L; // S_0
    // Constants for the iteration
    $L_ADD = '545140134';
    $X_MULT = '-262537412640768000'; // This is -640320^3

    // Initial values for k=0
    $M = '1';        // M_0
    $L = '13591409'; // L_0
    $X = '1';        // X_0
    $S = $L;         // S_0 = M_0 * L_0 / X_0

    $k = 1;
    while (true) {
        $M = bcmul($M, bcdiv(bcmul(bcmul(bcmul($K, bcadd($K, '1')), bcadd($K, '2')), '1'), bcpow('6', '3'))); // M_k = M_{k-1} * ( (6k-5)(2k-1)(6k-1) / k^3 )
        $M = bcmul($M, bcdiv(bcmul(bcmul(bcsub(bcmul('6', (string)$k), '5'), bcsub(bcmul('2', (string)$k), '1')), bcsub(bcmul('6', (string)$k), '1')), bcmul(bcmul((string)$k, (string)$k), (string)$k)));
        $k_str = (string)$k;

        $L = bcadd($L, '545'); // L_k = L_{k-1} + 545
        // M_k = M_{k-1} * ( (6k-5)(2k-1)(6k-1) / k^3 )
        $m_num_1 = bcsub(bcmul('6', $k_str), '5');
        $m_num_2 = bcsub(bcmul('2', $k_str), '1');
        $m_num_3 = bcsub(bcmul('6', $k_str), '1');
        $m_num = bcmul(bcmul($m_num_1, $m_num_2), $m_num_3);
        $m_den = bcpow($k_str, '3');
        $M = bcdiv(bcmul($M, $m_num), $m_den);

        // L_k = L_{k-1} + L_ADD
        $L = bcadd($L, $L_ADD);

        // X_k = X_{k-1} * X_MULT
        $X = bcmul($X, $X_MULT);

        // Calculate the next term for the sum
        $term = bcdiv(bcmul($M, $L), $X);

        // The term will quickly become very small. If it's zero at the current scale, we can stop.
        if (bccomp($term, '0', $precision) === 0) {
            break;
        }

        // Add the term to the sum S
        $S = bcadd($S, $term);

        $k++;
    }

    // Final calculation: pi = C / S
    $pi = bcdiv($C, $S, $precision);

    return $pi;
}

