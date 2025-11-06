<?php
// Initialize variables to store form data, result, and any errors.
$expression = '';
$result = '';
$error = '';

// Check if the form has been submitted using the POST method.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the expression from the form.
    $expression = isset($_POST['expression']) ? $_POST['expression'] : '';
    // Sanitize by removing anything not a number, dot, or basic operator.
    $sanitized_expression = preg_replace('/[^-+*\/0-9\.]/', '', $expression);

    if ($expression !== $sanitized_expression) {
        $error = "Invalid characters in expression.";
        $expression = htmlspecialchars($expression); // Show the user what they typed
    } else {
        // Regex to parse a simple "number operator number" expression.
        // This is a safe alternative to using eval() and currently supports one operation at a time.
        $pattern = '/^\s*([0-9\.]+)\s*([\+\-\*\/])\s*([0-9\.]+)\s*$/';
        if (preg_match($pattern, $expression, $matches)) {
            $num1 = (float)$matches[1];
            $operator = $matches[2];
            $num2 = (float)$matches[3];

            // Perform the calculation based on the detected operator.
            switch ($operator) {
                case '+':
                    $result = $num1 + $num2;
                    break;
                case '-':
                    $result = $num1 - $num2;
                    break;
                case '*':
                    $result = $num1 * $num2;
                    break;
                case '/':
                    // Handle division by zero, a critical edge case.
                    if ($num2 == 0) {
                        $error = "Error: Cannot divide by zero.";
                    } else {
                        $result = $num1 / $num2;
                    }
                    break;
            }
        } elseif (trim($expression) !== '') {
            // If the expression is not empty and doesn't match, it's invalid.
            $error = "Invalid expression format. Please use 'Number Operator Number' (e.g., 5 * 10).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Chart.js for graphing -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>PHP Calculator</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            line-height: 1.6;
            color: #333;
            max-width: 500px;
            margin: 2rem auto;
            padding: 0 1rem;
            background-color: #f4f7f6;
        }
        h1 {
            color: #111;
            text-align: center;
        }
        .calculator {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        #display {
            width: 100%;
            padding: 15px;
            font-size: 2.2rem;
            text-align: right;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            box-sizing: border-box;
            font-family: monospace;
        }
        .keypad {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .keypad button {
            padding: 20px;
            font-size: 1.2rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: background-color 0.2s, box-shadow 0.2s;
        }
        .keypad button:hover {
            background-color: #f0f0f0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .keypad button.operator {
            background-color: #e6f2ff;
            color: #007bff;
            font-weight: bold;
        }
        .keypad button.equals {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .keypad button.equals:hover {
            background-color: #0056b3;
        }
        .keypad button.clear {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        .result, .error {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 4px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            word-break: break-all; /* Wrap long numbers/errors */
        }
        .result { background-color: #e9f7ef; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .home-link { display: block; text-align: center; margin-top: 1.5rem; color: #007bff; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }

        /* Graphing Styles */
        .graph-options {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.5rem;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 10px;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #007bff; }
        input:checked + .slider:before { transform: translateX(26px); }
        #graph-container {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

    <h1>PHP Calculator</h1>

    <div class="calculator">
        <form id="calculator-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" onsubmit="if(document.getElementById('graphing-mode-toggle').checked) { event.preventDefault(); drawGraph(); }">
            <input type="text" name="expression" id="display" value="<?php echo htmlspecialchars($expression); ?>" autocomplete="off" autofocus>
            <div class="keypad">
                <button type="button" onclick="clearDisplay()" class="clear">C</button> <button type="button" onclick="appendToDisplay('(')">(</button> <button type="button" onclick="appendToDisplay(')')">)</button> <button type="button" onclick="appendToDisplay('/')" class="operator">/</button> <button type="button" onclick="appendToDisplay('7')">7</button> <button type="button" onclick="appendToDisplay('8')">8</button> <button type="button" onclick="appendToDisplay('9')">9</button> <button type="button" onclick="appendToDisplay('*')" class="operator">*</button> <button type="button" onclick="appendToDisplay('4')">4</button> <button type="button" onclick="appendToDisplay('5')">5</button> <button type="button" onclick="appendToDisplay('6')">6</button> <button type="button" onclick="appendToDisplay('-')" class="operator">-</button> <button type="button" onclick="appendToDisplay('1')">1</button> <button type="button" onclick="appendToDisplay('2')">2</button> <button type="button" onclick="appendToDisplay('3')">3</button> <button type="button" onclick="appendToDisplay('+')" class="operator">+</button> <button type="button" onclick="appendToDisplay('0')" style="grid-column: span 2;">0</button> <button type="button" onclick="appendToDisplay('.')">.</button>
                <button type="submit" class="equals">=</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($result !== '' && is_numeric($result)): ?>
            <div class="result">Result: <?php echo htmlspecialchars(rtrim(rtrim(sprintf('%.15F', $result), '0'), '.')); ?></div>
        <?php endif; ?>

        <div class="graph-options">
            <label for="graphing-mode-toggle">Graphing Mode</label>
            <label class="switch">
                <input type="checkbox" id="graphing-mode-toggle">
                <span class="slider"></span>
            </label>
        </div>
    </div>

    <div id="graph-container"></div>

    <a href="index.php" class="home-link">Back to File Index</a>

    <script>
        const display = document.getElementById('display');
        const form = document.getElementById('calculator-form');
        const graphingModeToggle = document.getElementById('graphing-mode-toggle');
        const graphContainer = document.getElementById('graph-container');
        let chartInstance = null;

        function appendToDisplay(value) {
            display.value += value;
        }

        function clearDisplay() {
            display.value = '';
        }

        function drawGraph() {
            const expression = display.value.toLowerCase();
            // Clear previous results/errors and graph
            document.querySelector('.result')?.remove();
            document.querySelector('.error')?.remove();
            if (chartInstance) {
                chartInstance.destroy();
            }

            try {
                // Basic parsing: y = mx + c or y = mx
                const parts = expression.replace(/\s/g, '').split('=');
                if (parts.length !== 2 || parts[0] !== 'y') {
                    throw new Error("Invalid format. Use 'y = [equation in x]' (e.g., y = 2*x + 3).");
                }

                let equation = parts[1];
                // Add implicit multiplication for cases like '4x' -> '4*x'
                equation = equation.replace(/(\d)x/g, '$1*x');

                // Create a function from the equation string.
                // This is safer than eval() as it's scoped.
                const func = new Function('x', `return ${equation.replace(/\^/g, '**')}`);

                const labels = [];
                const dataPoints = [];
                // Use whole numbers for the x-axis
                for (let x = -10; x <= 10; x++) {
                    labels.push(x);
                    dataPoints.push(func(x));
                }

                // Create canvas for the chart
                const canvas = document.createElement('canvas');
                graphContainer.innerHTML = ''; // Clear previous canvas
                graphContainer.appendChild(canvas);

                chartInstance = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: `y = ${equation}`,
                            data: dataPoints,
                            fill: false,
                            borderColor: '#007bff',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                ticks: {
                                    // Ensure only integers are shown on the x-axis
                                    stepSize: 1
                                }
                            },
                            y: {
                                title: { display: true, text: 'Y-axis' }
                                ticks: {
                                    // Ensure only integers are shown on the y-axis
                                    callback: function(value) {
                                        if (Math.floor(value) === value) { return value; }
                                    }
                                }
                            }
                        }
                    }
                });

            } catch (e) {
                graphContainer.innerHTML = `<div class="error">Graphing Error: ${e.message}</div>`;
            }
        }
    </script>
</body>
</html>