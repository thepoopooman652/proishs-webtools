<?php
// Default values
$count = 5;
$type = 'paragraphs';
$output = '';

// A standard set of Lorem Ipsum words
const LOREM_WORDS = [
    'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum',
    'dictum', 'mi', 'non', 'egestas', 'cras', 'tincidunt', 'odio', 'eu', 'finibus', 'bibendum', 'mauris',
    'velit', 'posuere', 'erat', 'nec', 'placerat', 'nisl', 'sapien', 'non', 'nisi', 'pellentesque', 'habitant',
    'morbi', 'tristique', 'senectus', 'et', 'netus', 'et', 'malesuada', 'fames', 'ac', 'turpis', 'egestas',
    'vestibulum', 'ante', 'ipsum', 'primis', 'in', 'faucibus', 'orci', 'luctus', 'et', 'ultrices', 'posuere',
    'cubilia', 'curae', 'donec', 'vitae', 'consequat', 'lectus', 'sed', 'mollis', 'mauris', 'et', 'sodales',
    'consequat', 'nunc', 'massa', 'dictum', 'ipsum', 'id', 'molestie', 'quam', 'velit', 'ac', 'massa',
    'fusce', 'finibus', 'odio', 'vel', 'consequat', 'venenatis', 'aenean', 'eget', 'justo', 'ut', 'quam',
    'viverra', 'hendrerit', 'in', 'vel', 'lectus', 'vivamus', 'accumsan', 'turpis', 'eu', 'semper',
    'aliquet', 'nulla', 'facilisi', 'phasellus', 'suscipit', 'accumsan', 'dolor', 'eu', 'luctus', 'metus',
    'placerat', 'vel', 'quisque', 'tincidunt', 'justo', 'ac', 'sodales', 'gravida', 'nunc', 'libero',
    'malesuada', 'massa', 'sed', 'porta', 'ligula', 'massa', 'vel', 'diam'
];

/**
 * Generates a specified number of random words.
 * @param int $count Number of words to generate.
 * @return string The generated words.
 */
function generate_words(int $count): string {
    $words = [];
    for ($i = 0; $i < $count; $i++) {
        $words[] = LOREM_WORDS[array_rand(LOREM_WORDS)];
    }
    return implode(' ', $words);
}

/**
 * Generates a specified number of sentences.
 * @param int $count Number of sentences to generate.
 * @return string The generated sentences.
 */
function generate_sentences(int $count): string {
    $sentences = [];
    for ($i = 0; $i < $count; $i++) {
        $wordCount = rand(8, 15);
        $sentence = generate_words($wordCount);
        $sentences[] = ucfirst($sentence) . '.';
    }
    return implode(' ', $sentences);
}

/**
 * Generates a specified number of paragraphs.
 * @param int $count Number of paragraphs to generate.
 * @return string The generated paragraphs, wrapped in <p> tags.
 */
function generate_paragraphs(int $count): string {
    $paragraphs = [];
    for ($i = 0; $i < $count; $i++) {
        $sentenceCount = rand(4, 8);
        $paragraph = generate_sentences($sentenceCount);
        // Ensure the first paragraph starts with "Lorem ipsum..."
        if ($i === 0) {
            $paragraph = 'Lorem ipsum dolor sit amet, ' . lcfirst($paragraph);
        }
        $paragraphs[] = "<p>" . $paragraph . "</p>";
    }
    return implode("\n", $paragraphs);
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $count = isset($_POST['count']) ? (int)$_POST['count'] : 5;
    $type = isset($_POST['type']) ? $_POST['type'] : 'paragraphs';

    // Basic validation
    if ($count <= 0) {
        $count = 1;
    }
    if ($count > 100) { // Add a reasonable limit
        $count = 100;
    }

    switch ($type) {
        case 'words':
            $output = generate_words($count);
            break;
        case 'sentences':
            $output = generate_sentences($count);
            break;
        case 'paragraphs':
        default:
            $output = generate_paragraphs($count);
            break;
    }
} else {
    // Generate default content for the first visit
    $output = generate_paragraphs($count);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Lorem Ipsum Generator</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
            background-color: #f4f7f6;
        }
        h1 {
            color: #111;
            text-align: center;
        }
        .generator-form {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .generator-form input[type="number"],
        .generator-form select {
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .generator-form button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .generator-form button:hover {
            background-color: #0056b3;
        }
        .output-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            text-align: justify;
        }
        .output-box p {
            margin-top: 0;
            margin-bottom: 1em;
        }
        .output-box p:last-child {
            margin-bottom: 0;
        }
        .home-link { display: block; text-align: center; margin-top: 1.5rem; color: #007bff; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <h1>PHP Lorem Ipsum Generator</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="generator-form">
        <label for="count">Generate:</label>
        <input type="number" id="count" name="count" value="<?php echo htmlspecialchars($count); ?>" min="1" max="100">
        <select name="type" id="type">
            <option value="paragraphs" <?php if ($type === 'paragraphs') echo 'selected'; ?>>Paragraphs</option>
            <option value="sentences" <?php if ($type === 'sentences') echo 'selected'; ?>>Sentences</option>
            <option value="words" <?php if ($type === 'words') echo 'selected'; ?>>Words</option>
        </select>
        <button type="submit">Generate</button>
    </form>

    <?php if ($output): ?>
        <div class="output-box">
            <?php
                // For paragraphs, output is already HTML. For others, escape it.
                if ($type === 'paragraphs') {
                    echo $output;
                } else {
                    echo htmlspecialchars($output);
                }
            ?>
        </div>
    <?php endif; ?>

    <a href="index.php" class="home-link">Back to File Index</a>

</body>
</html>