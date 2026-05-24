#!/usr/bin/env node
/**
 * Builds a Font Awesome subset that contains only the `fa-*` glyphs referenced
 * in the codebase (blade/JS/CSS). Output: public/fonts/fa-subset/*.woff2.
 *
 * Requirements (host): a Python venv with `fonttools` + `brotli` installed at
 * the path stored in $CF4_FA_VENV (defaults to /tmp/cf4-fonttools). Example:
 *
 *   python3 -m venv /tmp/cf4-fonttools
 *   /tmp/cf4-fonttools/bin/pip install fonttools brotli
 *
 * Usage:  node scripts/fonts/build-fa-subset.mjs
 *
 * Re-run this whenever new `fa-*` classes appear in templates/JS, then
 * `npm run build` so Vite picks up the updated CSS.
 */
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..', '..');

const FA_PKG = path.join(ROOT, 'node_modules', '@fortawesome', 'fontawesome-free');
const FA_CSS = path.join(FA_PKG, 'css', 'fontawesome.css');
const FA_SOLID_WOFF2 = path.join(FA_PKG, 'webfonts', 'fa-solid-900.woff2');
const FA_REGULAR_WOFF2 = path.join(FA_PKG, 'webfonts', 'fa-regular-400.woff2');
const OUT_DIR = path.join(ROOT, 'public', 'fonts', 'fa-subset');
const VENV = process.env.CF4_FA_VENV || '/tmp/cf4-fonttools';
const PYFTSUBSET = path.join(VENV, 'bin', 'pyftsubset');

if (!fs.existsSync(FA_CSS)) {
    console.error(`[fa-subset] Cannot find ${FA_CSS}. Run \`npm install\` first.`);
    process.exit(1);
}
if (!fs.existsSync(PYFTSUBSET)) {
    console.error(`[fa-subset] Missing pyftsubset at ${PYFTSUBSET}. Create a venv with:`);
    console.error('    python3 -m venv ' + VENV);
    console.error('    ' + VENV + '/bin/pip install fonttools brotli');
    process.exit(1);
}

const css = fs.readFileSync(FA_CSS, 'utf8');
const nameToCode = new Map();
const rx = /\.fa-([a-z0-9-]+)\s*\{\s*--fa:\s*"\\([0-9a-f]+)"/g;
let m;
while ((m = rx.exec(css))) nameToCode.set(m[1], m[2]);
if (nameToCode.size === 0) {
    console.error('[fa-subset] Failed to parse fontawesome.css glyph map.');
    process.exit(1);
}

function walk(dir, out = []) {
    if (!fs.existsSync(dir)) return out;
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        if (e.name.startsWith('.')) continue;
        if (e.name === 'node_modules' || e.name === 'vendor' || e.name === 'storage') continue;
        const p = path.join(dir, e.name);
        if (e.isDirectory()) walk(p, out);
        else if (/\.(blade\.php|js|ts|tsx|jsx|vue|css|html|php)$/i.test(e.name)) out.push(p);
    }
    return out;
}

// Limit scanning to source (not Vite build output, which embeds the whole FA class map).
const scanDirs = ['resources', 'app', 'config', 'routes'];
const files = scanDirs.flatMap((d) => walk(path.join(ROOT, d)));
const used = new Set();
const usedNames = new Set();
const iconRx = /\bfa-([a-z0-9-]+)\b/g;
for (const f of files) {
    const txt = fs.readFileSync(f, 'utf8');
    let mm;
    while ((mm = iconRx.exec(txt))) {
        const name = mm[1];
        if (nameToCode.has(name)) {
            used.add(nameToCode.get(name));
            usedNames.add(name);
        }
    }
}

/** Always include these glyphs even if static scan misses a reference. */
const REQUIRED_ICONS = [
    // Admin sidebar
    'chart-line', 'cash-register', 'shopping-cart', 'clipboard-list', 'box',
    'layer-group', 'th-list', 'tags', 'truck', 'users', 'file-alt', 'globe',
    'sign-out-alt',
    // Inventory actions
    'eye', 'edit', 'plus-circle', 'minus-circle', 'trash', 'star', 'filter',
    'search', 'upload', 'file-import', 'box-open', 'plus', 'th',
    // Client header / catalog
    'heart', 'bell', 'cart-plus', 'cart-shopping', 'magnifying-glass',
    'user-circle', 'bars', 'chevron-down', 'bicycle', 'arrow-right',
    // Product detail / placeholders
    'tint', 'wine-bottle', 'layer-group', 'bolt', 'money-bill-wave', 'comment-alt',
];

const missingRequired = [];
for (const name of REQUIRED_ICONS) {
    const code = nameToCode.get(name);
    if (code) {
        used.add(code);
        usedNames.add(name);
    } else {
        missingRequired.push(name);
    }
}
if (missingRequired.length) {
    console.warn('[fa-subset] Required icons not found in fontawesome.css:', missingRequired.join(', '));
}

const codes = [...used].sort();
const extras = ['0020', '00A0', '200B', '200C', '200D', 'FEFF'];
const unicodes = [...codes, ...extras].map((c) => 'U+' + c).join(',');

fs.mkdirSync(OUT_DIR, { recursive: true });

function subset(src, dst, label) {
    const res = spawnSync(
        PYFTSUBSET,
        [src, `--unicodes=${unicodes}`, '--flavor=woff2', `--output-file=${dst}`],
        { stdio: 'inherit' },
    );
    if (res.status !== 0) {
        console.error(`[fa-subset] ${label} failed (exit ${res.status}).`);
        process.exit(res.status ?? 1);
    }
    const size = fs.statSync(dst).size;
    console.log(`[fa-subset] ${label}: ${(size / 1024).toFixed(1)} KiB -> ${path.relative(ROOT, dst)}`);
}

console.log(`[fa-subset] Indexed ${nameToCode.size} FA glyphs.`);
console.log(`[fa-subset] Codebase uses ${usedNames.size} unique icons.`);

subset(FA_SOLID_WOFF2, path.join(OUT_DIR, 'fa-solid-900.subset.woff2'), 'fa-solid-900');
subset(FA_REGULAR_WOFF2, path.join(OUT_DIR, 'fa-regular-400.subset.woff2'), 'fa-regular-400');

const manifest = {
    generatedAt: new Date().toISOString(),
    icons: [...usedNames].sort(),
};
fs.writeFileSync(path.join(OUT_DIR, 'manifest.json'), JSON.stringify(manifest, null, 2));
console.log('[fa-subset] Wrote manifest.json.');
