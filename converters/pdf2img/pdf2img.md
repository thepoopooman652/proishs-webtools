# pdf2img — PDF to image converter

Overview
--------
`pdf2img` converts uploaded PDF files into raster images, producing one image per PDF page. The tool supports PNG, JPEG and WEBP output formats and can produce a single image (for single-page PDFs) or a ZIP archive containing one image per page.

Features
--------
- Upload a PDF or provide it via programmatic POST.
- Output formats: PNG, JPEG, WEBP.
- Control rasterization DPI (dots-per-inch) for resolution control.
- Control JPEG/WEBP quality.
- Optionally package multi-page outputs into a ZIP archive.
- Uses the PHP `Imagick` extension when available; falls back to the ImageMagick CLI (`magick` or `convert`) if present.

Dependencies
------------
- Preferred: PHP `Imagick` extension (ImageMagick binding for PHP). Ensure the extension is enabled in the PHP build used by the webserver.
- Fallback: ImageMagick command-line tools (`magick` or `convert`) available in PATH. Ghostscript is commonly required by ImageMagick to rasterize PDFs.

Quick start (local)
-------------------
1. Start PHP's built-in server in the `pdf2img` folder:

```powershell
cd 'C:\Users\Administrator\Documents\GitHub\proishs-webtools\converters\pdf2img'
php -S 127.0.0.1:8000 -t .
```

2. Open `http://127.0.0.1:8000/index.php` in a browser and upload a PDF. Adjust DPI, quality and output format, then press Convert.

Notes on server configuration
-----------------------------
- Imagick: enable the extension in `php.ini` (Windows builds often include a DLL; Linux installs via package manager or pecl). When Imagick is present the tool will use it by default for conversion.
- ImageMagick CLI: if Imagick is not available, the script attempts to use `magick` or `convert` commands. On Windows install ImageMagick and ensure the installation directory is added to PATH. On Linux install the `imagemagick` package and Ghostscript (`ghostscript` / `gs`) to handle PDF rasterization.

Behavior details
----------------
- DPI: Controls rasterization resolution. Higher values produce larger, higher-detail images but increase CPU and memory usage.
- Quality: Controls JPEG/WEBP compression quality (ignored for PNG). Range 10–100.
- Output packaging: For multi-page PDFs, outputs are zipped by default if the Zip option is selected; otherwise images can be downloaded individually by modifying the page behavior.

Programmatic usage
-------------------
Example curl POST (returns a ZIP if multiple pages and zip option enabled):

```bash
curl -F "pdffile=@document.pdf" -F "format=png" -F "dpi=150" -F "quality=90" -F "zip=1" http://127.0.0.1:8000/index.php -o output.zip
```

Output file naming
------------------
- Single-page PDF: the returned file is the image for page 1 with an appropriate extension.
- Multi-page PDF: images are named `page-001.png`, `page-002.png`, ... inside the ZIP.

Testing checklist
-----------------
1. Convert a single-page PDF to PNG and verify the resulting image renders correctly.
2. Convert a multi-page PDF to PNG and confirm the ZIP contains all page images named sequentially.
3. Try JPEG and WEBP outputs and validate the `quality` parameter changes file size and visual quality.
4. Test with varying `dpi` values (72, 150, 300) and verify resolution changes.
5. If Imagick is unavailable, ensure ImageMagick CLI is available and test the fallback path.

Limitations and notes
---------------------
- The conversion process can be memory- and CPU-intensive for high DPI values and large PDFs. Monitor server resources when using in production.
- The script uses temporary files; ensure the webserver's temp directory has sufficient space and appropriate cleanup policies.
- For very large PDFs or batch processing, consider an asynchronous worker queue rather than synchronous web handling.

Changelog
---------
- 2025-11-07: Initial implementation and documentation added.

This README documents the converter's options, dependencies and usage. For production deployments, configure appropriate timeouts, resource limits and storage policies before exposing the tool publicly.
