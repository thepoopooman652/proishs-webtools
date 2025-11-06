<?php
// Default values
$search_term = '';
$results = [];
$error = '';
$total_hits = 0;

/**
 * Searches Wikipedia for a given term using the MediaWiki API.
 *
 * @param string $term The search term.
 * @return array An array containing either 'results' or 'error'.
 */
function search_wikipedia(string $term): array {
    $base_url = 'https://en.wikipedia.org/w/api.php';
    $params = [
        'action' => 'query',
        'list' => 'search',
        'srsearch' => $term,
        'srprop' => 'snippet',
        'srinfo' => 'totalhits',
        'format' => 'json',
        'srlimit' => 20, // Limit to a reasonable number of results
    ];

    $url = $base_url . '?' . http_build_query($params);

    // Set a User-Agent header as recommended by the MediaWiki API policy.
    // It's good practice to replace the placeholder details with your actual contact info.
    $options = [
        'http' => [
            'header' => "User-Agent: WebTools/1.0 (https://example.com/wikipedia-search.php; user@example.com)\r\n"
        ]
    ];
    $context = stream_context_create($options);

    // Use @ to suppress warnings on failure; we'll handle it manually.
    $response_json = @file_get_contents($url, false, $context);

    if ($response_json === false) {
        return ['error' => 'Failed to connect to the Wikipedia API. Please try again later.'];
    }

    $response_data = json_decode($response_json);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Failed to parse the API response.'];
    }

    if (isset($response_data->error)) {
        return ['error' => 'API Error: ' . htmlspecialchars($response_data->error->info)];
    }

    return [
        'results' => $response_data->query->search ?? [],
        'total_hits' => $response_data->query->searchinfo->totalhits ?? 0
    ];
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';

    if (empty($search_term)) {
        $error = "Please enter a search term.";
    } else {
        $api_response = search_wikipedia($search_term);
        if (isset($api_response['error'])) {
            $error = $api_response['error'];
        } else {
            $results = $api_response['results'];
            $total_hits = $api_response['total_hits'];
            if (empty($results)) {
                $error = "No results found for \"" . htmlspecialchars($search_term) . "\".";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Wikipedia Search</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
            background-color: #f4f7f6;
        }
        h1 {
            color: #111;
            text-align: center;
        }
        .search-form {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .search-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-form button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .search-form button:hover {
            background-color: #0056b3;
        }
        .results-box, .error-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            margin-top: 2rem;
        }
        .error-box {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            text-align: center;
        }
        .results-list { list-style-type: none; padding: 0; margin: 0; }
        .results-list li { padding: 12px 0; border-bottom: 1px solid #eee; }
        .results-list li:last-child { border-bottom: none; }
        .results-list a { text-decoration: none; color: #007bff; font-weight: 500; font-size: 1.1rem; }
        .results-list a:hover { text-decoration: underline; }
        .total-hits {
            color: #6c757d;
            margin-top: -1rem;
            margin-bottom: 1.5rem;
        }
        .result-snippet { color: #555; font-size: 0.9em; margin-top: 5px; }
        .result-snippet .searchmatch { font-weight: bold; background-color: #fff3cd; }
        .home-link { display: block; text-align: center; margin-top: 1.5rem; color: #007bff; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
    </style>
</head>
<body>

    <h1>Wikipedia Search</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="search-form">
        <label for="search_term" class="sr-only">Search Term:</label>
        <input type="text" id="search_term" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="e.g., Roman Empire" required autofocus>
        <button type="submit">Search</button>
    </form>

    <?php if ($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php elseif (!empty($results)): ?>
        <div class="results-box">
            <h2>Results for "<?php echo htmlspecialchars($search_term); ?>"</h2>
            <p class="total-hits">
                Found approximately <?php echo number_format($total_hits); ?> results. Showing the top <?php echo count($results); ?>.
            </p>
            <ul class="results-list">
                <?php foreach ($results as $result): ?>
                    <?php
                        // Construct the permanent link to the Wikipedia article
                        $page_url = 'https://en.wikipedia.org/?curid=' . urlencode($result->pageid);
                        $page_title = htmlspecialchars($result->title);
                        // The snippet contains HTML for highlighting, so we output it directly.
                        $snippet = $result->snippet;
                    ?>
                    <li>
                        <a href="<?php echo $page_url; ?>" target="_blank" rel="noopener noreferrer"><?php echo $page_title; ?></a>
                        <div class="result-snippet"><?php echo $snippet; ?>...</div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <a href="index.php" class="home-link">Back to File Index</a>

</body>
</html>