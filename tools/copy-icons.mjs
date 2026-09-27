import { cp, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const root = new URL('../', import.meta.url);
const destination = new URL('public/assets/icons/', root);
await mkdir(destination, { recursive: true });
await cp(new URL('node_modules/bootstrap-icons/font/bootstrap-icons.css', root), new URL('bootstrap-icons.css', destination));
await cp(new URL('node_modules/bootstrap-icons/font/fonts/', root), new URL('fonts/', destination), { recursive: true });
await cp(new URL('node_modules/bootstrap-icons/LICENSE', root), new URL('LICENSE', destination));
console.log(`Bootstrap Icons copiados a ${fileURLToPath(destination)}`);

