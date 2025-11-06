# csv-parse

Description
-----------
Simple CSV upload and preview tool. Accepts CSV / text files and renders the contents as an HTML table. Tries to detect common separators (comma, semicolon, tab).

Key files
---------
- `index.php` — Upload form, server-side parsing using `fgetcsv`, header-row option, output escaping, and CSS for a responsive table.

Usage
-----
1. Place `index.php` in a web-accessible folder and load it in a browser.
2. Upload a `.csv` or `.txt` file; optionally mark the first row as header.

Security notes
--------------
- The script escapes all output with `htmlspecialchars()` to prevent XSS.
- File size limits are enforced (default 2–10 MB depending on file), and uploads are validated via `is_uploaded_file()`.
- Avoid storing uploaded files in public directories without sanitizing filenames.

Testing
-------
Serve the directory with PHP's built-in server and test uploads:

```powershell
cd 'C:\path\to\proish-webtools\csv-parse'
php -S localhost:8000
```

Notes
-----
- For large CSVs consider adding pagination or server-side streaming.
- Optionally add client-side sorting/filtering.
