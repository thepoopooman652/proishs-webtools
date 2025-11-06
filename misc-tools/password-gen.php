<?php
// Default values
$length = 12;
$include_uppercase = true;
$include_lowercase = true;
$include_numbers = true;
$include_symbols = false;
$generated_password = '';
$error = '';

// Character sets
const LOWERCASE_CHARS = 'abcdefghijklmnopqrstuvwxyz';
const UPPERCASE_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
const NUMBER_CHARS = '0123456789';
const SYMBOL_CHARS = '!@#$%^&*()-_=+[]{}|;:,.<>?'; // Some common symbols

/**
 * Generates a random password based on specified criteria.
 *
 * @param int $length The desired length of the password.
 * @param bool $include_uppercase Whether to include uppercase letters.
 * @param bool $include_lowercase Whether to include lowercase letters.
 * @param bool $include_numbers Whether to include numbers.
 * @param bool $include_symbols Whether to include symbols.
 * @return string The generated password or an empty string if an error occurs.
 */
function generate_password(int $length, bool $include_uppercase, bool $include_lowercase, bool $include_numbers, bool $include_symbols): string {
    $character_pool = '';
    $password_chars = []; // To ensure at least one of each selected type

    // Build the character pool and ensure at least one of each selected type
    if ($include_lowercase) {
        $character_pool .= LOWERCASE_CHARS;
        $password_chars[] = LOWERCASE_CHARS[rand(0, strlen(LOWERCASE_CHARS) - 1)];
    }
    if ($include_uppercase) {
        $character_pool .= UPPERCASE_CHARS;
        $password_chars[] = UPPERCASE_CHARS[rand(0, strlen(UPPERCASE_CHARS) - 1)];
    }
    if ($include_numbers) {
        $character_pool .= NUMBER_CHARS;
        $password_chars[] = NUMBER_CHARS[rand(0, strlen(NUMBER_CHARS) - 1)];
    }
    if ($include_symbols) {
        $character_pool .= SYMBOL_CHARS;
        $password_chars[] = SYMBOL_CHARS[rand(0, strlen(SYMBOL_CHARS) - 1)];
    }

    // Check if any character type was selected
    if (empty($character_pool)) {
        return ''; // This case should be caught by validation before calling this function
    }

    // Fill the remaining length with random characters from the full pool
    $remaining_length = $length - count($password_chars);
    for ($i = 0; $i < $remaining_length; $i++) {
        $password_chars[] = $character_pool[rand(0, strlen($character_pool) - 1)];
    }

    // Shuffle the array to randomize the order of the guaranteed characters
    shuffle($password_chars);

    return implode('', $password_chars);
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $length = isset($_POST['length']) ? (int)$_POST['length'] : $length;
    $include_uppercase = isset($_POST['include_uppercase']);
    $include_lowercase = isset($_POST['include_lowercase']);
    $include_numbers = isset($_POST['include_numbers']);
    $include_symbols = isset($_POST['include_symbols']);

    // Validation
    if ($length < 4 || $length > 64) { // Reasonable min/max length
        $error = "Password length must be between 4 and 64 characters.";
    } elseif (!$include_uppercase && !$include_lowercase && !$include_numbers && !$include_symbols) {
        $error = "Please select at least one character type.";
    } else {
        $generated_password = generate_password($length, $include_uppercase, $include_lowercase, $include_numbers, $include_symbols);
        if (empty($generated_password) && empty($error)) { // Fallback error, though validation should prevent this
            $error = "An unexpected error occurred during password generation.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Password Generator</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #333;
            max-width: 600px;
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
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        .form-group label {
            flex: 1;
            margin-right: 1rem;
            font-weight: bold;
        }
        .form-group input[type="number"],
        .form-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
        .form-group input[type="number"] {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input[type="checkbox"] {
            transform: scale(1.2); /* Make checkboxes a bit larger */
        }
        .form-actions {
            text-align: center;
            margin-top: 1.5rem;
        }
        .form-actions button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .form-actions button:hover {
            background-color: #0056b3;
        }
        .output-box {
            background-color: #e9f7ef;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #c3e6cb;
            font-size: 1.5rem;
            font-weight: bold;
            word-break: break-all;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .copy-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 4px;
            border: 1px solid #155724;
            background-color: #fff;
            color: #155724;
            cursor: pointer;
            margin-left: 1rem;
            flex-shrink: 0;
            transition: background-color 0.2s, color 0.2s;
        }
        .copy-btn:hover {
            background-color: #d4edda;
        }
        .copy-btn:disabled {
            background-color: #c3e6cb;
            cursor: default;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
            margin-top: 1.5rem;
        }
        .home-link { display: block; text-align: center; margin-top: 1.5rem; color: #007bff; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <h1>PHP Password Generator</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="generator-form">
        <div class="form-group">
            <label for="length">Password Length:</label>
            <input type="number" id="length" name="length" value="<?php echo htmlspecialchars($length); ?>" min="4" max="64">
        </div>
        <div class="form-group">
            <label for="include_lowercase">Include Lowercase (a-z):</label>
            <input type="checkbox" id="include_lowercase" name="include_lowercase" <?php if ($include_lowercase) echo 'checked'; ?>>
        </div>
        <div class="form-group">
            <label for="include_uppercase">Include Uppercase (A-Z):</label>
            <input type="checkbox" id="include_uppercase" name="include_uppercase" <?php if ($include_uppercase) echo 'checked'; ?>>
        </div>
        <div class="form-group">
            <label for="include_numbers">Include Numbers (0-9):</label>
            <input type="checkbox" id="include_numbers" name="include_numbers" <?php if ($include_numbers) echo 'checked'; ?>>
        </div>
        <div class="form-group">
            <label for="include_symbols">Include Symbols (!@#$%...):</label>
            <input type="checkbox" id="include_symbols" name="include_symbols" <?php if ($include_symbols) echo 'checked'; ?>>
        </div>
        <div class="form-actions">
            <button type="submit">Generate Password</button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($generated_password): ?>
        <div class="output-box">
            <span id="password-output"><?php echo htmlspecialchars($generated_password); ?></span>
            <button type="button" id="copy-btn" class="copy-btn" title="Copy to clipboard">Copy</button>
        </div>
    <?php endif; ?>

    <a href="index.php" class="home-link">Back to File Index</a>

    <script>
        // Only run script if the copy button exists on the page
        const copyBtn = document.getElementById('copy-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const passwordOutput = document.getElementById('password-output');
                const password = passwordOutput.innerText;

                // Use the modern Clipboard API
                navigator.clipboard.writeText(password).then(() => {
                    // Provide feedback to the user
                    const originalText = copyBtn.innerText;
                    copyBtn.innerText = 'Copied!';
                    copyBtn.disabled = true;
                    setTimeout(() => {
                        copyBtn.innerText = originalText;
                        copyBtn.disabled = false;
                    }, 2000); // Revert back after 2 seconds
                }).catch(err => {
                    console.error('Failed to copy password: ', err);
                    // Fallback for older browsers or if something goes wrong
                    alert('Failed to copy password. Please copy it manually.');
                });
            });
        }
    </script>

</body>
</html>