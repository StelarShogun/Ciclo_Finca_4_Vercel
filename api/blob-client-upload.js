import { handleUpload } from '@vercel/blob/client';

const MAX_IMPORT_SIZE = 100 * 1024 * 1024;
const IMPORT_PREFIX = 'catalog-imports/';
const ALLOWED_CONTENT_TYPES = [
    'application/zip',
    'application/x-zip-compressed',
    'application/octet-stream',
    'application/json',
    'application/xml',
    'application/vnd.ms-excel',
    'text/csv',
    'text/plain',
    'text/xml',
];
const ALLOWED_EXTENSIONS = new Set(['zip', 'json', 'xml', 'csv', 'txt']);

function json(res, status, payload) {
    res.statusCode = status;
    res.setHeader('content-type', 'application/json; charset=utf-8');
    res.end(JSON.stringify(payload));
}

function extensionFromPath(pathname) {
    return String(pathname).split('?')[0].split('#')[0].split('.').pop()?.toLowerCase() || '';
}

function parseClientPayload(clientPayload) {
    if (!clientPayload) {
        return {};
    }

    try {
        return JSON.parse(clientPayload);
    } catch {
        return {};
    }
}

async function readBody(req) {
    if (req.body && typeof req.body === 'object') {
        return req.body;
    }

    if (typeof req.body === 'string') {
        return JSON.parse(req.body);
    }

    const chunks = [];
    for await (const chunk of req) {
        chunks.push(chunk);
    }

    const raw = Buffer.concat(chunks).toString('utf8');
    return raw ? JSON.parse(raw) : {};
}

async function isAdminRequest(req) {
    const cookie = req.headers.cookie || '';
    if (!cookie) {
        return false;
    }

    const proto = req.headers['x-forwarded-proto'] || 'https';
    const host = req.headers['x-forwarded-host'] || req.headers.host;
    if (!host) {
        return false;
    }

    const response = await fetch(`${proto}://${host}/inventory/import/active`, {
        headers: {
            accept: 'application/json',
            cookie,
            'x-requested-with': 'XMLHttpRequest',
        },
        redirect: 'manual',
    });

    return response.ok && (response.headers.get('content-type') || '').includes('application/json');
}

export default async function handler(req, res) {
    if (req.method === 'OPTIONS') {
        res.statusCode = 204;
        res.end();
        return;
    }

    if (req.method !== 'POST') {
        json(res, 405, { message: 'Method not allowed' });
        return;
    }

    try {
        const body = await readBody(req);
        const response = await handleUpload({
            request: req,
            body,
            onBeforeGenerateToken: async (pathname, clientPayload) => {
                if (!await isAdminRequest(req)) {
                    throw new Error('No autorizado.');
                }

                const payload = parseClientPayload(clientPayload);
                const extension = extensionFromPath(payload.originalName || pathname);

                if (!String(pathname).startsWith(IMPORT_PREFIX) || !ALLOWED_EXTENSIONS.has(extension)) {
                    throw new Error('Archivo de importación no permitido.');
                }

                return {
                    allowedContentTypes: ALLOWED_CONTENT_TYPES,
                    maximumSizeInBytes: MAX_IMPORT_SIZE,
                    addRandomSuffix: false,
                    allowOverwrite: true,
                    tokenPayload: JSON.stringify({
                        originalName: String(payload.originalName || pathname).slice(0, 255),
                        pathname,
                    }),
                };
            },
            onUploadCompleted: async ({ blob, tokenPayload }) => {
                console.log('Catalog import uploaded to Blob', {
                    pathname: blob.pathname,
                    tokenPayload,
                });
            },
        });

        json(res, 200, response);
    } catch (error) {
        json(res, 400, {
            message: error instanceof Error ? error.message : 'No se pudo preparar la subida.',
        });
    }
}
