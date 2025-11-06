<?php
// api.php

// --- CONFIGURATION ---
// The directory where your files are stored.
// IMPORTANT: This MUST end with a forward slash.
define('FILES_BASE_DIR', __DIR__ . '/files/');
define('READ_ONLY_FILES', ['welcome.txt']); // Files that cannot be modified.

// --- LOGGING ---
define('LOG_FILE', __DIR__ . '/editor-log.json');

/**
 * Logs an activity to the editor log file.
 *
 * @param string $logType The type of activity (e.g., 'file_access', 'file_management').
 * @param string $status The status of the activity (e.g., 'success', 'failure').
 * @param array $details Additional context-specific data.
 */
function logActivity(string $logType, string $status, array $details = []): void
{
    $logEntry = [
        'timestamp'   => date('c'),
        'log_type'    => $logType,
        'user_ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'action'      => $_REQUEST['action'] ?? 'none', // Use $_REQUEST to catch POST/GET actions
        'status'      => $status
    ];
    // Use @ to suppress warnings if the log file is not writable.
    @file_put_contents(LOG_FILE, json_encode(array_merge($logEntry, $details), JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// --- SETUP ---
// Get user's IP and ensure their personal directory exists.
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_dir = FILES_BASE_DIR . $user_ip . '/';
if (!is_dir($user_dir)) {
    // Use @ to suppress warnings if the directory already exists due to a race condition.
    @mkdir($user_dir, 0755, true);
}

// --- SECURITY HELPER ---
/**
 * Validates and resolves a user-provided path against the base directory.
 * Prevents directory traversal and ensures user can only access their own
 * IP folder or the root `files` folder.
 *
 * @param string $path The user-provided path.
 * @return string|false The absolute, sanitized path or false if invalid.
 */
function get_safe_path(string $path): string|false
{
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $realBasePath = realpath(FILES_BASE_DIR);
    $realUserIpPath = realpath(FILES_BASE_DIR . $user_ip);

    // Sanitize user input to prevent '..' traversal before realpath resolves it.
    $path = str_replace('..', '', $path);
    $fullPath = FILES_BASE_DIR . $path;
    
    $realFullPath = realpath($fullPath);

    // The path must resolve to a real file.
    if ($realFullPath === false || !is_file($realFullPath)) {
        return false;
    }

    // 1. Must be within the main files directory.
    if (strpos($realFullPath, $realBasePath) !== 0) {
        return false; 
    }

    // 2. Is it in the user's own IP directory? (Allowed)
    $is_in_user_dir = ($realUserIpPath !== false) && (strpos($realFullPath, $realUserIpPath) === 0);

    // 3. Is it a file directly in the root of FILES_BASE_DIR? (Allowed, e.g. welcome.txt)
    $is_in_root = dirname($realFullPath) === $realBasePath;

    if ($is_in_user_dir || $is_in_root) {
        return $realFullPath;
    }

    // Path is in another user's IP folder or an invalid subfolder.
    return false;
}


// --- API ROUTER ---
$action = $_GET['action'] ?? 'none';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'list-files':
            $allFiles = [];
            
            // 1. Get files from the root directory (shared files)
            $rootFiles = array_diff(scandir(FILES_BASE_DIR), ['.', '..', '.gitkeep']);
            foreach ($rootFiles as $file) {
                $filePath = FILES_BASE_DIR . $file;
                if (is_file($filePath)) { // Only list files, not directories
                    $fileSize = filesize($filePath);
                    $type = ($fileSize === 0) ? 'text/plain' : (mime_content_type($filePath) ?: 'application/octet-stream');
                    $allFiles[] = [
                        'name' => $file,
                        'path' => $file,
                        'size' => $fileSize,
                        'type' => $type,
                        'readonly' => in_array($file, READ_ONLY_FILES)
                    ];
                }
            }

            // 2. Get files from the user's IP directory
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $user_dir = FILES_BASE_DIR . $user_ip . '/';
            if (is_dir($user_dir)) {
                $userFiles = array_diff(scandir($user_dir), ['.', '..', 'deleted-files']);
                foreach ($userFiles as $file) {
                    $filePath = $user_dir . $file;
                    if (is_file($filePath)) {
                        $fileSize = filesize($filePath);
                        $type = ($fileSize === 0) ? 'text/plain' : (mime_content_type($filePath) ?: 'application/octet-stream');
                        $allFiles[] = [
                            'name' => $file,
                            'path' => $user_ip . '/' . $file, // Path includes IP folder
                            'size' => $fileSize,
                            'type' => $type,
                            'readonly' => false // User files are never read-only by default
                        ];
                    }
                }
            }

            logActivity('file_management', 'success', ['details' => 'Listed files']);
            echo json_encode(['success' => true, 'files' => $allFiles, 'user_ip' => $user_ip]);
            break;

        case 'get-content':
            $path = $_GET['path'] ?? '';
            if (empty($path)) {
                throw new Exception("File path is required.");
            }

            $safePath = get_safe_path($path);
            if ($safePath === false) {
                throw new Exception("Invalid or forbidden file path.");
            }

            // Special handling for empty files to treat them as text
            if (filesize($safePath) === 0) {
                $mimeType = 'text/plain';
            } else {
                $mimeType = mime_content_type($safePath) ?: 'application/octet-stream';
            }
            
            // For text files, we can return the content as JSON.
            // For all other types (binary), we stream the file directly.
            if (strpos($mimeType, 'text/') === 0 || $mimeType === 'application/json') {
                 echo json_encode([
                    logActivity('file_access', 'success', ['path' => $path]),
                    'success' => true,
                    'content' => file_get_contents($safePath),
                    'mime' => $mimeType
                ]);
            } else {
                // For binary files (images, audio, etc.), we don't use JSON.
                // We clear any previously set headers and stream the file directly.
                // The browser will then handle it based on the Content-Type.
                logActivity('file_access', 'success', ['path' => $path, 'details' => 'Streamed binary file']);
                header_remove(); // Clear JSON content type header
                header("Content-Type: {$mimeType}");
                header("Content-Length: " . filesize($safePath));
                readfile($safePath);
            }
            break;

        case 'create-file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("POST method required.");
            }
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                throw new Exception("Filename is required.");
            }

            // Sanitize filename to prevent directory traversal and invalid characters.
            $filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($filename));
            if (empty($filename) || $filename === "." || $filename === "..") {
                throw new Exception("Invalid filename provided.");
            }

            $user_ip = $_SERVER['REMOTE_ADDR'];
            $user_dir = FILES_BASE_DIR . $user_ip . '/';
            $newFilePath = $user_dir . $filename;
            $relativePath = $user_ip . '/' . $filename;

            if (file_exists($newFilePath)) {
                throw new Exception("File '{$filename}' already exists in your directory.");
            }

            if (file_put_contents($newFilePath, '') === false) {
                throw new Exception("Could not create file. Check server permissions.");
            }

            logActivity('file_management', 'success', ['path' => $relativePath, 'details' => 'File created']);
            echo json_encode(['success' => true, 'message' => 'File created.', 'path' => $relativePath]);
            break;

        case 'save-file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("POST method required.");
            }
            $path = $_POST['path'] ?? '';
            $content = $_POST['content'] ?? '';

            if (empty($path)) {
                throw new Exception("File path is required.");
            }
            
            if (in_array(basename($path), READ_ONLY_FILES)) {
                throw new Exception("Cannot save. This file is read-only.");
            }

            $user_ip = $_SERVER['REMOTE_ADDR'];
            if (strpos($path, $user_ip . '/') !== 0) {
                throw new Exception("Permission denied. You can only save files in your own directory.");
            }

            $safePath = get_safe_path($path);
            if ($safePath === false) {
                throw new Exception("Invalid or forbidden file path for saving.");
            }

            if (file_put_contents($safePath, $content) === false) {
                throw new Exception("Failed to save file. Check server permissions.");
            }

            logActivity('file_management', 'success', ['path' => $path, 'details' => 'File saved']);
            echo json_encode(['success' => true, 'message' => 'File saved successfully.']);
            break;

        case 'upload-file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("POST method required.");
            }

            if (!isset($_FILES['uploadedFile']) || $_FILES['uploadedFile']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['uploadedFile']['error'] ?? UPLOAD_ERR_NO_FILE;
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE   => 'File is larger than upload_max_filesize.',
                    UPLOAD_ERR_FORM_SIZE  => 'File is larger than MAX_FILE_SIZE.',
                    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
                ];
                throw new Exception($errorMessages[$errorCode] ?? 'Unknown upload error.');
            }

            $tmpName = $_FILES['uploadedFile']['tmp_name'];
            // Sanitize the filename to prevent path traversal and other issues.
            $filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($_FILES['uploadedFile']['name']));

            $user_ip = $_SERVER['REMOTE_ADDR'];
            $user_dir = FILES_BASE_DIR . $user_ip . '/';
            $destination = $user_dir . $filename;

            if (file_exists($destination)) {
                throw new Exception("A file named '{$filename}' already exists.");
            }

            if (!move_uploaded_file($tmpName, $destination)) {
                throw new Exception("Failed to move uploaded file.");
            }

            logActivity('file_management', 'success', ['filename' => $filename, 'details' => 'File uploaded']);
            echo json_encode(['success' => true, 'message' => 'File uploaded successfully.']);
            break;

        case 'delete-file':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("POST method required.");
            }
            $path = $_POST['path'] ?? '';
            if (empty($path)) {
                throw new Exception("File path is required.");
            }

            // Security: Prevent deleting read-only files
            if (in_array(basename($path), READ_ONLY_FILES)) {
                throw new Exception("This file is read-only and cannot be deleted.");
            }

            // Security: User can only delete files from their own folder
            $user_ip = $_SERVER['REMOTE_ADDR'];
            if (strpos($path, $user_ip . '/') !== 0) {
                throw new Exception("Permission denied. You can only delete your own files.");
            }

            $safePath = get_safe_path($path);
            if ($safePath === false) {
                throw new Exception("Invalid or forbidden file path for deletion.");
            }

            $deleted_files_dir = dirname($safePath) . '/deleted-files/';
            if (!is_dir($deleted_files_dir)) {
                @mkdir($deleted_files_dir, 0755, true);
            }

            // To prevent overwriting, append a timestamp to the deleted file's name
            $destination = $deleted_files_dir . time() . '_' . basename($safePath);

            if (!rename($safePath, $destination)) {
                throw new Exception("Failed to delete file.");
            }

            logActivity('file_management', 'success', ['path' => $path, 'details' => 'File deleted']);
            echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
            break;

        default:
            throw new Exception("Invalid action.");
    }
} catch (Exception $e) {
    logActivity('api_error', 'failure', ['message' => $e->getMessage()]);
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
