<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic File List</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        h1 {
            color: #111;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        li {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 8px;
            border-radius: 4px;
            transition: box-shadow 0.2s ease-in-out;
        }
        li:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
        }
        a:hover {
            background-color: #e9ecef;
        }
        .no-files {
            color: #777;
            font-style: italic;
            padding: 12px 15px;
        }
    </style>
</head>
<body>

    <h1>File Index</h1>
    <p>This page dynamically lists all <code>.php</code> and <code>.html</code> files in the current directory.</p>

    <ul>
        <?php
        // Get the current script's filename to exclude it from the list.
        $currentFile = basename(__FILE__);

        // Use glob() to find all .php and .html files. This is more efficient than scanning the directory and filtering.
        $files = glob('*.{php,html}', GLOB_BRACE);

        if (empty($files) || (count($files) === 1 && $files[0] === $currentFile)) {
            echo "<li class='no-files'>No other PHP or HTML files found.</li>";
        } else {
            // Sort files alphabetically.
            sort($files);

            // Loop through the array of found files.
            foreach ($files as $file) {
                // Exclude the current file from the list to prevent a self-referencing loop.
                if ($file === $currentFile) continue;

                // Sanitize the filename for the href attribute and for display.
                echo '<li><a href="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</a></li>';
            }
        }
        ?>
    </ul>

</body>
</html>