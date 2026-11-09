/**
 * Laravel LSP runtime for OpenCode V2.
 *
 * OpenCode V2 accepts the `lsp` config block but ships no LSP runtime, so the
 * configured server is never started. This module starts Laravel LSP over
 * stdio, exposes a `laravel_lsp` tool, and appends framework diagnostics to
 * write/edit results.
 *
 * It is diagnostics-first on purpose. Laravel LSP v0.0.32 covers routes,
 * views, config, env, middleware, Inertia, policies, and controller actions;
 * generic PHP hover/definition is not its strength.
 */

import { spawn } from 'node:child_process';
import { appendFileSync, existsSync } from 'node:fs';
import os from 'node:os';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

// Composer's global bin directory, resolved per user so the plugin works on
// any machine. Override with LARAVEL_LSP_BIN when the binary lives elsewhere.
const DEFAULT_BIN = process.env.LARAVEL_LSP_BIN
    ? process.env.LARAVEL_LSP_BIN
    : path.join(
          process.env.APPDATA ?? path.join(os.homedir(), 'AppData', 'Roaming'),
          'Composer',
          'vendor',
          'bin',
          'laravel-lsp',
      );
const START_TIMEOUT_MS = 45_000;
const REQUEST_TIMEOUT_MS = 20_000;
const DIAGNOSTICS_TIMEOUT_MS = 30_000;
const SETTLE_MS = 350;
const EMPTY_SETTLE_MS = 3000;
const MAX_REPORTED = 20;
const SEVERITY = { 1: 'ERROR', 2: 'WARN', 3: 'INFO', 4: 'HINT' };
const MUTATING_TOOLS = new Set(['write', 'edit', 'patch', 'apply_patch']);
const DEBUG = process.env.DEALFLOW_LSP_DEBUG === 'true';
const DEBUG_FILE =
    process.env.DEALFLOW_LSP_DEBUG_FILE ||
    `${process.env.TEMP || '.'}\\dealflow-lsp.log`;

function debugLog(message) {
    if (!DEBUG) {
        return;
    }

    try {
        appendFileSync(DEBUG_FILE, `${new Date().toISOString()} ${message}\n`);
    } catch {
        // Debug logging must never break the plugin.
    }
}

/**
 * Parse a VS Code settings file. Real settings files are JSONC, so strip
 * full-line comments, block comments, and trailing commas before parsing.
 * Unparseable files yield `{}` instead of failing the plugin.
 */
function parseSettings(raw) {
    const cleaned = raw
        .replace(/^\s*\/\/.*$/gm, '')
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/,(\s*[}\]])/g, '$1');

    return JSON.parse(cleaned);
}

async function readSettingsFile(file) {
    try {
        return parseSettings(await readFile(file, 'utf8'));
    } catch {
        return {};
    }
}

/**
 * Map `Laravel.<feature>.<capability>` settings to the flat camelCase
 * `initializationOptions` keys the server gates on, mirroring what the VS
 * Code extension sends (e.g. `Laravel.env.diagnostics` -> `envDiagnostics`).
 *
 * Reads VS Code's user settings first, then the project's
 * `.vscode/settings.json` (which wins), matching VS Code's own scope order.
 * Missing or invalid files contribute nothing, preserving server defaults.
 */
export async function readInitializationOptions(root) {
    const settings = {};

    if (process.env.APPDATA) {
        Object.assign(
            settings,
            await readSettingsFile(
                path.join(process.env.APPDATA, 'Code', 'User', 'settings.json'),
            ),
        );
    }

    Object.assign(
        settings,
        await readSettingsFile(path.join(root, '.vscode', 'settings.json')),
    );

    const options = {};

    for (const [key, value] of Object.entries(settings)) {
        const match = /^Laravel\.(\w+)\.(\w+)$/.exec(key);

        if (match !== null) {
            const capability =
                match[2].charAt(0).toUpperCase() + match[2].slice(1);
            options[`${match[1]}${capability}`] = value;
        }
    }

    return options;
}

