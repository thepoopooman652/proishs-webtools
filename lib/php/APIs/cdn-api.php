<?php

/**
 * A simple and secure file serving API.
 *
 * This script serves files from a designated 'public' directory.
 * It handles requests like:
 * - api.php?file=image.jpg
 * - api.php?file=documents/report.pdf
 *
 * Security measures are in place to prevent directory traversal attacks,
 * ensuring that only files within the 'public' directory can be accessed.
 * Or, when the auth param in the url is equal to $authToken it allows access to the 'private' directory.
 */

// --- Configuration ---

// Define the path to the public directory, relative to this script's location.
$publicDir = __DIR__ . '/public';

// Define the path to the private directory for authenticated access.
$privateDir = __DIR__ . '/private';

// Define the secret authentication token for accessing private files.
$authToken = '[REDACTED]'; // !!! CHANGE THIS TO A STRONG, UNIQUE SECRET !!!
$uploadToken = '[REDACTED]'; // !!! CHANGE THIS TO A STRONG, UNIQUE SECRET !!!
$uploadTokenPrivate = '[REDACTED]'; // !!! CHANGE THIS TO A STRONG, UNIQUE SECRET !!!

// --- Logic ---

// --- UPLOAD HANDLER ---
// This block handles authenticated upload requests.
// It is triggered by providing a 'path' and a valid 'auth' token for uploading.
if (isset($_GET['path']) && isset($_GET['auth']) && $_GET['auth'] === $uploadToken) {
    $uploadRelPath = $_GET['path'];

    // Security: Basic check to prevent directory traversal.
    if (strpos($uploadRelPath, '..') !== false) {
        http_response_code(403);
        die('Forbidden: Invalid upload path.');
    }

    // The destination directory is always within the public folder.
    $uploadDestDir = $publicDir . '/' . $uploadRelPath;

    // Security: Resolve the real path and ensure it's within the public directory.
    $publicDirReal = realpath($publicDir);
    // We create the directory if it doesn't exist before getting the real path.
    if (!is_dir($uploadDestDir)) {
        if (!@mkdir($uploadDestDir, 0755, true)) {
            http_response_code(500);
            die('Error: Could not create upload directory.');
        }
    }
    $uploadDestDirReal = realpath($uploadDestDir);

    if ($uploadDestDirReal === false || strpos($uploadDestDirReal, $publicDirReal) !== 0) {
        http_response_code(403);
        die('Forbidden: Upload path is outside the allowed directory.');
    }

    // If it's a POST request, handle the file uploads.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $uploadMessages = [];
        if (isset($_FILES['uploadedFiles'])) {
            $totalFiles = count($_FILES['uploadedFiles']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['uploadedFiles']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['uploadedFiles']['tmp_name'][$i];
                    $fileName = basename($_FILES['uploadedFiles']['name'][$i]);
                    $destination = $uploadDestDirReal . '/' . $fileName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadMessages[] = "✅ Successfully uploaded " . htmlspecialchars($fileName);
                    } else {
                        $uploadMessages[] = "❌ Failed to upload " . htmlspecialchars($fileName);
                    }
                } elseif ($_FILES['uploadedFiles']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $uploadMessages[] = "❌ Error with file " . htmlspecialchars($_FILES['uploadedFiles']['name'][$i]) . ": Error code " . $_FILES['uploadedFiles']['error'][$i];
                }
            }
        }
        if (empty($uploadMessages)) {
            $uploadMessages[] = "No files were selected for upload.";
        }
    }

    // Display the HTML upload page for both GET requests and after POST uploads.
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Uploader</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 2em; }
        .container { max-width: 600px; margin: 2em auto; padding: 2em; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; border-bottom: 2px solid #eee; padding-bottom: 0.5em; }
        p { word-break: break-all; }
        .upload-form { margin-top: 1.5em; }
        input[type="file"] { border: 1px solid #ccc; padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { background-color: #007bff; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 1em; }
        button:hover { background-color: #0056b3; }
        .messages { margin-top: 1.5em; background-color: #e9ecef; padding: 1em; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Files</h1>
        <p><strong>Destination:</strong> /public/{$uploadRelPath}</p>
        <form class="upload-form" method="post" enctype="multipart/form-data">
            <input type="file" name="uploadedFiles[]" multiple required>
            <button type="submit">Upload</button>
        </form>
HTML;
    if (!empty($uploadMessages)) {
        echo '<div class="messages"><h3>Upload Status:</h3><p>' . implode('<br>', $uploadMessages) . '</p></div>';
    }
    echo <<<HTML
    </div>
</body>
</html>
HTML;
    exit; // Stop the script after handling the upload request.
}

// --- PRIVATE UPLOAD HANDLER ---
// This block handles authenticated upload requests to the private folder.
if (isset($_GET['path']) && isset($_GET['auth']) && $_GET['auth'] === $uploadTokenPrivate) {
    $uploadRelPath = $_GET['path'];

    // Security: Basic check to prevent directory traversal.
    if (strpos($uploadRelPath, '..') !== false) {
        http_response_code(403);
        die('Forbidden: Invalid upload path.');
    }

    // The destination directory is always within the private folder.
    $uploadDestDir = $privateDir . '/' . $uploadRelPath;

    // Security: Resolve the real path and ensure it's within the private directory.
    $privateDirReal = realpath($privateDir);
    // We create the directory if it doesn't exist before getting the real path.
    if (!is_dir($uploadDestDir)) {
        if (!@mkdir($uploadDestDir, 0755, true)) {
            http_response_code(500);
            die('Error: Could not create upload directory.');
        }
    }
    $uploadDestDirReal = realpath($uploadDestDir);

    if ($uploadDestDirReal === false || strpos($uploadDestDirReal, $privateDirReal) !== 0) {
        http_response_code(403);
        die('Forbidden: Upload path is outside the allowed directory.');
    }

    // If it's a POST request, handle the file uploads.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $uploadMessages = [];
        if (isset($_FILES['uploadedFiles'])) {
            $totalFiles = count($_FILES['uploadedFiles']['name']);
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['uploadedFiles']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['uploadedFiles']['tmp_name'][$i];
                    $fileName = basename($_FILES['uploadedFiles']['name'][$i]);
                    $destination = $uploadDestDirReal . '/' . $fileName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadMessages[] = "✅ Successfully uploaded " . htmlspecialchars($fileName);
                    } else {
                        $uploadMessages[] = "❌ Failed to upload " . htmlspecialchars($fileName);
                    }
                } elseif ($_FILES['uploadedFiles']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $uploadMessages[] = "❌ Error with file " . htmlspecialchars($_FILES['uploadedFiles']['name'][$i]) . ": Error code " . $_FILES['uploadedFiles']['error'][$i];
                }
            }
        }
        if (empty($uploadMessages)) {
            $uploadMessages[] = "No files were selected for upload.";
        }
    }

    // Display the HTML upload page for both GET requests and after POST uploads.
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private File Uploader</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 2em; }
        .container { max-width: 600px; margin: 2em auto; padding: 2em; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; border-bottom: 2px solid #eee; padding-bottom: 0.5em; }
        p { word-break: break-all; }
        .upload-form { margin-top: 1.5em; }
        input[type="file"] { border: 1px solid #ccc; padding: 10px; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { background-color: #dc3545; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; margin-top: 1em; }
        button:hover { background-color: #c82333; }
        .messages { margin-top: 1.5em; background-color: #e9ecef; padding: 1em; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Files to Private</h1>
        <p><strong>Destination:</strong> /private/{$uploadRelPath}</p>
        <form class="upload-form" method="post" enctype="multipart/form-data">
            <input type="file" name="uploadedFiles[]" multiple required>
            <button type="submit">Upload</button>
        </form>
HTML;
    if (!empty($uploadMessages)) {
        echo '<div class="messages"><h3>Upload Status:</h3><p>' . implode('<br>', $uploadMessages) . '</p></div>';
    }
    echo <<<HTML
    </div>
</body>
</html>
HTML;
    exit; // Stop the script after handling the upload request.
}

// --- FILE SERVING LOGIC ---
// This part of the script remains unchanged and handles serving files.

// 1. Get the requested file path from the query string.
$requestedFile = $_GET['file'] ?? '';
$providedAuth = $_GET['auth'] ?? null;

// Determine which directory to use based on authentication.
$baseDir = $publicDir; // Default to public.
if ($providedAuth !== null && $providedAuth === $authToken) {
    $baseDir = $privateDir; // Switch to private if auth token is correct.
}

// 2. Validate input: Ensure a file was requested.
if (empty($requestedFile)) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Bad Request: No file specified.']);
    exit;
}

// 3. Security: Prevent directory traversal attacks.
// The requested path should not contain '..' components.
if (strpos($requestedFile, '..') !== false) {
    http_response_code(403); // Forbidden
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden: Invalid file path.']);
    exit;
}

// 4. Construct the full, absolute path to the requested file.
$filePath = $baseDir . '/' . $requestedFile;

// 5. Security: Resolve the real path and verify it's within the public directory.
// This is the most critical security check. It prevents accessing files
// outside the $publicDir, even with complex paths or symlinks.
$baseDirReal = realpath($baseDir);
$filePathReal = realpath($filePath);

if ($filePathReal === false || strpos($filePathReal, $baseDirReal) !== 0) {
    http_response_code(404); // Not Found
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found: The requested file does not exist.']);
    exit;
}

// 6. Check if the path points to an actual file (not a directory).
if (!is_file($filePathReal)) {
    http_response_code(404); // Not Found
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found: The requested resource is not a file.']);
    exit;
}

// 7. Serve the file with the correct headers.
$mimeType = mime_content_type($filePathReal) ?: 'application/octet-stream';
$fileSize = filesize($filePathReal);

header("Content-Type: $mimeType");
header("Content-Length: $fileSize");
header('Content-Disposition: inline; filename="' . basename($requestedFile) . '"');

// Use readfile() to efficiently output the file's contents.
readfile($filePathReal);

exit;
