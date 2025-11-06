# cdn

Description
-----------
This folder contains a small content delivery / file-serving tool used to host public and private files. It includes an API endpoint for serving files and HTML upload UIs for authenticated uploads.

Key files
---------
- `api.php` — Main file-serving API. Serves files from `/public` and, when authenticated via an API token, from `/private`. Implements directory traversal protections and upload handlers.
- `index.html` — Documentation and examples for using the CDN API.

Usage
-----
1. Put public assets into the `public/` subfolder.
2. For private assets, place files under `private/` and access them with the `auth` query parameter and a valid token.
3. Upload endpoints are protected by upload tokens passed as `auth` in the upload URL. Use the included upload UI or POST files directly.

Security notes
--------------
- Do NOT commit API tokens or secrets to the repository. Configure tokens via environment variables (the code reads `MAIN_API_KEY`, `UPLOAD_API_KEY`, and `PRIVATE_API_UPLOAD_KEY`).
- Remove any debug logging that prints auth tokens before publishing.
- Rotate any tokens that were previously committed.

Testing
-------
Run a local PHP server in this folder and try requesting files and using the upload UI:

```powershell
cd 'C:\path\to\proish-webtools\cdn'
php -S localhost:8000
```
Open http://localhost:8000/api.php?file=[path] in your browser.

Notes & next steps
------------------
- Consider adding `.gitignore` rules for secrets and an example `.env.example` explaining required env vars.
- Consider removing any hard-coded secrets and reading them from environment variables only.
