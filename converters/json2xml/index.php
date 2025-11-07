<?php
// JSON -> XML converter
// - Accept uploaded .json / .ndjson or pasted JSON
// - Supports object/array top-level JSON
// - Optional attribute convention: keys starting with '@' become XML attributes; '#text' becomes node text
// - Preview XML or download as .xml

function is_assoc(array $arr) { return array_keys($arr) !== range(0, count($arr) - 1); }

function json_to_xml_node($data, DOMElement $parent, DOMDocument $doc, $options) {
    // $data can be scalar, array or object (assoc array)
    if (is_array($data)) {
        if (is_assoc($data)) {
            foreach ($data as $k => $v) {
                if ($k === $options['textKey']) {
                    // set text content
                    $parent->appendChild($doc->createTextNode((string)$v));
                    continue;
                }
                if ($options['attrPrefix'] !== '' && strncmp($k, $options['attrPrefix'], strlen($options['attrPrefix'])) === 0) {
                    $attrName = substr($k, strlen($options['attrPrefix']));
                    $parent->setAttribute($attrName, (string)$v);
                    continue;
                }
                if (is_numeric($k)) {
                    $tag = $options['itemName'];
                } else {
                    $tag = $k;
                }
                if (is_array($v) && !is_assoc($v)) {
                    // numeric array -> multiple child elements
                    foreach ($v as $item) {
                        $child = $doc->createElement($tag);
                        $parent->appendChild($child);
                        json_to_xml_node($item, $child, $doc, $options);
                    }
                } else {
                    $child = $doc->createElement($tag);
                    $parent->appendChild($child);
                    json_to_xml_node($v, $child, $doc, $options);
                }
            }
        } else {
            // sequential array -> multiple item children
            foreach ($data as $item) {
                $child = $doc->createElement($options['itemName']);
                $parent->appendChild($child);
                json_to_xml_node($item, $child, $doc, $options);
            }
        }
    } else {
        // scalar
        $parent->appendChild($doc->createTextNode((string)$data));
    }
}

function parse_raw_input($raw) {
    $raw = trim($raw);
    // ndjson detection: multiple JSON objects on separate lines
    $lines = preg_split('/\r?\n/', $raw);
    $allJson = true; $items = [];
    $countNonEmpty = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $countNonEmpty++;
        $data = json_decode($line, true);
        if (json_last_error() !== JSON_ERROR_NONE) { $allJson = false; break; }
        $items[] = $data;
    }
    if ($allJson && $countNonEmpty > 1) return $items; // NDJSON -> array

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $data;
}

