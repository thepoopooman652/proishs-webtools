<?php

// --- A Simple PHP Proxy Script ---

// This script fetches the content of a URL and returns it to the user.
// To use it, call it with a URL parameter like: proxy.php?url=https://example.com

// WARNING: This is a very basic implementation with significant limitations.
// 1. SECURITY: This is an "open proxy." Anyone could use your server to access any website,
//    which can be abused. For production use, you MUST implement restrictions, such as a
//    whitelist of allowed domains (see $allowed_domains below).
// 2. COMPLEXITY: This script does NOT rewrite URLs within the fetched content (e.g., links to
//    CSS files, images, or other pages). This means it will work well for fetching raw data
//    (like JSON/XML APIs) but will likely result in broken-looking pages for complex websites.

// --- CONFIGURATION ---
// **IMPORTANT**: For security, create a whitelist of domains that can be proxied.
// An empty array means all domains are allowed, which is dangerous.
// Example: $allowed_domains = ['example.com', 'api.anotherservice.com'];
$allowed_domains = [/* Uncomment and remove this text to enable whitelist 'google.com', 'youtube.com' 'whatismyipaddress.com', 'example.com', 'codestore.kesug.com', 'proishtesting.fwh.is', 'tests.fwh.is'*/];
// -------------------

if (!isset($_GET['url'])) {
    http_response_code(400);
    die('Error: The "url" parameter is missing.');
}

$url = $_GET['url'];

// Re-assemble the URL with any additional query parameters from the request.
// This is crucial for handling form submissions (method=GET) that are rewritten to use the proxy.
$query_params = [];
// Use $_SERVER['QUERY_STRING'] to get the raw query string.
parse_str($_SERVER['QUERY_STRING'] ?? '', $query_params);
unset($query_params['url']); // Remove our own 'url' parameter

if (!empty($query_params)) {
    // Check if the original URL already has a query string.
    $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';
    $url .= $separator . http_build_query($query_params);
}

// Validate that the provided string is a plausible URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('Error: An invalid URL was provided.');
}

// --- Security Check: Whitelist ---
if (!empty($allowed_domains)) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host || !in_array($host, $allowed_domains, true)) {
        http_response_code(403);
        die('Error: Access to this domain is not permitted.');
    }
}

// Use cURL to fetch the content from the target URL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the response as a string
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow any redirects
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);      // Stop after 10 redirects

// Set a user-agent, as some servers block requests that don't have one
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'PHP-Proxy/1.0');

// --- Handle POST requests ---
// If the client made a POST request to the proxy, forward it.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
}

// The following line is for development. In production, you should have a valid SSL setup.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// We will capture the Content-Type header from the final response.
// Using a header function is more robust than parsing the full header block.
$final_content_type = null;
curl_setopt($ch, CURLOPT_HEADERFUNCTION,
    function($curl, $header) use (&$final_content_type) {
        $len = strlen($header);
        $header_parts = explode(':', $header, 2);
        if (count($header_parts) < 2) {
            return $len;
        }

        $header_name = strtolower(trim($header_parts[0]));
        if ($header_name === 'content-type') {
            // Overwrite with the latest Content-Type header found
            $final_content_type = trim($header_parts[1]);
        }

        return $len;
    }
);

$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    die('cURL Error: ' . curl_error($ch));
}

// Get the final URL after any redirects. This will be our base for rewriting relative URLs.
$final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

curl_close($ch);

// Forward the final Content-Type header from the target to the client
// This ensures the browser renders the content correctly (e.g., as HTML, JSON, or an image)
if ($final_content_type !== null) {
    header('Content-Type: ' . $final_content_type);
}

// --- URL Rewriting for HTML content ---
// If the content is HTML, we need to rewrite relative URLs to point back to the proxy.
// This is necessary for links, forms, images, etc. to work correctly.
if ($body && $final_content_type && stripos($final_content_type, 'text/html') !== false) {

    // Function to convert any URL to an absolute URL.
    // Based on: https://stackoverflow.com/a/44462733
    function url_to_absolute($relative_url, $base_url) {
        if (empty(trim($relative_url))) return $base_url;
        if (parse_url($relative_url, PHP_URL_SCHEME) != '') return $relative_url;
        if (strpos($relative_url, 'data:') === 0) return $relative_url;
        if (!($base_parts = parse_url($base_url))) return $relative_url;
        if ($relative_url[0] == '#' || $relative_url[0] == '?') return $base_url . $relative_url;
        if (substr($relative_url, 0, 2) == "//") return $base_parts['scheme'] . ':' . $relative_url;
        
        $path = isset($base_parts['path']) ? preg_replace('#/[^/]*$#', '', $base_parts['path']) : '';
        if ($relative_url[0] == '/') $path = '';
        
        $abs = "$path/$relative_url";
        $abs = preg_replace('#/\./#', '/', $abs);
        while (strpos($abs, '/../') !== false) {
            $abs = preg_replace('#/[^/]+/\.\./#', '/', $abs, 1);
        }
        
        $port = isset($base_parts['port']) ? ':' . $base_parts['port'] : '';
        return $base_parts['scheme'] . '://' . $base_parts['host'] . $port . $abs;
    }

    $doc = new DOMDocument();
    // The @ suppresses warnings from malformed HTML.
    // LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD prevents DOMDocument from adding <html><body> tags.
    @$doc->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $tags_to_rewrite = [
        'a'      => 'href',
        'img'    => 'src',
        'script' => 'src',
        'link'   => 'href',
        'form'   => 'action'
    ];

    foreach ($tags_to_rewrite as $tag_name => $attribute_name) {
        $elements = $doc->getElementsByTagName($tag_name);
        foreach ($elements as $element) {
            if ($element->hasAttribute($attribute_name)) {
                $original_url = $element->getAttribute($attribute_name);
                if (empty(trim($original_url)) || preg_match('/^(javascript|mailto|tel|data):/i', $original_url)) {
                    continue;
                }
                $absolute_url = url_to_absolute($original_url, $final_url);
                $proxied_url = basename(__FILE__) . '?url=' . urlencode($absolute_url);
                $element->setAttribute($attribute_name, $proxied_url);
            }
        }
    }
    $body = $doc->saveHTML();
}

// Output the body of the response
echo $body;
