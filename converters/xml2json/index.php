<?php
// XML -> JSON converter
// - Accept uploaded .xml or pasted XML
// - Produces JSON where attributes are prefixed (default '@') and element text is '#text'
// - Arrays are created when multiple sibling elements share the same name
// - Preview JSON or download as .json

function domnode_to_array(DOMNode $node) {
    $output = [];
    // element node
    if ($node->hasAttributes()) {
        foreach ($node->attributes as $attr) {
            $output['@' . $attr->name] = $attr->value;
        }
    }

    // children
    $textNodes = [];
    $hasElementChildren = false;
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMText || $child instanceof DOMCdataSection) {
            $val = trim($child->nodeValue);
            if ($val !== '') $textNodes[] = $val;
        } elseif ($child instanceof DOMElement) {
            $hasElementChildren = true;
            $name = $child->nodeName;
            $value = domnode_to_array($child);
            if (isset($output[$name])) {
                if (!is_array($output[$name]) || array_keys($output[$name]) === range(0, count($output[$name]) - 1)) {
                    // already a sequential array
                    $output[$name][] = $value;
                } else {
                    // convert to sequential array
                    $output[$name] = [$output[$name], $value];
                }
            } else {
                $output[$name] = $value;
            }
        }
    }

    if (!$hasElementChildren) {
        if (!empty($textNodes)) {
            $text = implode(' ', $textNodes);
            if (count($output) === 0) return $text; // return scalar if no attributes
            $output['#text'] = $text;
        }
    }

    return $output;
}

$message = ''; $jsonPreview = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $download = isset($_POST['download']);
    $raw = '';
    if (!empty($_FILES['xmlfile']['tmp_name']) && is_uploaded_file($_FILES['xmlfile']['tmp_name'])) {
        $raw = file_get_contents($_FILES['xmlfile']['tmp_name']);
    } else {
        $raw = $_POST['xmltext'] ?? '';
    }

    $raw = trim($raw);
    if ($raw === '') {
        $message = 'No XML input provided.';
    } else {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if (!$doc->loadXML($raw)) {
            $message = 'Invalid XML input.';
        } else {
            $root = $doc->documentElement;
            $arr = [$root->nodeName => domnode_to_array($root)];
            $json = json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($download) {
                header('Content-Type: application/json; charset=utf-8');
                header('Content-Disposition: attachment; filename="export.json"');
                echo $json; exit;
            }
            $jsonPreview = $json;
            $message = 'Converted XML to JSON.';
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>XML → JSON</title>
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
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card"><h1>XML → JSON</h1><div class="note">Paste XML or upload an .xml file. Attributes are represented as keys prefixed with '@' and element text as '#text'.</div></div>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <label for="xmlfile">Upload XML file</label>
        <input type="file" id="xmlfile" name="xmlfile" accept="application/xml,.xml,text/xml">

        <label for="xmltext" style="margin-top:12px">Or paste XML</label>
        <textarea id="xmltext" name="xmltext"><?php echo isset($_POST['xmltext'])?htmlspecialchars($_POST['xmltext']):'';?></textarea>

        <div class="row" style="margin-top:12px;justify-content:flex-end">
          <div class="row"><button type="submit" name="preview">Convert (Preview)</button>
          <button type="submit" name="download">Convert & Download (.json)</button></div>
        </div>
      </form>

      <?php if ($message): ?><p><strong><?php echo htmlspecialchars($message); ?></strong></p><?php endif; ?>

      <?php if ($jsonPreview): ?>
        <h3>JSON preview</h3>
        <textarea readonly><?php echo htmlspecialchars($jsonPreview); ?></textarea>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Notes & conventions</h3>
      <ul>
        <li>XML attributes are represented as keys prefixed with `@`, e.g., `<tag id="1">` → `"@id": "1"`.</li>
        <li>Element text content is stored under `#text` if the element also has attributes or child elements; otherwise plain text is returned.</li>
        <li>Sibling elements with the same name become JSON arrays.</li>
      </ul>
      <h3>Example</h3>
      <pre>&lt;person id="123"&gt;
  &lt;name&gt;Alice&lt;/name&gt;
  &lt;phones&gt;
    &lt;phone&gt;123&lt;/phone&gt;
    &lt;phone&gt;456&lt;/phone&gt;
  &lt;/phones&gt;
&lt;/person&gt;</pre>
      <p class="note">Becomes:</p>
      <pre>{
  "person": {
    "@id": "123",
    "name": "Alice",
    "phones": {
      "phone": ["123", "456"]
    }
  }
}</pre>
    </div>
  </div>
</body>
</html>
