import { spawn } from 'node:child_process';
import { resolve } from 'node:path';
import { php, phpEnv } from './php-runtime.mjs';

const args = process.argv.slice(2);
const root = resolve(import.meta.dirname, '..');
const cwd = args[0]?.startsWith('--cwd=') ? resolve(root, args.shift().slice(6)) : root;
const child = spawn(php, args, { cwd, env: phpEnv(), stdio: 'inherit', windowsHide: true });
child.on('error', error => { console.error(error.message); process.exitCode = 1; });
child.on('exit', code => { process.exitCode = code ?? 1; });