$message = ''; $xmlPreview = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $download = isset($_POST['download']);
    $rootName = preg_replace('/[^A-Za-z0-9_\-]/', '', ($_POST['root'] ?? 'root')) ?: 'root';
    $attrPrefix = $_POST['attr_prefix'] ?? '@';
    $textKey = $_POST['text_key'] ?? '#text';
    $itemName = preg_replace('/[^A-Za-z0-9_\-]/', '', ($_POST['item_name'] ?? 'item')) ?: 'item';
    $pretty = isset($_POST['pretty']);

    $raw = '';
    if (!empty($_FILES['jsonfile']['tmp_name']) && is_uploaded_file($_FILES['jsonfile']['tmp_name'])) {
        $raw = file_get_contents($_FILES['jsonfile']['tmp_name']);
    } else {
        $raw = $_POST['jsontext'] ?? '';
    }

    $parsed = parse_raw_input($raw);
    if ($parsed === null) {
        $message = 'Invalid JSON input.';
    } else {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = $pretty;
        $root = $doc->createElement($rootName);
        $doc->appendChild($root);
        $options = ['attrPrefix' => $attrPrefix, 'textKey' => $textKey, 'itemName' => $itemName];
        // If top-level is sequential array, create multiple children under root
        if (is_array($parsed) && !is_assoc($parsed)) {
            foreach ($parsed as $elem) {
                $child = $doc->createElement($itemName);
                $root->appendChild($child);
                json_to_xml_node($elem, $child, $doc, $options);
            }
        } else {
            json_to_xml_node($parsed, $root, $doc, $options);
        }

        $xml = $doc->saveXML();
        if ($download) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="export.xml"');
            echo $xml; exit;
        }
        $xmlPreview = $xml;
        $message = 'Converted JSON to XML.';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>JSON → XML</title>
  <style>
    body{font-family:Segoe UI,Arial,Helvetica,sans-serif;background:#f7fafc;color:#111;padding:18px}
    .wrap{max-width:980px;margin:0 auto}
    .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 18px rgba(2,6,23,.08);margin-bottom:16px}
    textarea{width:100%;min-height:240px;font-family:monospace;font-size:13px}
    .row{display:flex;gap:12px;margin-top:12px;align-items:center}
    .small{width:160px}
    button{background:#0b5fff;color:#fff;border:0;padding:8px 10px;border-radius:6px;cursor:pointer}
    .note{color:#6b7280}
    pre{background:#0b0b0b;color:#dcdcdc;padding:12px;border-radius:6px;overflow:auto}
    label{font-weight:600}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card"><h1>JSON → XML</h1><div class="note">Paste JSON (or NDJSON) or upload a .json file. Control attribute naming and item tag names below.</div></div>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <label for="jsonfile">Upload JSON file</label>
        <input type="file" id="jsonfile" name="jsonfile" accept="application/json,.json,.ndjson,text/plain">

        <label for="jsontext" style="margin-top:12px">Or paste JSON / NDJSON</label>
        <textarea id="jsontext" name="jsontext"><?php echo isset($_POST['jsontext'])?htmlspecialchars($_POST['jsontext']):'';?></textarea>

        <div class="row">
          <div class="small">
            <label>Root element</label>
            <input type="text" name="root" value="<?php echo htmlspecialchars($_POST['root'] ?? 'root'); ?>">
          </div>
          <div class="small">
            <label>Item element</label>
            <input type="text" name="item_name" value="<?php echo htmlspecialchars($_POST['item_name'] ?? 'item'); ?>">
          </div>
          <div class="small">
            <label>Attr prefix</label>
            <input type="text" name="attr_prefix" value="<?php echo htmlspecialchars($_POST['attr_prefix'] ?? '@'); ?>">
          </div>
          <div style="flex:1">
            <label>Text key</label>
            <input type="text" name="text_key" value="<?php echo htmlspecialchars($_POST['text_key'] ?? '#text'); ?>">
          </div>
        </div>

        <div class="row" style="margin-top:12px">
          <label><input type="checkbox" name="pretty" <?php echo isset($_POST['pretty'])?'checked':''; ?>> Pretty-print XML</label>
        </div>

        <div class="row" style="margin-top:12px">
          <div style="flex:1"></div>
          <div class="row"><button type="submit" name="preview">Convert (Preview)</button>
          <button type="submit" name="download">Convert & Download (.xml)</button></div>
        </div>
      </form>

      <?php if ($message): ?><p><strong><?php echo htmlspecialchars($message); ?></strong></p><?php endif; ?>

      <?php if ($xmlPreview): ?>
        <h3>XML preview</h3>
        <textarea readonly><?php echo htmlspecialchars($xmlPreview); ?></textarea>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Notes & conventions</h3>
      <ul>
        <li>Keys starting with the attribute prefix (default `@`) are converted to XML attributes on the same element.</li>
        <li>The text key (default `#text`) sets the element's text content.</li>
        <li>Arrays become repeated child elements using the configured item element name.</li>
      </ul>
      <h3>Example</h3>
      <pre>{"person":{"@id":123,"name":"Alice","phones":["123","456"]}}</pre>
      <p class="note">Becomes:</p>
      <pre>&lt;root&gt;
  &lt;person id="123"&gt;
    &lt;name&gt;Alice&lt;/name&gt;
    &lt;phones&gt;
      &lt;item&gt;123&lt;/item&gt;
      &lt;item&gt;456&lt;/item&gt;
    &lt;/phones&gt;
  &lt;/person&gt;
&lt;/root&gt;</pre>
    </div>
  </div>
</body>
</html>
