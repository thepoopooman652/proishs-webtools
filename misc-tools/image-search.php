<?php

// --- CONFIGURATION ---
// Replace with your own API Key and Search Engine ID
$apiKey = 'AIzaSyD1Xz5hIQRy3aVj8M1LsLthmCcL3VBdNSc';
$cseId = '21e5943854bff4d6a';
// -------------------

$query = '';
$start = 1;
$results = null;
$error = '';

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $query = trim($_GET['query']);
    // Get start index for pagination, default to 1. Must be an integer >= 1.
    $start = isset($_GET['start']) ? filter_var($_GET['start'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 1;
    if ($start === false) {
        $start = 1;
    }

    // The API endpoint. We add the 'start' parameter for pagination.
    // 'num=10' is the maximum number of results per page allowed by the API.
    $url = sprintf(
        "https://www.googleapis.com/customsearch/v1?key=%s&cx=%s&q=%s&searchType=image&num=10&start=%d",
        $apiKey,
        $cseId,
        urlencode($query),
        $start
    );

    // Use cURL to make the API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // The following line is for development on some systems.
    // In production, you should have a valid SSL certificate.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = 'cURL Error: ' . curl_error($ch);
    } else {
        $results = json_decode($response);

        // Check for API errors
        if ($httpCode !== 200 || isset($results->error)) {
            $error = 'API Error: ' . ($results->error->message ?? 'An unknown error occurred.');
            $results = null; // Clear results on error
        }
    }

    curl_close($ch);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Image Search</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        h1 { text-align: center; color: #444; }
        form { display: flex; justify-content: center; margin-bottom: 40px; }
        input[type="text"] { font-size: 1.2em; padding: 10px; width: 50%; max-width: 500px; border: 1px solid #ccc; border-radius: 5px 0 0 5px; }
        button { font-size: 1.2em; padding: 10px 20px; border: 1px solid #007BFF; background-color: #007BFF; color: white; cursor: pointer; border-radius: 0 5px 5px 0; }
        button:hover { background-color: #0056b3; }
        .error { color: #D8000C; background-color: #FFD2D2; padding: 10px; border-radius: 5px; text-align: center; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .image-item { border: 1px solid #ddd; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .image-item a { display: block; text-decoration: none; color: #333; }
        .image-item img { width: 100%; height: 200px; object-fit: cover; display: block; }
        .image-item p { padding: 10px; margin: 0; background-color: #f9f9f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .no-results { text-align: center; font-size: 1.2em; color: #777; }
        .pagination { display: flex; justify-content: center; margin-top: 40px; gap: 20px; }
        .page-link { text-decoration: none; padding: 10px 20px; border: 1px solid #007BFF; color: #007BFF; border-radius: 5px; font-weight: bold; }
        .page-link:hover { background-color: #f0f8ff; }
    </style>
</head>
<body>

    <h1>PHP Image Search</h1>

    <form method="GET" action="">
        <!-- We keep the start parameter out of the form to always start a new search from page 1 -->
        <input type="text" name="query" placeholder="Search for images..." value="<?= htmlspecialchars($query) ?>" required>
        <button type="submit">Search</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($results): ?>
        <div class="image-grid">
            <?php if (isset($results->items) && count($results->items) > 0): ?>
                <?php foreach ($results->items as $item): ?>
                    <div class="image-item">
                        <a href="<?= htmlspecialchars($item->image->contextLink) ?>" target="_blank" rel="noopener noreferrer" title="View original page">
                            <img src="<?= htmlspecialchars($item->link) ?>" alt="<?= htmlspecialchars($item->title) ?>">
                            <p><?= htmlspecialchars($item->title) ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results">No images found for "<?= htmlspecialchars($query) ?>".</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($results && (isset($results->queries->previousPage) || isset($results->queries->nextPage))): ?>
        <div class="pagination">
            <?php if (isset($results->queries->previousPage[0])): ?>
                <?php
                    $prevStart = $results->queries->previousPage[0]->startIndex;
                    $prevLink = sprintf("?query=%s&start=%d", urlencode($query), $prevStart);
                ?>
                <a href="<?= $prevLink ?>" class="page-link">‹ Previous</a>
            <?php endif; ?>

            <?php if (isset($results->queries->nextPage[0])): ?>
                <?php
                    $nextStart = $results->queries->nextPage[0]->startIndex;
                    $nextLink = sprintf("?query=%s&start=%d", urlencode($query), $nextStart);
                ?>
                <a href="<?= $nextLink ?>" class="page-link">Next ›</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>