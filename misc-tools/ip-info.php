<?php
// Default values
$ip_address = '';
$ip_info = null;
$show_raw = false;
$error = '';

/**
 * Fetches geolocation information for a given IP address.
 *
 * @param string $ip The IP address to look up.
 * @return array An array containing either 'data' or 'error'.
 */
function get_ip_info(string $ip): array {
    $url = "https://freeipapi.com/api/json/" . urlencode($ip);

    // Set a User-Agent header. It's good practice.
    $options = [
        'http' => [
            'header' => "User-Agent: WebTools/1.0 (https://example.com/ip-lookup.php)\r\n"
        ]
    ];
    $context = stream_context_create($options);

    // Use @ to suppress warnings on failure; we'll handle it manually.
    $response_json = @file_get_contents($url, false, $context);

    if ($response_json === false) {
        return ['error' => 'Failed to connect to the IP lookup API. Please try again later.'];
    }

    // Decode the JSON response into an associative array
    $response_data = json_decode($response_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Failed to parse the API response.'];
    }

    // The API returns an "error" key on failure
    if (isset($response_data['error'])) {
        return ['error' => 'API Error: ' . htmlspecialchars($response_data['error'])];
    }

    return ['data' => $response_data];
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_address = isset($_POST['ip_address']) ? trim($_POST['ip_address']) : '';
    $show_raw = isset($_POST['show_raw']);

    if (empty($ip_address)) {
        $error = "Please enter an IP address.";
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error = "The provided IP address is not valid.";
    } else {
        $api_response = get_ip_info($ip_address);
        if (isset($api_response['error'])) {
            $error = $api_response['error'];
        } else {
            $ip_info = $api_response['data'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP IP Address Lookup</title>
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
        .lookup-form {
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
        .lookup-form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .lookup-form button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .lookup-form button:hover {
            background-color: #0056b3;
        }
        .form-option {
            width: 100%;
            text-align: center;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #555;
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
        .results-table { width: 100%; border-collapse: collapse; }
        .results-table th, .results-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .results-table th { font-weight: bold; color: #555; width: 30%; }
        .raw-json {
            background-color: #2d2d2d;
            color: #f1f1f1;
            padding: 1rem;
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .home-link { display: block; text-align: center; margin-top: 1.5rem; color: #007bff; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
        .map-link-container {
            text-align: center;
            margin-top: 1.5rem;
        }
        .map-button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            background-color: #28a745; /* A pleasant green for the map button */
            color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .map-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

    <h1>IP Address Lookup</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="lookup-form">
        <label for="ip_address" class="sr-only">IP Address:</label>
        <input type="text" id="ip_address" name="ip_address" value="<?php echo htmlspecialchars($ip_address); ?>" placeholder="e.g., 8.8.8.8" required autofocus>
        <button type="submit">Lookup</button>
        <div class="form-option">
            <input type="checkbox" id="show_raw" name="show_raw" <?php if ($show_raw) echo 'checked'; ?>>
            <label for="show_raw">Show Raw Info</label>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="error-box"><?php echo $error; ?></div>
    <?php elseif ($ip_info): ?>
        <div class="results-box">
            <h2>Information for <?php echo htmlspecialchars($ip_address); ?></h2>
            <?php if ($show_raw): ?>
                <pre class="raw-json"><code><?php echo htmlspecialchars(json_encode($ip_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
            <?php else: ?>
                <table class="results-table">
                    <?php foreach ($ip_info as $key => $value): ?>
                        <tr>
                            <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                            <td><?php echo is_bool($value) ? ($value ? 'Yes' : 'No') : htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <?php if (!$show_raw && isset($ip_info['latitude']) && isset($ip_info['longitude']) && $ip_info['latitude'] !== null && $ip_info['longitude'] !== null): ?>
                <div class="map-link-container">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($ip_info['latitude'] . ',' . $ip_info['longitude']); ?>" target="_blank" rel="noopener noreferrer" class="map-button">Show Location On Map</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a href="index.php" class="home-link">Back to File Index</a>

</body>
</html>