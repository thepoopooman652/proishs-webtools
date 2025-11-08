<?php
// PDF -> Image converter
// - Upload a PDF and produce one image per page (PNG/JPG/WEBP)
// - Uses Imagick when available, falls back to ImageMagick 'magick' CLI if present
// - Can return a single image (if PDF has 1 page) or a ZIP archive of images

function tmpPath(string $suffix = ''): string {
    $f = tempnam(sys_get_temp_dir(), 'pdf2img_');
    if ($suffix !== '') {
        $new = $f . $suffix;
        rename($f, $new);
        return $new;
    }
    return $f;
}

function send_file_download(string $path, string $filename, string $contentType = 'application/octet-stream') {
    if (!file_exists($path)) return false;
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    return true;
}

function convert_with_imagick(string $pdfPath, string $format, int $dpi, int $quality): array {
    $outFiles = [];
    $imagick = new Imagick();
    // Set resolution before reading
    $imagick->setResolution($dpi, $dpi);
    try {
        $imagick->readImage($pdfPath);
    } catch (Exception $e) {
        return ['error' => 'Imagick read error: ' . $e->getMessage()];
    }

    $num = $imagick->getNumberImages();
    $i = 0;
    foreach ($imagick as $frame) {
        $frame->setImageFormat($format);
        if (in_array(strtolower($format), ['jpeg','jpg','webp'])) {
            $frame->setImageCompressionQuality($quality);
        }
        // Flatten transparency for formats that don't support it (jpg)
        if (strtolower($format) === 'jpeg' || strtolower($format) === 'jpg') {
            $bg = new Imagick();
            $bg->newImage($frame->getImageWidth(), $frame->getImageHeight(), 'white');
            $bg->compositeImage($frame, Imagick::COMPOSITE_OVER, 0, 0);
            $imgData = $bg->getImageBlob();
            $bg->destroy();
        } else {
            $imgData = $frame->getImageBlob();
        }
        $out = tmpPath('.' . strtolower($format));
        file_put_contents($out, $imgData);
        $outFiles[] = $out;
        $i++;
    }
    $imagick->clear();
    $imagick->destroy();
    return $outFiles;
}

function convert_with_cli(string $pdfPath, string $format, int $dpi, int $quality): array {
    // Use ImageMagick 'magick' or 'convert' command
    $magick = null;
    $which = trim(shell_exec('where magick 2>NUL')) ?: trim(shell_exec('where convert 2>NUL'));
    if (!$which) {
        return ['error' => 'ImageMagick CLI not found (magick/convert)'];
    }
    $outPattern = tmpPath('_out_%03d.' . strtolower($format));
    // magick -density DPI input.pdf out-%03d.png
    $cmd = escapeshellcmd($which) . ' -density ' . intval($dpi) . ' ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outPattern);
    // attempt to set quality for JPEG/WEBP via -quality
    if (in_array(strtolower($format), ['jpg','jpeg','webp'])) {
        $cmd .= ' -quality ' . intval($quality);
    }
    exec($cmd . ' 2>&1', $out, $rc);
    if ($rc !== 0) return ['error' => 'ImageMagick CLI failed: ' . implode('\n', $out)];

    // glob results
    $files = glob(dirname($outPattern) . DIRECTORY_SEPARATOR . basename($outPattern));
    // The tmp pattern may not expand; instead find files matching prefix
    if (!$files) {
        $patternDir = dirname($outPattern);
        $base = basename($outPattern);
        $basePrefix = strstr($base, '%03d', true);
        $files = glob($patternDir . DIRECTORY_SEPARATOR . '*' . '.' . strtolower($format));
    }
    sort($files);
    return $files ?: [];
}

