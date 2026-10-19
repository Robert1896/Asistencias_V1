import { cp, mkdir, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';

const out = 'dist';

await rm(out, { recursive: true, force: true });
await mkdir(out, { recursive: true });

const items = ['build', 'css', 'js', 'img', 'favicon.ico', 'robots.txt'];

for (const item of items) {
    const src = `public/${item}`;

    if (existsSync(src)) {
        await cp(src, `${out}/${item}`, { recursive: true });
        console.log(`[prepare-dist] public/${item} -> ${out}/${item}`);
    }
}

console.log('[prepare-dist] assets copiados a dist/');
