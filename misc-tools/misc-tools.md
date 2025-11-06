
# misc-tools

This folder contains short, self-contained PHP utilities used for quick tasks and demos. Below is a file-by-file summary, notes about what each does, and security/testing recommendations.

## Files and explanations

- `index.php`
	- Purpose: Dynamic file index that lists all `.php` and `.html` files in the directory and links to them.
	- Behavior: Uses `glob('*.{php,html}', GLOB_BRACE)` to enumerate files and excludes itself.
	- Notes: Safe to run. Useful entry point for browsing the small toolset.

- `calc.php`
	- Purpose: Small calculator UI with optional graphing mode.
	- Behavior: Parses simple "Number Operator Number" expressions on the server (safe parsing, no `eval()`), and includes a client-side graphing helper that turns `y = ...` expressions into a Chart.js line chart using `new Function()` for the math expression.
	- Caveats & security: Server-side parsing is restricted and safe. Client-side graphing uses `new Function('x', ...)` — this executes user-provided text in the browser. It's acceptable for a local tool, but avoid exposing it to untrusted users.

- `image-search.php`
	- Purpose: Image search UI that queries Google's Custom Search JSON API and displays results.
	- Behavior: Submits a query to `https://www.googleapis.com/customsearch/v1` using a hard-coded API key and CSE ID, shows thumbnails, supports pagination.
	- Security finding (ACTION REQUIRED): The file contains a literal API key and CSE id near the top. Example pattern found: `$apiKey = 'AIzaSy...'; $cseId = '...';` — DO NOT commit such keys to a public repo.
	- Recommended actions:
		- Remove the literal API key and CSE id from the source.
		- Read them from environment variables or a local, gitignored configuration file (e.g., `.env`) instead.
		- Rotate the API key if it was used while the code was public.

- `ip-info.php`
	- Purpose: IP geolocation lookup using `https://freeipapi.com/api/json/[IP]`.
	- Behavior: Submits a request to the external free IP API, shows parsed fields in a table or raw JSON, optionally links to Google Maps for lat/long.
	- Notes: Depends on an external API — no API keys stored locally. Be mindful of rate limits of the chosen API. Uses `file_get_contents()` with a custom User-Agent.

- `lorem-ipsum.php`
	- Purpose: Lorem ipsum generator (words/sentences/paragraphs).
	- Behavior: Generates randomized placeholder text, with limits (max 100 items) and safe defaults.
	- Notes: Safe to run locally. No external dependencies.

- `password-gen.php`
	- Purpose: Generate random passwords with options for length and character sets.
	- Behavior: Server-side generator guarantees at least one char from each selected type, shuffles, and displays a copy button that uses the Clipboard API.
	- Security notes: Generated passwords show in the browser; treat them as sensitive. The generator is fine for testing but not a replacement for platform password management best practices.

- `wikipedia-search.php`
	- Purpose: Searches Wikipedia via the MediaWiki API and displays results/snippets.
	- Behavior: Uses `file_get_contents()` against `https://en.wikipedia.org/w/api.php` with a custom User-Agent and returns top results.
	- Notes: Safe; respects API policy by sending a User-Agent header. No secrets involved.

## Testing & usage

To quickly test the tools locally using PHP's built-in server:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proish-webtools\misc-tools'
php -S localhost:8000
```

Then open `http://localhost:8000/index.php` and click the tool you want to try.
