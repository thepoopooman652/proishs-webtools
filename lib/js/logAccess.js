async function sendPageAccessNotification(webhookUrl) {
    const url = window.location.href;

    const userAgent = navigator.userAgent;

    const browser = getBrowser(userAgent);
    const os = getOS(userAgent);

    let userIp = 'Unavailable';
    try {
        const ipResponse = await fetch('https://api.ipify.org?format=json');
        const ipData = await ipResponse.json();
        userIp = ipData.ip; 
    } catch (error) {
        console.error('Could not fetch IP address:', error);
    }
    
    const messageContent = `
**Page Access Notification:**
URL: ${url}
IP Address: ${userIp}
Browser: ${browser}
User Agent: ${userAgent}
Operating System: ${os}
    `.trim();

    const payload = {
        content: messageContent,
    };

    try {
        const response = await fetch(webhookUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)

        if (response.ok) {
            console.log('Successfully sent notification to Discord!');
        } else {
            console.error('Failed to send notification to Discord. Status:', response.status);
        }
    } catch (error) {
        console.error('Network error while sending to Discord:', error);
    }
}

// --- Helper Functions ---

function getBrowser(userAgent) {
    if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) return 'Chrome';
    if (userAgent.includes('Firefox')) return 'Firefox';
    if (userAgent.includes('Edg')) return 'Edge';
    if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
    if (userAgent.includes('MSIE') || userAgent.includes('Trident')) return 'Internet Explorer';
    return 'Unknown/Other';
}

function getOS(userAgent) {
    if (userAgent.includes('Win')) return 'Windows';
    if (userAgent.includes('Mac')) return 'macOS/iOS';
    if (userAgent.includes('Linux')) return 'Linux';
    if (userAgent.includes('Android')) return 'Android';
    return 'Unknown/Other';
}

const DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/1429258248464240660/j5l2X9yqKjC5ZoA3KG0AsXVqQ0oT_P4TD2F-db2d6_1srrJcyB-cmLvntE8E4mhz-6Ke';

sendPageAccessNotification(DISCORD_WEBHOOK_URL);