export function createLaravelLsp(root) {
    const binary = process.env.LARAVEL_LSP_BIN || DEFAULT_BIN;
    const php = process.env.LARAVEL_LSP_PHP || 'php';

    return new LaravelLsp({ root, php, binary });
}

class LaravelLsp {
    constructor({ root, php, binary }) {
        this.root = root;
        this.php = php;
        this.binary = binary;
        this.child = null;
        this.buffer = Buffer.alloc(0);
        this.stderr = '';
        this.nextId = 1;
        this.pending = new Map();
        this.publishCount = new Map();
        this.latest = new Map();
        this.versions = new Map();
        this.opened = new Set();
        this.waiters = [];
        this.starting = null;
        this.stopping = false;
    }

    get running() {
        return (
            this.child !== null &&
            this.child.exitCode === null &&
            this.child.signalCode === null
        );
    }

    async start() {
        if (this.running) {
            return;
        }

        if (this.starting !== null) {
            return this.starting;
        }

        this.starting = this.spawnAndInitialize();

        try {
            await this.starting;
        } finally {
            this.starting = null;
        }
    }

    async spawnAndInitialize() {
        if (!existsSync(this.binary)) {
            throw new Error(`Laravel LSP binary not found: ${this.binary}`);
        }

        this.child = spawn(this.php, [this.binary], {
            cwd: this.root,
            stdio: ['pipe', 'pipe', 'pipe'],
            windowsHide: true,
        });

        this.child.stdout.on('data', (chunk) => this.receive(chunk));
        this.child.stderr.on('data', (chunk) => {
            this.stderr = (this.stderr + chunk.toString()).slice(-4000);
        });
        this.child.on('exit', () => {
            this.child = null;
            this.opened.clear();

            for (const { reject, timer } of this.pending.values()) {
                clearTimeout(timer);
                reject(new Error('Laravel LSP exited.'));
            }

            this.pending.clear();
        });

        const uri = pathToFileURL(this.root).href;

        await this.request(
            'initialize',
            {
                processId: process.pid,
                rootUri: uri,
                workspaceFolders: [
                    { uri, name: path.basename(this.root) || 'project' },
                ],
                initializationOptions: await readInitializationOptions(
                    this.root,
                ),
                capabilities: {
                    textDocument: {
                        publishDiagnostics: {},
                        synchronization: {},
                        hover: {},
                    },
                },
            },
            START_TIMEOUT_MS,
        );

        this.notify('initialized', {});
    }

    async stop() {
        this.stopping = true;

        if (!this.running) {
            return;
        }

        try {
            this.notify('shutdown', null);
            this.notify('exit', null);
        } catch {
            // The process is going away; a failed polite shutdown is fine.
        }

        const child = this.child;
        this.child = null;

        await new Promise((resolve) => {
            const timer = setTimeout(() => {
                child.kill();
                resolve();
            }, 1000);

            child.once('exit', () => {
                clearTimeout(timer);
                resolve();
            });
        });
    }

    notify(method, params) {
        this.send({ jsonrpc: '2.0', method, params });
    }

