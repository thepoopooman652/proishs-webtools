<?php
// Markdown -> TXT converter
// - Accepts uploaded .md files or pasted markdown text
// - Strips common markdown syntax (headings, emphasis, links, images, lists, code fences)
// - Produces plain text preview or forces a .txt download

function markdown_to_text(string $md): string {
    // Normalize line endings
    $text = str_replace(["\r\n","\r"], "\n", $md);

    // Remove code fences (``` ```), keep inner content but mark as block
    $text = preg_replace('/```[\s\S]*?```/m', function($m){
        $inner = preg_replace('/^```.*$/m','',$m[0]);
        // remove the fence lines
        $inner = preg_replace('/^\s*```\s*$/m','',$inner);
        return trim($inner) . "\n";
    }, $text);

    // Remove inline code markers `code`
    $text = preg_replace('/`([^`]*)`/', '$1', $text);

    // Convert ATX headings (#, ##) to plain text (remove leading hashes)
    $text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $text);

    // Remove Setext-style underlined headings (lines of === or ---)
    $text = preg_replace('/^={2,}\s*$/m', '', $text);
    $text = preg_replace('/^-{2,}\s*$/m', '', $text);

    // Remove images: ![alt](url) -> alt
    $text = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '$1', $text);

    // Convert links [text](url) -> text
    $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

    // Remove reference-style links [text][1] or [text]: url
    $text = preg_replace('/\[([^\]]+)\]\s*\[[^\]]*\]/', '$1', $text);
    $text = preg_replace('/^\s*\[[^\]]+\]:.*$/m','', $text);

    // Remove emphasis **bold**, __bold__, *italic*, _italic_
    $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
    $text = preg_replace('/__([^_]+)__/', '$1', $text);
    $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
    $text = preg_replace('/_([^_]+)_/', '$1', $text);

    // Remove HTML tags if any
    $text = strip_tags($text);

    // Remove list markers at start of lines (-, *, +, numbered lists)
    $text = preg_replace('/^\s*[-*+]\s+/m', '- ', $text);
    $text = preg_replace('/^\s*\d+\.\s+/m', '', $text);

    // Blockquotes > at line start -> remove leading > and optional space
    $text = preg_replace('/^\s*>\s?/m', '', $text);

    // Collapse multiple blank lines to max two
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Trim trailing whitespace on each line
    $lines = explode("\n", $text);
    foreach ($lines as &$line) { $line = rtrim($line); }
    $text = implode("\n", $lines);

    // Final trim
    return trim($text) . "\n";
}

$message=''; $txtPreview='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $download = isset($_POST['download']);
    $raw = '';
    if (!empty($_FILES['mdfile']['tmp_name']) && is_uploaded_file($_FILES['mdfile']['tmp_name'])) {
        $raw = file_get_contents($_FILES['mdfile']['tmp_name']);
    } else {
        $raw = $_POST['mdtext'] ?? '';
    }

    if (trim($raw) === '') {
        $message = 'No Markdown input provided.';
    } else {
        $txt = markdown_to_text($raw);
        if ($download) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="export.txt"');
            echo $txt; exit;
        }
        $txtPreview = $txt;
        $message = 'Converted. Download to save as .txt or copy the preview.';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Markdown → TXT Converter</title>
  <style>
    body{font-family:Segoe UI,Arial,Helvetica,sans-serif;background:#f7fafc;color:#111;padding:18px}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,.08);margin-bottom:16px}
    textarea{width:100%;min-height:220px;font-family:monospace;font-size:13px}
    .row{display:flex;gap:12px;margin-top:12px}
    .small{width:150px}
    button{background:#111827;color:#fff;border:0;padding:8px 10px;border-radius:6px;cursor:pointer}
    .note{color:#6b7280}
    pre{background:#0b0b0b;color:#dcdcdc;padding:12px;border-radius:6px;overflow:auto}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Markdown → TXT</h1>
      <p class="note">Paste Markdown or upload a `.md` file. Common Markdown punctuation will be removed and links/images simplified to text.</p>
    </div>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <label for="mdfile">Upload Markdown file</label>
        <input type="file" id="mdfile" name="mdfile" accept="text/markdown,.md,text/*">

        <label for="mdtext" style="margin-top:12px">Or paste Markdown text</label>
        <textarea id="mdtext" name="mdtext"><?php echo isset($_POST['mdtext'])?htmlspecialchars($_POST['mdtext']):'';?></textarea>

        <div class="row" style="margin-top:10px;align-items:center">
          <div class="small">
            <label><input type="checkbox" name="preserve_blanks" checked disabled> Preserve paragraph spacing</label>
          </div>
          <div style="flex:1"></div>
          <div class="row">
            <button type="submit" name="preview">Convert (Preview)</button>
            <button type="submit" name="download">Convert & Download (.txt)</button>
          </div>
        </div>
      </form>

      <?php if ($message): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
      <?php endif; ?>

      <?php if ($txtPreview): ?>
        <h3>Text preview</h3>
        <textarea readonly><?php echo htmlspecialchars($txtPreview); ?></textarea>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Behavior & rules</h3>
      <ul>
        <li>Code fences (```lang ... ```) are unwrapped and preserved as plain text blocks.</li>
        <li>Inline code markers <code>`code`</code> are removed and the inner code is kept.</li>
        <li>Headings (ATX and setext) have leading markers removed.</li>
        <li>Links <code>[text](url)</code> become <code>text</code>. Images <code>![alt](url)</code> become <code>alt</code>.</li>
        <li>List markers are normalized to simple bullets or removed for numbered lists.</li>
      </ul>
      <h3>Examples</h3>
      <pre># Title\n\nParagraph with **bold** and [link](https://example.com)\n\n- Item A\n- Item B\n</pre>
      <p class="note">Converts to:</p>
      <pre>Title\n\nParagraph with bold and link\n\n- Item A\n- Item B\n</pre>
    </div>
  </div>
</body>
</html>
// PLACEHOLDER
