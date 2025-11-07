# json2xml — JSON to XML converter

Overview
--------
`json2xml` converts JSON input into XML. It supports uploaded JSON files, pasted JSON, and NDJSON (newline-delimited JSON). The converter provides configurable conventions for mapping JSON keys to XML attributes and text nodes, and it can pretty-print the result or provide a downloadable `.xml` file.

Features
--------
- Accepts file upload or pasted JSON/NDJSON.
- Handles JSON objects and arrays (top-level arrays become repeated child elements under the root).
- Attribute convention: keys prefixed with a configurable string (default `@`) become XML attributes.
- Text node convention: a configurable key (default `#text`) maps to a node's text content.
- Configurable item element name for array elements (default `item`).
- Preview and download as `.xml`.

Conventions and options
-----------------------
- Root element name: the XML root element name (default `root`).
- Attribute prefix: keys starting with this prefix are interpreted as attributes (default `@`).
- Text key: if an object contains this key, its value becomes the element text content (default `#text`).
- Item element: when arrays are encountered, elements are wrapped in the named tag for repeated entries (default `item`).
- Pretty-print: enable formatted XML output.

Mapping rules
-------------
- Objects map to elements with child elements for each key.
- Numeric arrays produce repeated elements with the configured item element name.
- Keys that start with the attribute prefix become attributes on the element (the attribute name is the remainder of the key).
- The text key sets the element's text content instead of creating a child element.

Examples
--------
Input JSON:

```json
{
  "person": {
    "@id": 123,
    "name": "Alice",
    "phones": ["123","456"]
  }
}
```

Default XML output (with root `root`):

```xml
<root>
  <person id="123">
    <name>Alice</name>
    <phones>
      <item>123</item>
      <item>456</item>
    </phones>
  </person>
</root>
```

Limitations
-----------
- This converter uses a deterministic rule set and is not schema-aware. For advanced XML needs (namespaces, attributes with specific types, mixed content), post-processing or a schema-driven approach may be necessary.

Usage
-----
1. Start a local PHP server in the `json2xml` folder for testing:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proishs-webtools\converters\json2xml'
php -S 127.0.0.1:8000 -t .
```

2. Open `http://127.0.0.1:8000/index.php`, paste JSON or upload a `.json` file, adjust options, then Preview or Download.

Testing checklist
-----------------
1. Convert simple objects, arrays, nested structures.
2. Validate attribute prefix handling (e.g., `{"@id":1}` -> `id="1"`).
3. Convert NDJSON input (multiple JSON objects separated by newlines) and verify repeated child elements.
4. Toggle pretty-print to verify formatted output.

Integration notes
-----------------
- The tool can be invoked programmatically by POSTing a file or the `jsontext` form field and reading the response (as preview or downloaded file).

Changelog
---------
- 2025-11-07: Initial implementation: `index.php` converter and this README.

This README documents the conversion conventions, options and examples for the `json2xml` converter.