    request(method, params, timeoutMs = REQUEST_TIMEOUT_MS) {
        const id = this.nextId++;

        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                if (this.pending.delete(id)) {
                    reject(
                        new Error(`Laravel LSP request timed out: ${method}`),
                    );
                }
            }, timeoutMs);

            this.pending.set(id, { resolve, reject, timer });
            this.send({ jsonrpc: '2.0', id, method, params });
        });
    }

    send(message) {
        if (!this.running) {
            throw new Error('Laravel LSP is not running.');
        }

        const body = JSON.stringify(message);

        this.child.stdin.write(
            `Content-Length: ${Buffer.byteLength(body)}\r\n\r\n${body}`,
        );
    }

    receive(chunk) {
        this.buffer = Buffer.concat([this.buffer, chunk]);

        for (;;) {
            const headerEnd = this.buffer.indexOf('\r\n\r\n');

            if (headerEnd === -1) {
                return;
            }

            const header = this.buffer.subarray(0, headerEnd).toString('ascii');
            const length = Number(/Content-Length:\s*(\d+)/i.exec(header)?.[1]);

            if (!Number.isFinite(length) || length <= 0) {
                this.buffer = this.buffer.subarray(headerEnd + 4);

                continue;
            }

            const total = headerEnd + 4 + length;

            if (this.buffer.length < total) {
                return;
            }

            const body = this.buffer
                .subarray(headerEnd + 4, total)
                .toString('utf8');
            this.buffer = this.buffer.subarray(total);

            let message;

            try {
                message = JSON.parse(body);
            } catch {
                continue;
            }

            this.handle(message);
        }
    }

    handle(message) {
        if (message.id !== undefined && message.method === undefined) {
            const pending = this.pending.get(message.id);

            if (pending) {
                clearTimeout(pending.timer);
                this.pending.delete(message.id);
                pending.resolve(message);
            }

            return;
        }

        if (
            message.method === 'textDocument/publishDiagnostics' &&
            message.params?.uri
        ) {
            this.publish(message.params);
        }

        if (message.id !== undefined && message.method !== undefined) {
            // The server is asking the client for something. Answering null
            // keeps it from waiting forever on features this plugin does not use.
            this.send({ jsonrpc: '2.0', id: message.id, result: null });
        }
    }

    publish(params) {
        debugLog(
            `publish ${params.uri.split('/').pop()} diagnostics=${
                (params.diagnostics ?? []).length
            } at=${Date.now()}`,
        );

        const count = (this.publishCount.get(params.uri) ?? 0) + 1;
        this.publishCount.set(params.uri, count);
        this.latest.set(params.uri, params.diagnostics ?? []);

        const ready = this.waiters.filter(
            (waiter) => waiter.uri === params.uri && count > waiter.seen,
        );

        for (const waiter of ready) {
            clearTimeout(waiter.deadline);

            if (waiter.settle !== null) {
                clearTimeout(waiter.settle);
            }

            const delay =
                (this.latest.get(params.uri) ?? []).length === 0
                    ? EMPTY_SETTLE_MS
                    : SETTLE_MS;

            waiter.settle = setTimeout(() => {
                this.waiters = this.waiters.filter((item) => item !== waiter);
                waiter.resolve(this.latest.get(params.uri) ?? []);
            }, delay);
        }
    }

    async open(file) {
        const text = await readFile(file, 'utf8');
        const uri = pathToFileURL(file).href;
        const version = (this.versions.get(uri) ?? 0) + 1;
        this.versions.set(uri, version);

        if (this.opened.has(uri)) {
            this.notify('textDocument/didChange', {
                textDocument: { uri, version },
                contentChanges: [{ text }],
            });
        } else {
            this.notify('textDocument/didOpen', {
                textDocument: {
                    uri,
                    languageId: file.endsWith('.blade.php') ? 'blade' : 'php',
                    version,
                    text,
                },
            });
            this.opened.add(uri);
        }

        return uri;
    }

    async diagnostics(file, timeoutMs = DIAGNOSTICS_TIMEOUT_MS) {
        await this.start();

        const uri = await this.open(file);
        const seen = this.publishCount.get(uri) ?? 0;

        return new Promise((resolve) => {
            const waiter = { uri, seen, resolve, settle: null, deadline: null };

            waiter.deadline = setTimeout(() => {
                this.waiters = this.waiters.filter((item) => item !== waiter);

                if (waiter.settle !== null) {
                    clearTimeout(waiter.settle);
                }

                resolve(this.latest.get(uri) ?? []);
            }, timeoutMs);

            this.waiters.push(waiter);
        });
    }

    async warm(file) {
        try {
            await this.start();
            await this.open(file);
        } catch (error) {
            debugLog(`warm failed: ${error?.message ?? error}`);
        }
    }

    async hover(file, line, character) {
        await this.start();

        const uri = await this.open(file);
        const response = await this.request('textDocument/hover', {
            textDocument: { uri },
            position: { line: line - 1, character: character - 1 },
        });

        return response.result ?? null;
    }
}

