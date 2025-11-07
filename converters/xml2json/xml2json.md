# xml2json — XML to JSON converter

Overview
--------
`xml2json` converts XML documents into JSON. It accepts uploaded `.xml` files or pasted XML text and returns a JSON representation where attributes are represented with an `@` prefix and element text is placed under `#text` where appropriate. Repeated sibling elements are mapped to JSON arrays.

Mapping conventions
-------------------
- Attributes -> `@attrName` keys
- Element text -> `#text` (only present when element has attributes or children; otherwise the element is a string)
- Repeated sibling elements -> JSON arrays

Examples
--------
Input XML:

```xml
<person id="123">
  <name>Alice</name>
  <phones>
    <phone>123</phone>
    <phone>456</phone>
  </phones>
</person>
```

Output JSON:

```json
{
  "person": {
    "@id": "123",
    "name": "Alice",
    "phones": {
      "phone": ["123", "456"]
    }
  }
}
```

Usage
-----
1. Start a local PHP server in the `xml2json` folder for testing:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proishs-webtools\converters\xml2json'
php -S 127.0.0.1:8000 -t .
```

2. Open `http://127.0.0.1:8000/index.php`, paste XML or upload an `.xml` file, then Preview or Download the JSON result.

Limitations and notes
---------------------
- The conversion follows the conventions above and is intended for inspection, lightweight transformations, or scripting. For schema-aware conversions or special namespace handling, a more advanced, schema-driven approach should be used.
- CDATA sections and mixed content are preserved as text nodes where appropriate.

Testing checklist
-----------------
1. Convert XML with attributes and verify the `@`-prefixed keys.
2. Convert XML with repeated sibling elements and check for arrays in the output.
3. Convert nested XML structures, CDATA, and elements with text and attributes.

Changelog
---------
- 2025-11-07: Initial implementation of XML→JSON converter and documentation.

This README documents the mapping conventions, usage, examples and testing steps for the `xml2json` tool.
