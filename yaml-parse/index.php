<?php
// Simple YAML / newline-delimited YAML upload and preview tool
// Place this file on your webserver and open it in a browser.

$maxFileSize = 5 * 1024 * 1024; // 5 MB
$acceptedExtensions = ['yaml', 'yml', 'txt'];
$allowedMimes = [
	'text/plain',
	'application/x-yaml',
	'text/x-yaml'
];

$errors = [];
$tableHtml = '';
$rawPreview = '';

function is_list_array(array $arr): bool {
	return array_keys($arr) === range(0, count($arr) - 1);
}

function stringify_value($v) {
	if (is_null($v)) return '';
	if (is_bool($v)) return $v ? 'true' : 'false';
	if (is_scalar($v)) return (string)$v;
	return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Minimal fallback YAML parser for simple mapping/list structures.
// This is NOT a full YAML implementation; it's a pragmatic fallback for common cases.
function parse_simple_yaml(string $input) {
	$lines = preg_split('/\r?\n/', $input);
	$root = [];
	$stack = [ [ 'indent' => -1, 'type' => 'map', 'ref' => &$root ] ];

	foreach ($lines as $raw) {
		// strip comments and trailing spaces
		$line = preg_replace('/\s+#.*$/', '', rtrim($raw));
		if ($line === '') continue;

		// count leading spaces
		preg_match('/^(\s*)/', $raw, $m);
		$indent = strlen($m[1]);
		$trim = ltrim($line);

		// find parent according to indent
		while (!empty($stack) && $indent <= end($stack)['indent']) {
			array_pop($stack);
		}
		$parent = &end($stack)['ref'];

		if (strpos($trim, '- ') === 0) {
			// list item
			$valPart = substr($trim, 2);
			if (!is_array($parent) || (is_array($parent) && !is_list_array($parent))) {
				// convert parent to a list if needed
				$parent = [];
				// update reference in stack
				end($stack);
				$stack[count($stack)-1]['ref'] = &$parent;
			}

			// if valPart contains key: value inline, treat as mapping
			if (preg_match('/^([A-Za-z0-9_\-]+)\s*:\s*(.*)$/', $valPart, $kv)) {
				$item = [];
				$item[$kv[1]] = $kv[2] === '' ? null : parse_scalar($kv[2]);
				$parent[] = $item;
				// push item to stack to allow nested keys under this list item
				$idx = count($parent) - 1;
				$stack[] = [ 'indent' => $indent, 'type' => 'map', 'ref' => &$parent[$idx] ];
			} else {
				// plain scalar list item
				$parent[] = parse_scalar($valPart);
				// leave stack as is
			}
		} elseif (preg_match('/^([A-Za-z0-9_\-]+)\s*:\s*(.*)$/', $trim, $kv)) {
			$key = $kv[1];
			$val = $kv[2];
			if ($val === '') {
				// start nested map or list under this key
				$parent[$key] = [];
				$stack[] = [ 'indent' => $indent, 'type' => 'map', 'ref' => &$parent[$key] ];
			} else {
				$parent[$key] = parse_scalar($val);
			}
		} else {
			// unsupported line format, attempt to treat as scalar and append
			if (is_array($parent) && is_list_array($parent)) {
				$parent[] = parse_scalar($trim);
			} else {
				// assign to a special key
				$parent['content'][] = parse_scalar($trim);
			}
		}
	}

	return $root;
}

function parse_scalar($s) {
	$s = trim($s);
	if ($s === '~' || strtolower($s) === 'null') return null;
	if (strtolower($s) === 'true') return true;
	if (strtolower($s) === 'false') return false;
	if (is_numeric($s)) {
		// integer or float
		return (strpos($s, '.') !== false) ? (float)$s : (int)$s;
	}
	// strip quotes
	if ((strpos($s, '"') === 0 && strrpos($s, '"') === strlen($s)-1) || (strpos($s, "'") === 0 && strrpos($s, "'") === strlen($s)-1)) {
		return substr($s, 1, -1);
	}
	return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['yaml_file'])) {
	$file = $_FILES['yaml_file'];

	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'Upload error (code: ' . $file['error'] . ').';
	} else {
		if ($file['size'] > $maxFileSize) {
			$errors[] = 'File too large. Maximum allowed size is ' . ($maxFileSize / (1024*1024)) . ' MB.';
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, $acceptedExtensions, true)) {
			$errors[] = 'Invalid file extension. Please upload a .yaml, .yml or .txt file containing YAML.';
		}

		if (function_exists('finfo_file')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			if ($finfo) {
				$mime = finfo_file($finfo, $file['tmp_name']);
				finfo_close($finfo);
				if (!in_array($mime, $allowedMimes, true)) {
					$errors[] = 'Warning: uploaded file has MIME type "' . htmlspecialchars($mime) . '" — expected YAML/plain text type.';
				}
			}
		}

		if (empty($errors)) {
			if (is_uploaded_file($file['tmp_name'])) {
				$content = file_get_contents($file['tmp_name']);
				if ($content === false) {
					$errors[] = 'Failed to read uploaded file.';
				} else {
					$data = null;

					// Prefer ext-yaml if available
					if (function_exists('yaml_parse')) {
						try {
							$data = @yaml_parse($content);
							if ($data === false) {
								$errors[] = 'yaml_parse failed to parse the document.';
							}
						} catch (Throwable $e) {
							$errors[] = 'yaml_parse error: ' . $e->getMessage();
						}
					} elseif (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
						// If project has symfony/yaml installed via composer
						try {
							$data = Symfony\Component\Yaml\Yaml::parse($content);
						} catch (Throwable $e) {
							$errors[] = 'Symfony YAML parse error: ' . $e->getMessage();
						}
					} else {
						// fallback simple parser
						try {
							$data = parse_simple_yaml($content);
						} catch (Throwable $e) {
							$errors[] = 'Fallback YAML parse error: ' . $e->getMessage();
						}
					}

					if (empty($errors)) {
						// Normalize into rows similar to JSON tool
						$rows = [];
						if (is_array($data) && is_list_array($data)) {
							$rows = $data;
						} elseif (is_array($data)) {
							$rows = [$data];
						} else {
							$rows = [[$data]];
						}

						// Determine headers and render table
						$headers = [];
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
							$tableHtml .= '<div class="table-wrap">';
							$tableHtml .= '<table class="csv-table">';
							if ($maxCols > 0) {
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
									for ($i = 1; $i < $maxCols; $i++) $tableHtml .= '<td></td>';
								}
								$tableHtml .= '</tr>';
							}
							$tableHtml .= '</tbody>';
							$tableHtml .= '</table>';
							$tableHtml .= '</div>';
						}

						// Raw preview (show input and pretty JSON conversion)
						$pretty = '';
						if (is_array($data) || is_object($data)) {
							$pretty = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						} else {
							$pretty = htmlspecialchars($content);
						}
						$rawPreview = '<pre style="white-space:pre-wrap;overflow:auto;border:1px solid #e6e6ef;padding:10px;border-radius:6px;background:#fafafa">' . htmlspecialchars($pretty) . '</pre>';
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
	<title>YAML Upload &amp; Preview</title>
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
	<h1>YAML Upload &amp; Preview</h1>
	<p class="note">Upload a YAML file (.yaml or .yml). This tool supports newline-delimited YAML and common mapping/list structures. Max file size 5 MB.</p>

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
			<input type="file" name="yaml_file" accept=".yaml,.yml,text/plain" required>
			<button type="submit">Upload &amp; Show</button>
		</div>
	</form>

	<?php if ($tableHtml): ?>
		<?php echo $tableHtml; ?>
	<?php endif; ?>

	<?php if ($rawPreview): ?>
		<h3 style="margin-top:16px">Raw / Pretty Preview</h3>
		<?php echo $rawPreview; ?>
	<?php endif; ?>

	<hr>
	<p class="small">Notes: If you have complex YAML features (anchors, merges, advanced tags), install the PHP yaml extension (PECL yaml) or composer package symfony/yaml for robust parsing; this page falls back to a simple parser for common cases.</p>
</div>
</body>
</html>

