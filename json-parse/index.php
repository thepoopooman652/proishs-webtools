<?php
// Simple JSON / NDJSON upload and display tool
// Place this file on your webserver and open it in a browser.

$maxFileSize = 100 * 1024 * 1024; // 100 MB
$acceptedExtensions = ['json', 'ndjson', 'txt'];
$allowedMimes = [
	'application/json',
	'application/x-ndjson',
	'text/plain'
];

$errors = [];
$tableHtml = '';
$rawJsonPreview = '';

function is_list_array(array $arr): bool {
	// PHP arrays are lists when keys are 0..n-1
	return array_keys($arr) === range(0, count($arr) - 1);
}

function stringify_value($v) {
	if (is_null($v)) return '';
	if (is_bool($v)) return $v ? 'true' : 'false';
	if (is_scalar($v)) return (string)$v;
	// For arrays / objects, return compact JSON
	return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
	$file = $_FILES['json_file'];

	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'Upload error (code: ' . $file['error'] . ').';
	} else {
		if ($file['size'] > $maxFileSize) {
			$errors[] = 'File too large. Maximum allowed size is ' . ($maxFileSize / (1024*1024)) . ' MB.';
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, $acceptedExtensions, true)) {
			$errors[] = 'Invalid file extension. Please upload a .json, .ndjson or .txt file containing JSON.';
		}

		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);
				if (!in_array($mime, $allowedMimes, true)) {
					$errors[] = 'Warning: uploaded file has MIME type "' . htmlspecialchars($mime) . '" — expected JSON/plain text type.';
				}
			}
		}

		if (empty($errors)) {
			if (is_uploaded_file($file['tmp_name'])) {
				$content = file_get_contents($file['tmp_name']);
				if ($content === false) {
					$errors[] = 'Failed to read uploaded file.';
				} else {
					$rows = [];
					$headers = [];
					$isNdjson = ($ext === 'ndjson') || (strpos($content, "\n") !== false && preg_match('/^\s*\{.+\}\s*$/s', trim($content)) === 0 && preg_match('/\n\s*\{/', $content));

					if ($ext === 'ndjson' || $isNdjson) {
						// Parse NDJSON: each line is one JSON object/value
						$lines = preg_split('/\r?\n/', trim($content));
						foreach ($lines as $line) {
							$line = trim($line);
							if ($line === '') continue;
							$decoded = json_decode($line, true);
							if (json_last_error() !== JSON_ERROR_NONE) {
								$errors[] = 'JSON decode error in NDJSON line: ' . json_last_error_msg();
								break;
							}
							$rows[] = $decoded;
						}
					} else {
						$data = json_decode($content, true);
						if (json_last_error() !== JSON_ERROR_NONE) {
							$errors[] = 'JSON decode error: ' . json_last_error_msg();
						} else {
							// Normalize into rows
							if (is_array($data) && is_list_array($data)) {
								// Array root (list) -> rows
								$rows = $data;
							} elseif (is_array($data)) {
								// Associative object -> single row
								$rows = [$data];
							} else {
								// Scalar -> single cell
								$rows = [[$data]];
							}
						}
					}

					if (empty($errors)) {
						// Determine columns
						$maxCols = 0;
						$isAssocRows = false;
						foreach ($rows as $r) {
							if (is_array($r) && !is_list_array($r)) {
								$isAssocRows = true;
								foreach (array_keys($r) as $k) $headers[$k] = true;
							} elseif (is_array($r)) {
								$maxCols = max($maxCols, count($r));
							} else {
								$maxCols = max($maxCols, 1);
							}
						}

						if ($isAssocRows) {
							$headers = array_keys($headers);
							// Build table with headers as union of keys
							$tableHtml .= '<div class="table-wrap">';
							$tableHtml .= '<table class="csv-table">';
							$tableHtml .= '<thead><tr>';
							foreach ($headers as $h) {
								$tableHtml .= '<th>' . htmlspecialchars($h) . '</th>';
							}
							$tableHtml .= '</tr></thead>';
							$tableHtml .= '<tbody>';
							foreach ($rows as $r) {
								$tableHtml .= '<tr>';
								foreach ($headers as $h) {
									$val = '';
									if (is_array($r) && array_key_exists($h, $r)) {
										$val = stringify_value($r[$h]);
									}
									$tableHtml .= '<td>' . htmlspecialchars($val) . '</td>';
								}
								$tableHtml .= '</tr>';
							}
							$tableHtml .= '</tbody>';
							$tableHtml .= '</table>';
							$tableHtml .= '</div>';
						} else {
							// Indexed rows (arrays) or scalars
							$tableHtml .= '<div class="table-wrap">';
							$tableHtml .= '<table class="csv-table">';
							if ($maxCols > 0) {
								// Optionally add numeric headers (Col 1, Col 2...)
								$tableHtml .= '<thead><tr>';
								for ($i = 0; $i < $maxCols; $i++) {
									$tableHtml .= '<th>Col ' . ($i + 1) . '</th>';
								}
								$tableHtml .= '</tr></thead>';
							}
							$tableHtml .= '<tbody>';
							foreach ($rows as $r) {
								$tableHtml .= '<tr>';
								if (is_array($r)) {
									for ($i = 0; $i < $maxCols; $i++) {
										$val = isset($r[$i]) ? stringify_value($r[$i]) : '';
										$tableHtml .= '<td>' . htmlspecialchars($val) . '</td>';
									}
								} else {
									$tableHtml .= '<td>' . htmlspecialchars(stringify_value($r)) . '</td>';
									// pad remaining cols
									for ($i = 1; $i < $maxCols; $i++) $tableHtml .= '<td></td>';
								}
								$tableHtml .= '</tr>';
							}
							$tableHtml .= '</tbody>';
							$tableHtml .= '</table>';
							$tableHtml .= '</div>';
						}

						// Raw pretty JSON preview
						$rawJsonPreview = '<pre style="white-space:pre-wrap;overflow:auto;border:1px solid #e6e6ef;padding:10px;border-radius:6px;background:#fafafa">' . htmlspecialchars(json_encode(json_decode($content), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</pre>';
					}
				}
			} else {
				$errors[] = 'Possible file upload attack detected.';
			}
		}
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>JSON / NDJSON Upload &amp; Preview</title>
	<style>
		:root{font-family:Segoe UI, Roboto, Arial, sans-serif;color:#111}
		body{margin:18px;background:#f7f7fb}
		.container{max-width:1100px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 24px rgba(16,24,40,.08)}
		h1{margin:0 0 12px;font-size:20px}
		form.row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
		input[type=file]{padding:6px}
		.note{font-size:13px;color:#444}
		.errors{color:#8a1f1f;background:#fff1f1;border:1px solid #f3c6c6;padding:10px;margin:12px 0;border-radius:6px}
		.warning{color:#7a5a00;background:#fff8e0;border:1px solid #f0dca1;padding:10px;margin:12px 0;border-radius:6px}
		.table-wrap{overflow:auto;border-radius:6px;border:1px solid #e6e6ef}
		table.csv-table{border-collapse:collapse;width:100%;min-width:600px}
		table.csv-table th, table.csv-table td{padding:8px 10px;border-bottom:1px solid #eef0f6;text-align:left}
		table.csv-table thead th{background:#f1f5ff;position:sticky;top:0;z-index:2}
		table.csv-table tbody tr:nth-child(even){background:#fbfbff}
		table.csv-table tbody tr:hover{background:#f6f9ff}
		.controls{display:flex;gap:12px;align-items:center}
		button{background:#2563eb;color:#fff;border:0;padding:8px 12px;border-radius:6px;cursor:pointer}
		button:hover{opacity:.95}
		.small{font-size:13px;color:#666}
	</style>
</head>
<body>
<div class="container">
	<h1>JSON / NDJSON Upload &amp; Preview</h1>
	<p class="note">Upload a JSON or NDJSON file. For NDJSON (newline-delimited JSON) use .ndjson. Max file size 100 MB.</p>

	<?php if (!empty($errors)): ?>
		<?php foreach ($errors as $err): ?>
			<?php if (strpos($err, 'Warning:') === 0): ?>
				<div class="warning"><?php echo htmlspecialchars($err); ?></div>
			<?php else: ?>
				<div class="errors"><?php echo htmlspecialchars($err); ?></div>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>

	<form method="post" enctype="multipart/form-data" class="row" autocomplete="off">
		<div class="controls">
			<input type="file" name="json_file" accept=".json,.ndjson,text/plain" required>
			<button type="submit">Upload &amp; Show</button>
		</div>
	</form>

	<?php if ($tableHtml): ?>
		<?php echo $tableHtml; ?>
	<?php endif; ?>

	<?php if ($rawJsonPreview): ?>
		<h3 style="margin-top:16px">Raw / Pretty JSON</h3>
		<?php echo $rawJsonPreview; ?>
	<?php endif; ?>

	<hr>
	<p class="small">Tip: If your JSON is an array of objects it will be shown as rows with object keys as columns. Nested objects/arrays are shown as JSON strings inside cells.</p>
</div>
</body>
</html>

