/**
 * Copies the Lucide icon set into templates/icons/lucide so Twig can inline
 * any icon by name with `source('icons/lucide/<name>.svg')` — no icon
 * JavaScript on the front end, and no dependency on node_modules at runtime.
 *
 * Each file is minified on the way in: the licence comment is dropped (it is
 * kept once in LICENSE) and whitespace collapsed, so inlining an icon costs a
 * few hundred bytes rather than a kilobyte.
 *
 * Runs automatically after `npm install`; the output folder is committed.
 */
import { mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const from = resolve(root, 'node_modules/lucide-static/icons');
const to = resolve(root, 'templates/icons/lucide');

rmSync(to, { force: true, recursive: true });
mkdirSync(to, { recursive: true });

let count = 0;

for (const file of readdirSync(from)) {
    if (!file.endsWith('.svg')) {
        continue;
    }

    const svg = readFileSync(resolve(from, file), 'utf8')
        .replace(/<!--[\s\S]*?-->/g, '')
        .replace(/\s+/g, ' ')
        .replace(/\s*\/>/g, '/>')
        .replace(/>\s+</g, '><')
        .trim();

    writeFileSync(resolve(to, file), svg);
    count++;
}

writeFileSync(
    resolve(to, 'LICENSE'),
    readFileSync(resolve(root, 'node_modules/lucide-static/LICENSE'), 'utf8'),
);

console.log(`Copied ${count} Lucide icons to templates/icons/lucide`);
