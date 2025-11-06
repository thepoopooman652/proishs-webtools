<?php
/**
 * file-api.php
 *
 * This script serves a requested file if a valid authentication key is provided in the URL.
 * It includes security measures to prevent directory traversal and ensures only files
 * within a defined base directory can be accessed.
 *
 * Usage: file-api.php?file=/path/to/your/file.txt&auth=your_secret_key
 */

// --- CONFIGURATION ---
// Define a strong, secret key.
// IMPORTANT: In a production environment, this should NEVER be hardcoded.
// Load it from a secure configuration file, environment variable, or a secrets management service. 
define('AUTH_SECRET', '[REDACTED]'); // !!! CHANGE THIS TO A STRONG, UNIQUE SECRET !!! 

// Define the base directory from which files can be served.
// This example assumes 'file-api.php' is located in 'lib/php' and you want to serve
// files from the 'subl files' directory (two levels up). Adjust as needed.
define('FILES_BASE_DIR', realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR);

/**
 * Validates and resolves a user-provided path against the base directory.
 * Prevents directory traversal attacks and ensures only actual files within
 * the specified base directory are accessible.
 *
 * @param string $path The user-provided relative file path.
 * @param string $baseDir The absolute path to the base directory.
 * @return string|false The absolute, sanitized path to the file, or false if invalid/forbidden.
 */
function get_safe_path(string $path, string $baseDir): string|false
{
    error_log("DEBUG: get_safe_path called with path='{$path}' and baseDir='{$baseDir}'");
    // Normalize the base directory path to ensure it ends with a directory separator.
    $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Basic sanitization: remove any '..' or similar patterns to mitigate traversal attempts.
    // realpath() will handle canonicalization, but this pre-check adds a layer of safety.
    $path = str_replace(['../', '..\\', './', '.\\'], '', $path);
    $path = trim($path, '/\\'); // Remove leading/trailing slashes for consistency.

    // Construct the full absolute path.
    $fullPath = $baseDir . $path; 
    error_log("DEBUG: Constructed fullPath='{$fullPath}'");

    // Resolve the real path. This canonicalizes the path, resolves symlinks,
    // and returns false if the file or directory does not exist.
    $realFullPath = realpath($fullPath);
    error_log("DEBUG: realpath returned realFullPath='" . ($realFullPath === false ? 'false' : $realFullPath) . "'");

    // If realpath returns false, the file/path doesn't exist or is invalid.
    if ($realFullPath === false) {
        error_log("DEBUG: realFullPath is false. File does not exist or path is invalid.");
        return false;
    }

    // Ensure the resolved path points to an actual file, not a directory.
    if (!is_file($realFullPath)) {
        error_log("DEBUG: realFullPath is not a file (it might be a directory or non-existent).");
        return false;
    }

    // CRUCIAL SECURITY CHECK: Ensure the resolved path is strictly within the defined base directory.
    // This prevents an attacker from using symlinks or other tricks to access files outside $baseDir.
    if (strpos($realFullPath, $baseDir) !== 0) {
        error_log("DEBUG: Security check failed: realFullPath '{$realFullPath}' is not within baseDir '{$baseDir}'.");
        return false;
    }

    return $realFullPath;
}

// Get parameters from the URL
$requestedFile = $_GET['file'] ?? '';
$providedAuth = $_GET['auth'] ?? '';

error_log("DEBUG: Requested file='{$requestedFile}', Provided auth='{$providedAuth}'");

// --- Authentication Check ---
if ($providedAuth !== AUTH_SECRET) {
    error_log("DEBUG: Authentication failed. Provided auth does not match AUTH_SECRET.");
    http_response_code(403); // Forbidden
    exit("Unauthorized access.");
}

// --- File Request Handling ---
if (empty($requestedFile)) {
    error_log("DEBUG: Requested file is empty.");
    http_response_code(400); // Bad Request
    exit("File path is required.");
}
$safeFilePath = get_safe_path($requestedFile, FILES_BASE_DIR); 
$safeFilePath = get_safe_path($requestedFile, FILES_BASE_DIR);

if ($safeFilePath === false) {
    // Return a generic error to avoid leaking information about file existence or directory structure.
    http_response_code(404); // Not Found
    exit("File not found or access forbidden.");
}

// Determine MIME type of the file. Requires 'fileinfo' PHP extension.
$mimeType = mime_content_type($safeFilePath);
if ($mimeType === false) {
    $mimeType = 'application/octet-stream'; // Fallback for unknown types or if fileinfo is not enabled.
}

// Set appropriate headers for serving the file.
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($safeFilePath));
// 'inline' suggests the browser display the file, 'attachment' would force a download.
header('Content-Disposition: inline; filename="' . basename($safeFilePath) . '"');

// Output the file content.
readfile($safeFilePath);
exit;
