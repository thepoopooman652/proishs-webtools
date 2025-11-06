<?php
function openInLargeWindow($path) {
    if (empty($path) || !is_string($path)) {
        echo "<!-- Invalid path provided -->";
        return;
    }

    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

    echo <<<JS
<script>
(function() {
    var width = Math.floor(window.screen.availWidth * 0.9);
    var height = Math.floor(window.screen.availHeight * 0.9);
    var left = Math.floor((window.screen.availWidth - width) / 2);
    var top = Math.floor((window.screen.availHeight - height) / 2);

    var newWindow = window.open(
        'about:blank',
        '_blank',
        'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
    );

    if (!newWindow) {
        console.error('Failed to open new window. Pop-up blocker may be enabled.');
        return;
    }

    newWindow.location.href = '{$safePath}';
})();
</script>
JS;
}
?>
