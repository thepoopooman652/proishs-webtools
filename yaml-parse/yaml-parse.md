# yaml-parse

Description
-----------
YAML upload and preview tool. Accepts `.yaml` / `.yml` files and attempts to parse them into rows and columns for display in an HTML table. Uses the PHP `yaml` extension or `symfony/yaml` when available, otherwise falls back to a simple parser for common mapping/list structures.

Key files
---------
- `index.php` — Upload UI, parsing logic (ext-yaml / Symfony YAML / fallback), table rendering, and a raw pretty preview.

Usage
-----
1. Serve the `yaml-parse` folder and open `index.php` in a browser.
2. Upload YAML files (list of objects or single mapping) and review parsed table and raw preview.

Security notes
--------------
- The fallback parser is minimal and *does not* implement the full YAML specification. For robust parsing, install `pecl yaml` or `symfony/yaml` via Composer.
- Always escape output and avoid trusting uploaded content.

Testing
-------
```powershell
cd 'C:\path\to\proish-webtools\yaml-parse'
php -S localhost:8000
```