export function formatDiagnostics(file, diagnostics, root) {
    const relevant = diagnostics.filter(
        (diagnostic) => (diagnostic.severity ?? 1) <= 2,
    );

    if (relevant.length === 0) {
        return '';
    }

    const shown = relevant.slice(0, MAX_REPORTED).map((diagnostic) => {
        const severity = SEVERITY[diagnostic.severity ?? 1] ?? 'ERROR';
        const line = (diagnostic.range?.start?.line ?? 0) + 1;
        const character = (diagnostic.range?.start?.character ?? 0) + 1;
        const code = diagnostic.code ? ` (${diagnostic.code})` : '';

        return `${severity} [${line}:${character}] ${diagnostic.message}${code}`;
    });

    const more = relevant.length - shown.length;

    if (more > 0) {
        shown.push(`... and ${more} more`);
    }

    return `<diagnostics file="${displayPath(file, root)}">\n${shown.join('\n')}\n</diagnostics>`;
}

export function displayPath(file, root) {
    const relative = path.relative(root, file);

    return relative && !relative.startsWith('..') ? relative : file;
}

function insideRoot(file, root) {
    return file.toLowerCase().startsWith(root.toLowerCase());
}

export function touchedFiles(event, root) {
    const changed = new Set();
    const add = (value) => {
        if (typeof value !== 'string' || value === '') {
            return;
        }

        const resolved = path.isAbsolute(value)
            ? value
            : path.resolve(root, value.replace(/^@/, ''));

        changed.add(resolved);
    };

    const input = event.input;

    if (input && typeof input === 'object') {
        add(input.path);

        if (typeof input.patchText === 'string') {
            for (const match of input.patchText.matchAll(
                /^\*\*\* (?:Update|Add) File: (.+)$/gm,
            )) {
                add(match[1].trim());
            }
        }
    }

    const output = event.result?.output;

    if (output && typeof output === 'object') {
        add(output.target);

        if (Array.isArray(output.applied)) {
            for (const item of output.applied) {
                if (item && typeof item === 'object') {
                    add(item.target);
                }
            }
        }

        if (Array.isArray(output.files)) {
            for (const item of output.files) {
                if (
                    item &&
                    typeof item === 'object' &&
                    item.status !== 'deleted'
                ) {
                    add(item.file);
                }
            }
        }
    }

    return [...changed].filter(
        (file) => /\.(php|blade\.php)$/i.test(file) && insideRoot(file, root),
    );
}

function append(result, text) {
    if (!result || typeof result !== 'object') {
        return;
    }

    if (typeof result.content === 'string') {
        result.content = `${result.content}\n\n${text}`;
    } else if (Array.isArray(result.content)) {
        result.content = [...result.content, { type: 'text', text }];
    } else if (typeof result.output === 'string') {
        result.output = `${result.output}\n\n${text}`;
    } else {
        result.content = text;
    }
}

function hoverText(result) {
    const contents = result?.contents;

    if (typeof contents === 'string') {
        return contents;
    }

    if (Array.isArray(contents)) {
        return contents
            .map((item) =>
                typeof item === 'string' ? item : (item?.value ?? ''),
            )
            .filter(Boolean)
            .join('\n\n');
    }

    return typeof contents?.value === 'string' ? contents.value : '';
}