$message = ''; $results = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $format = $_POST['format'] ?? 'png';
    $dpi = intval($_POST['dpi'] ?? 150);
    $quality = intval($_POST['quality'] ?? 90);
    $zip = isset($_POST['zip']);

    $rawPath = '';
    if (!empty($_FILES['pdffile']['tmp_name']) && is_uploaded_file($_FILES['pdffile']['tmp_name'])) {
        $rawPath = $_FILES['pdffile']['tmp_name'];
    }

    if (!$rawPath) {
        $message = 'No PDF uploaded.';
    } else {
        // try Imagick
        if (class_exists('Imagick')) {
            $converted = convert_with_imagick($rawPath, $format, $dpi, $quality);
            if (isset($converted['error'])) {
                $message = $converted['error'];
            } else {
                $results = $converted;
            }
        } else {
            $cli = convert_with_cli($rawPath, $format, $dpi, $quality);
            if (isset($cli['error'])) {
                $message = $cli['error'];
            } else {
                $results = $cli;
            }
        }
    }

    if ($results && !$message) {
        if (count($results) === 1 && !$zip) {
            // serve single image
            $path = $results[0];
            $ctype = 'application/octet-stream';
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if ($ext === 'png') $ctype = 'image/png';
            if (in_array($ext, ['jpg','jpeg'])) $ctype = 'image/jpeg';
            if ($ext === 'webp') $ctype = 'image/webp';
            send_file_download($path, 'page1.' . $ext, $ctype);
            // cleanup
            foreach ($results as $f) @unlink($f);
            exit;
        } else {
            // package into zip
            $zipPath = tmpPath('.zip');
            $za = new ZipArchive();
            if ($za->open($zipPath, ZipArchive::CREATE) === true) {
                $i = 1;
                foreach ($results as $f) {
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    $name = sprintf('page-%03d.%s', $i, $ext);
                    $za->addFile($f, $name);
                    $i++;
                }
                $za->close();
                send_file_download($zipPath, 'images.zip', 'application/zip');
                // cleanup
                foreach ($results as $f) @unlink($f);
                @unlink($zipPath);
                exit;
            } else {
                $message = 'Failed to create ZIP archive.';
            }
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>PDF → Images</title>
  <style>
    body{font-family:Segoe UI,Arial,Helvetica,sans-serif;background:#f7fafc;color:#111;padding:18px}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,.08);margin-bottom:16px}
    input,select{padding:8px;border-radius:6px;border:1px solid #d1d5db}
    button{background:#111827;color:#fff;border:0;padding:8px 10px;border-radius:6px;cursor:pointer}
    textarea{width:100%;min-height:160px}
    .note{color:#6b7280}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card"><h1>PDF → Images</h1><div class="note">Upload a PDF and get one image per page. Requires the PHP Imagick extension or ImageMagick CLI.</div></div>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <label for="pdffile">PDF file</label>
        <input type="file" id="pdffile" name="pdffile" accept="application/pdf">

        <div style="height:12px"></div>
        <label>Format</label>
        <select name="format">
          <option value="png">PNG</option>
          <option value="jpeg">JPEG</option>
          <option value="webp">WEBP</option>
        </select>

        <label style="margin-left:12px">DPI</label>
        <input type="number" name="dpi" value="150" min="72" max="600" style="width:80px">
        <label style="margin-left:12px">Quality (for JPG/WEBP)</label>
        <input type="number" name="quality" value="90" min="10" max="100" style="width:80px">
        <label style="margin-left:12px"><input type="checkbox" name="zip" checked> Zip results</label>

        <div style="height:12px"></div>
        <button type="submit">Convert</button>
      </form>

      <?php if ($message): ?><p><strong><?php echo htmlspecialchars($message); ?></strong></p><?php endif; ?>

      <?php if ($results && is_array($results) && count($results)>0): ?>
        <h3>Generated files</h3>
        <ul>
          <?php foreach ($results as $f): ?><li><?php echo htmlspecialchars(basename($f)); ?></li><?php endforeach; ?>
        </ul>
        <p class="note">Files are available for download immediately; this page does not persist them long-term.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
