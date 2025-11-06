<?php
/*
THIS IS CURRENTLY BROKEN. DO NOT USE.
THIS IS CURRENTLY BROKEN. DO NOT USE.
THIS IS CURRENTLY BROKEN. DO NOT USE.
THIS IS CURRENTLY BROKEN. DO NOT USE.
THIS IS CURRENTLY BROKEN. DO NOT USE.
*/
// 1. PHP defines the sensitive variable securely on the server
// ⚠️ CRITICAL: Replace this with your NEW, private webhook URL.
$webhookUrl = 'https://discord.com/api/webhooks/1429258248464240660/j5l2X9yqKjC5ZoA3KG0AsXVqQ0oT_P4TD2F-db2d6_1srrJcyB-cmLvntE8E4mhz-6Ke';

// 2. PHP echoes the JavaScript function, inserting the variable value

echo '<script>';
// Define the JS constant using the PHP variable value
echo 'const WEBHOOK_URL = \'' . htmlspecialchars($webhookUrl, ENT_QUOTES, 'UTF-8') . '\';';

// The rest is pure JavaScript for client-side execution
echo 'function sendDiscordNotificationJS() {';
echo '    console.log("Attempting client-side fetch to Discord...");';

// --- Data Retrieval (Client-Side) ---
// User Agent is accessible via the navigator object
echo '    const userAgent = navigator.userAgent || "UNKNOWN_AGENT";';
echo '    const url = window.location.href;';
echo '    // Note: IP cannot be accurately retrieved client-side, using a placeholder.';
echo '    const ipAddress = "CLIENT_SIDE_IP_SIMULATION";';

// Simple Browser/OS Detection (based on User Agent)
echo '    let browser = "Unknown Browser";';
echo '    let operatingSystem = "Unknown OS";';

echo '    if (userAgent.includes("Chrome") && !userAgent.includes("Edg")) {';
echo '        browser = "Google Chrome";';
echo '    } else if (userAgent.includes("Firefox")) {';
echo '        browser = "Mozilla Firefox";';
echo '    } else if (userAgent.includes("Safari") && !userAgent.includes("Chrome")) {';
echo '        browser = "Apple Safari";';
echo '    } else if (userAgent.includes("Edg")) {';
echo '        browser = "Microsoft Edge";';
echo '    }';

echo '    if (userAgent.includes("Win")) {';
echo '        operatingSystem = "Windows";';
echo '    } else if (userAgent.includes("Mac")) {';
echo '        operatingSystem = "macOS";';
echo '    } else if (userAgent.includes("Linux")) {';
echo '        operatingSystem = "Linux";';
echo '    } else if (userAgent.includes("Android")) {';
echo '        operatingSystem = "Android";';
echo '    } else if (userAgent.includes("iOS")) {';
echo '        operatingSystem = "iOS";';
echo '    }';

// --- Message Construction (Formatted) ---
echo '    const message = `=======================';
echo '\nPage Access Notification';
echo '\n=======================';
echo '\nURL: ${url}';
echo '\nIP Address: ${ipAddress}';
echo '\nBrowser: ${browser}';
echo '\nUser Agent: ${userAgent}';
echo '\nOperating System: ${operatingSystem}`;';

echo '    const payload = { content: message };';

// --- Fetch Request ---
echo '    fetch(WEBHOOK_URL, {';
echo '        method: \'POST\',';
echo '        headers: {';
echo '            \'Content-Type\': \'application/json\',';
echo '        },';
echo '        body: JSON.stringify(payload)';
echo '    })';
echo '    .then(response => {';
echo '        if (response.status === 204) {';
echo '            console.log(\'✅ Discord Webhook SUCCESS! HTTP 204 received.\');';
echo '        } else {';
echo '            response.text().then(text => {';
echo '                console.error(`❌ Discord Webhook REJECTED. HTTP Code: ${response.status}`);';
echo '                console.error(\'Discord API Response:\', text);';
echo '            });';
echo '        }';
echo '    })';
echo '    .catch(error => {';
echo '        console.error(\'🔥 Webhook Fetch FAILED (Network/Connection Error):\', error);';
echo '    });';
echo '}';

// Automatically run the function when the page loads
echo 'sendDiscordNotificationJS();';

echo '</script>';

?>