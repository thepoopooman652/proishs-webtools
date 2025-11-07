# md2txt — Markdown to plain text converter

Overview
--------
`md2txt` is a small web utility that converts Markdown-formatted text into plain text. It accepts uploaded `.md` files or pasted Markdown/MDX text, strips or normalizes Markdown-specific punctuation and constructs, and produces a downloadable `.txt` file or a preview of the converted text.

Goals and scope
---------------
- Remove Markdown control characters and transform common constructs into readable plain text.
- Preserve human-readable content (headings, paragraph text, list items, code blocks) while removing formatting markers.
- Provide a simple UI consistent with other converters in the project (upload or paste, preview, download).

Behavior and transformation rules
---------------------------------
The converter applies a deterministic, rule-based transformation. The following rules summarize the main behaviors:

- Code fences (triple backticks): fence markers are removed, inner code lines are preserved verbatim.
- Inline code (single backticks): backticks are removed and the enclosed text is kept.
- ATX headings (`# Heading`) and Setext headings (underlined `===` / `---`) have leading markers removed; heading text remains.
- Links in the `[text](url)` form are replaced by their link text (`text`). Reference-style link definitions are removed from the output.
- Images `![alt](url)` are replaced with their alt text `alt`.
- Emphasis and strong markers (`*italic*`, `**bold**`, `_italic_`, `__bold__`) are removed while preserving the inner text.
- Lists: unordered list markers (`-`, `*`, `+`) are normalized to `- ` at the start of lines; ordered list numbers (`1. `) have their numbering removed and the item text is preserved.
- Blockquotes (`> `) have the leading marker removed.
- HTML tags, if present, are stripped.
- Repeated blank lines are collapsed to at most two consecutive newline characters.

Limitations
-----------
- The tool uses heuristic, regex-based transformations. It is not a full Markdown parser and may not cover every edge case in extended Markdown flavors or MDX.
- Complex constructs like nested lists, tables, or custom Markdown extensions may be simplified; table formatting is not preserved as a table in plain text.

Usage
-----
1. Serve the `md2txt` folder from a webserver that can serve PHP files. For quick local testing, use PHP's built-in server:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proish-webtools\converters\md2txt'
php -S 127.0.0.1:8000 -t .
```

2. Open `http://127.0.0.1:8000/index.php`.
3. Either upload a `.md` file or paste Markdown into the text area.
4. Click **Convert (Preview)** to view the resulting text in the page, or **Convert & Download (.txt)** to save a `.txt` file.

Examples
--------
Input:

```markdown
# Title

This is **bold** text and a [link](https://example.com).

1. First
2. Second

```

Output (plain text):

```
Title

This is bold text and a link.

First
Second

```

File naming
-----------
- When downloading, the converter provides a default filename `export.txt`. Rename as desired after download.

Testing
-------
Manual test checklist:

1. Upload a variety of Markdown files: simple docs, README.md files, and MDX snippets.
2. Verify headings are preserved but marker characters removed.
3. Verify inline links and images reduce to their readable text alternatives.
4. Confirm code fences retain inner text and inline code markers are removed.
5. Test lists, tables and nested list structures to observe simplification behavior.

Integration notes
-----------------
- The converter follows the project's lightweight, single-file tool pattern. It can be integrated into CI or automation flows by invoking the page via an HTTP POST with a file upload and reading the response (or using the download endpoint).

Changelog
---------
- 2025-11-07: Initial implementation and documentation — adds `index.php` converter and this README.

This document describes the transformation approach and usage for the `md2txt` converter. For workflows that require precise Markdown-to-text fidelity (e.g., preserving tables or conversion to other formats), consider using a full Markdown parsing library or a dedicated converter toolchain.

