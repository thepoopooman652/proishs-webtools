<?php
function genUnsaltedHash(string $input): string {
    return hash('sha512', $input);
};
?>