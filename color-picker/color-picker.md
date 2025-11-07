# Color Picker

Overview
--------
A compact, self-contained color picker tool that provides an interactive UI for selecting colors, viewing conversions (HEX, RGB, HSL), maintaining a small palette and history, copying values, exporting CSS variables, and downloading a PNG swatch. The tool is implemented as a single PHP-backed page (`index.php`) that delivers a static HTML/JS UI; no server-side processing is required beyond serving the file.

Features
--------
- Native `<input type="color">` color selection plus copyable HEX/RGB/HSL representations.
- One-click copy buttons for HEX, RGB and HSL values and generated CSS variable text.
- Palette support: add selected colors to a per-session palette (client-side memory), with quick reselect.
- History of most recently used colors with quick-access buttons.
- PNG swatch export (download generated via canvas).
- Optional flattening/export as CSS variable (`:root { --color-primary: #RRGGBB; }`).
- Accessible swatch elements (keyboard focus and Enter handling).

Files
-----
- `index.php` — color picker UI and client-side logic. Includes conversion helpers (hex↔rgb↔hsl), UI wiring, palette/history rendering, copy/download features, and usage/export snippets.
- `color-picker.md` — this documentation file.

Usage
-----
1. Serve the `color-picker` folder from a PHP-capable webserver or static server. For local development, the built-in PHP server works:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proish-webtools\color-picker'
php -S 127.0.0.1:8000 -t .
```

2. Open `http://127.0.0.1:8000/index.php` in a browser.
3. Use the color input to pick a color, or paste/choose from the palette/hitory.
4. Click the copy or export buttons to copy color formats or download a swatch image.

UI details
----------
- Primary controls:
  - Color input: native color picker that updates the preview and values live.
  - Copy buttons: clipboard copy for HEX, RGB and HSL formats.
  - Add to palette: pushes the current color into the palette strip for quick reuse.
  - History buttons: recent colors list exposed as small buttons.
  - Export: copy CSS variable text or download a PNG swatch.

Data formats and conversions
----------------------------
- HEX: `#RRGGBB` (uppercase display)
- RGB: `rgb(r, g, b)` where r/g/b are integers 0–255
- HSL: `hsl(h, s%, l%)` where h is degrees 0–360, s and l are percentages

The page includes conversion functions implemented in JavaScript:
- `hexToRgb(hex)` — converts `#RRGGBB` to `[r,g,b]`.
- `rgbToHex(r,g,b)` — converts numeric RGB to HEX string.
- `rgbToHsl(r,g,b)` — converts numeric RGB to HSL percent values.
- `hslToRgb(h,s,l)` — converts HSL values back to RGB.

Accessibility and keyboard support
----------------------------------
- Swatch elements are keyboard-focusable and support activation via the Enter key.
- Buttons include text labels and tooltip titles for assistive technologies.

Integration
-----------
Embed the generated CSS variable into a project's stylesheet by copying the `:root { --color-primary: #RRGGBB }` snippet shown in the usage area. Example:

```css
:root {
  --color-primary: #2563eb;
}

.button-primary {
  background: var(--color-primary);
  color: white;
}
```

Implementation notes
--------------------
- The tool is client-centric (in-browser). All palette/history are stored in memory only; refreshing the page clears session data.
- PNG export uses an off-screen canvas to produce a simple solid swatch image and triggers a download.

Testing
-------
Manual test plan:
1. Open the UI and pick multiple colors using the native color picker.
2. Verify HEX, RGB, and HSL values update correctly for a selection.
3. Click copy buttons and paste values into a text editor to confirm clipboard behavior.
4. Add several colors to the palette, then click each swatch to ensure it selects correctly.
5. Use keyboard navigation to focus a swatch and press Enter to select.
6. Download a swatch and open the PNG to confirm expected color.

Notes and changelog
-------------------
- 2025-11-07: Initial implementation — `index.php` (UI + JS), `color-picker.md` (documentation).

This documentation focuses on usage, developer integration, format conversions, and manual testing steps for the color picker tool.
