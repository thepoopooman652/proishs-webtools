# json-parse

Description
-----------
JSON and NDJSON upload & preview tool. Accepts `.json`, `.ndjson` and `.txt` files, normalizes the data into rows, and shows a responsive HTML table. Also displays a pretty-printed raw JSON preview.

Key files
---------
- `index.php` — Upload UI, server-side parsing (json_decode and NDJSON line parsing), table rendering, and a raw JSON preview.

Usage
-----
1. Serve the `json-parse` folder and open `index.php` in a browser.
2. Upload either a JSON file (array or object) or NDJSON (newline-delimited JSON) and review the table and raw output.

Security notes
--------------
- All cell contents are escaped with `htmlspecialchars()` before output.
- File size limits are enforced (default 10 MB).
- For large JSON data sets, the script renders everything in memory — consider pagination or streaming for big files.

Testing
-------
```powershell
cd 'C:\path\to\proish-webtools\json-parse'
php -S localhost:8000
```

Notes
-----
- Nested objects/arrays are stringified inside table cells. If you want to flatten nested objects into columns, I can add that transformation.
