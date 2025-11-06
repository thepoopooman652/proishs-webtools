<?php
// Simple CSV upload and display tool
// Drop this file into your webserver and open it in a browser.

$maxFileSize = 10 * 1024 * 1024; // 10 MB
$acceptedExtensions = ['csv', 'txt'];
$allowedMimes = [
	'text/csv',
	'text/plain',
	'application/csv',
	'application/vnd.ms-excel'
];

$errors = [];
$tableHtml = '';
$hasHeader = isset($_POST['has_header']) && $_POST['has_header'] ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
	$file = $_FILES['csv_file'];

	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'Upload error (code: ' . $file['error'] . ').';
	} else {
		if ($file['size'] > $maxFileSize) {
			$errors[] = 'File too large. Maximum allowed size is 2 MB.';
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, $acceptedExtensions, true)) {
			$errors[] = 'Invalid file extension. Please upload a .csv or .txt file.';
		}

		// Basic MIME sniffing (may vary by server)
		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);
				// If the server reports a very different mime, don't hard-fail, but warn
				if (!in_array($mime, $allowedMimes, true)) {
					// allow but add a soft warning
					$errors[] = 'Warning: uploaded file has MIME type "' . htmlspecialchars($mime) . '" — expected a CSV/plain text type.';
				}
			}
		}

		if (empty($errors)) {
			if (is_uploaded_file($file['tmp_name'])) {
				if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
					$rows = [];
					$maxCols = 0;

					while (($data = fgetcsv($handle)) !== false) {
						// If the line parsed as a single column, attempt to split by common separators
						if (count($data) === 1) {
							$line = $data[0];
							if (strpos($line, ';') !== false) {
								$data = str_getcsv($line, ';');
							} elseif (strpos($line, "\t") !== false) {
								$data = str_getcsv($line, "\t");
							}
						}

						$rows[] = $data;
						$maxCols = max($maxCols, count($data));
					}

					fclose($handle);

					if (count($rows) === 0) {
						$errors[] = 'No data found in the uploaded CSV file.';
					} else {
						// Build HTML table safely
						$tableHtml .= '<div class="table-wrap">';
						$tableHtml .= '<table class="csv-table">';

						if ($hasHeader) {
							$header = array_shift($rows);
							$tableHtml .= '<thead><tr>';
							for ($i = 0; $i < $maxCols; $i++) {
								$cell = isset($header[$i]) ? htmlspecialchars($header[$i]) : '';
								$tableHtml .= '<th>' . $cell . '</th>';
							}
							$tableHtml .= '</tr></thead>';
						}

						$tableHtml .= '<tbody>';
						foreach ($rows as $r) {
							$tableHtml .= '<tr>';
							for ($i = 0; $i < $maxCols; $i++) {
								$cell = isset($r[$i]) ? htmlspecialchars($r[$i]) : '';
								$tableHtml .= '<td>' . $cell . '</td>';
							}
							$tableHtml .= '</tr>';
						}
						$tableHtml .= '</tbody>';
						$tableHtml .= '</table>';
						$tableHtml .= '</div>';
					}
				} else {
					$errors[] = 'Failed to open uploaded file for reading.';
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
	<title>CSV Upload &amp; Preview</title>
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
	<h1>CSV Upload &amp; Preview</h1>
	<p class="note">Upload a CSV (comma, semicolon or tab-separated). Max file size 10 MB.</p>

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
			<input type="file" name="csv_file" accept=".csv,text/csv,text/plain" required>
			<label class="small"><input type="checkbox" name="has_header" value="1" <?php echo $hasHeader ? 'checked' : ''; ?>> First row is header</label>
			<button type="submit">Upload &amp; Show</button>
		</div>
	</form>

	<?php if ($tableHtml): ?>
		<?php echo $tableHtml; ?>
	<?php endif; ?>

	<hr>
	<p class="small">Tip: If a CSV appears as a single column, it may be using semicolons or tabs; this tool attempts to detect common separators.</p>
</div>
</body>
</html>
