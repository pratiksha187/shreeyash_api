const http = require('http');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');

const env = loadEnv();
const host = env.WHATSAPP_WEB_HOST || '127.0.0.1';
const port = Number(env.WHATSAPP_WEB_PORT || 3010);
const token = env.WHATSAPP_WEB_TOKEN || '';

let ready = false;

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'attendance-admin',
        dataPath: '.whatsapp-web-session',
    }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
});

client.on('qr', (qr) => {
    ready = false;
    console.log('\nScan this QR with WhatsApp: Menu > Linked devices > Link a device\n');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    ready = true;
    console.log('WhatsApp Web is ready.');
});

client.on('authenticated', () => {
    console.log('WhatsApp Web authenticated.');
});

client.on('auth_failure', (message) => {
    ready = false;
    console.error('WhatsApp Web authentication failed:', message);
});

client.on('disconnected', (reason) => {
    ready = false;
    console.error('WhatsApp Web disconnected:', reason);
});

client.initialize();

const server = http.createServer(async (request, response) => {
    if (request.method === 'GET' && request.url === '/status') {
        return json(response, 200, { ready });
    }

    if (request.method !== 'POST' || request.url !== '/send-message') {
        return json(response, 404, { message: 'Not found.' });
    }

    if (token && request.headers.authorization !== `Bearer ${token}`) {
        return json(response, 401, { message: 'Unauthorized.' });
    }

    if (!ready) {
        return json(response, 503, { message: 'WhatsApp Web is not ready. Scan QR first.' });
    }

    try {
        const body = await readJson(request);
        const mobile = normalizeMobile(body.mobile || '');
        const message = String(body.message || '').trim();

        if (!mobile || !message) {
            return json(response, 422, { message: 'mobile and message are required.' });
        }

        const chatId = `${mobile}@c.us`;
        const sentMessage = await client.sendMessage(chatId, message);

        return json(response, 200, {
            sent: true,
            message_id: sentMessage.id?._serialized || null,
        });
    } catch (error) {
        console.error('WhatsApp send failed:', error);

        return json(response, 500, {
            sent: false,
            message: error.message,
        });
    }
});

server.listen(port, host, () => {
    console.log(`WhatsApp Web server running at http://${host}:${port}`);
});

function readJson(request) {
    return new Promise((resolve, reject) => {
        let raw = '';

        request.on('data', (chunk) => {
            raw += chunk;

            if (raw.length > 100000) {
                request.destroy();
                reject(new Error('Request body too large.'));
            }
        });

        request.on('end', () => {
            try {
                resolve(raw ? JSON.parse(raw) : {});
            } catch (error) {
                reject(new Error('Invalid JSON body.'));
            }
        });
    });
}

function json(response, status, data) {
    response.writeHead(status, { 'Content-Type': 'application/json' });
    response.end(JSON.stringify(data));
}

function normalizeMobile(mobile) {
    const digits = String(mobile).replace(/\D+/g, '');

    if (digits.length === 10) {
        return `91${digits}`;
    }

    return digits;
}

function loadEnv() {
    const fs = require('fs');
    const path = require('path');
    const envPath = path.join(__dirname, '.env');

    if (!fs.existsSync(envPath)) {
        return process.env;
    }

    const values = { ...process.env };
    const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);

    for (const line of lines) {
        const trimmed = line.trim();

        if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) {
            continue;
        }

        const index = trimmed.indexOf('=');
        const key = trimmed.slice(0, index).trim();
        let value = trimmed.slice(index + 1).trim();

        if (
            (value.startsWith('"') && value.endsWith('"')) ||
            (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1);
        }

        values[key] = value;
    }

    return values;
}