export async function registerLaravelLsp(ctx) {
    const root = ctx.location.directory;
    const isLaravelProject = existsSync(path.join(root, 'artisan'));

    debugLog(`register location=${root} artisan=${isLaravelProject}`);

    if (!isLaravelProject) {
        return async () => {};
    }

    const lsp = createLaravelLsp(root);

    if (process.env.LARAVEL_LSP_WARM !== '0') {
        setTimeout(() => {
            void lsp
                .warm(path.join(root, 'routes', 'web.php'))
                .catch((error) =>
                    debugLog(`startup warm failed: ${error?.message ?? error}`),
                );
        }, 1500);
    }

    const tool = await ctx.tool.transform((editor) => {
        editor.add({
            name: 'laravel_lsp',
            description:
                'Laravel LSP: framework-aware diagnostics and hover for routes, views, config, env, middleware, Inertia, policies, and controller actions. Use `diagnostics` after changing Laravel code, and `hover` on a route name, config key, or view name to see what Laravel resolves it to. Line and character are 1-based.',
            input: {
                type: 'object',
                properties: {
                    operation: {
                        type: 'string',
                        enum: ['diagnostics', 'hover'],
                    },
                    path: {
                        type: 'string',
                        description: 'Project-relative or absolute file path.',
                    },
                    line: {
                        type: 'integer',
                        minimum: 1,
                        description: '1-based line for hover.',
                    },
                    character: {
                        type: 'integer',
                        minimum: 1,
                        description: '1-based character for hover.',
                    },
                },
                required: ['operation', 'path'],
                additionalProperties: false,
            },
            options: { codemode: false },
            async execute(input) {
                const file = path.isAbsolute(input.path)
                    ? input.path
                    : path.resolve(root, input.path);

                if (!insideRoot(file, root)) {
                    return { content: 'Refusing to read outside the project.' };
                }

                if (input.operation === 'hover') {
                    if (!input.line || !input.character) {
                        return { content: 'hover needs line and character.' };
                    }

                    const result = await lsp.hover(
                        file,
                        input.line,
                        input.character,
                    );
                    const text = hoverText(result);

                    return {
                        content: text || 'No hover result at that position.',
                        metadata: {
                            operation: 'hover',
                            path: displayPath(file, root),
                        },
                    };
                }

                const diagnostics = await lsp.diagnostics(file);
                const report = formatDiagnostics(file, diagnostics, root);

                return {
                    content:
                        report ||
                        `No Laravel LSP diagnostics for ${displayPath(file, root)}.`,
                    metadata: {
                        operation: 'diagnostics',
                        path: displayPath(file, root),
                        count: diagnostics.length,
                    },
                };
            },
        });
    });

    const hook = await ctx.tool.hook('execute.after', async (event) => {
        debugLog(`hook tool=${event.tool} status=${event.status}`);

        if (event.status !== 'completed') {
            return;
        }

        if (event.tool === 'read') {
            const input = event.input;

            if (
                typeof input?.path === 'string' &&
                /\.(php|blade\.php)$/i.test(input.path)
            ) {
                const file = path.isAbsolute(input.path)
                    ? input.path
                    : path.resolve(root, input.path);

                if (insideRoot(file, root)) {
                    void lsp.warm(file);
                }
            }

            return;
        }

        if (!MUTATING_TOOLS.has(event.tool)) {
            return;
        }

        const files = touchedFiles(event, root);
        debugLog(
            `hook files=${files.join(',') || 'none'} input=${JSON.stringify(
                event.input ?? {},
            ).slice(
                0,
                500,
            )} result=${Object.keys(event.result ?? {}).join(',')}`,
        );

        for (const file of files) {
            try {
                const diagnostics = await lsp.diagnostics(file);
                const report = formatDiagnostics(file, diagnostics, root);
                debugLog(
                    `diagnostics ${path.basename(file)} count=${
                        diagnostics.length
                    } report=${report !== ''}`,
                );

                if (report !== '') {
                    append(event.result, `Laravel LSP:\n${report}`);
                }
            } catch (error) {
                debugLog(
                    `diagnostics failed ${path.basename(file)}: ${
                        error?.message ?? error
                    }`,
                );
            }
        }
    });

    debugLog(
        `registered tool=${typeof tool} hook=${typeof hook} toolKeys=${Object.keys(
            tool ?? {},
        ).join(',')} hookKeys=${Object.keys(hook ?? {}).join(',')}`,
    );

    return async () => {
        if (typeof hook?.dispose === 'function') {
            await hook.dispose();
        }

        if (typeof tool?.dispose === 'function') {
            await tool.dispose();
        }

        await lsp.stop();
    };
}
