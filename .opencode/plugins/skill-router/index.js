/**
 * DealFlow skill router and Laravel LSP runtime.
 *
 * Enforces the minimum routing contract in AGENTS.md:
 * - UI edits require the Impeccable skill in the session.
 * - Marketing-surface edits require a Corey Haines skill in the session.
 * - Laravel edits require at least one Laravel Boost call in the session.
 * - Test edits require a testing skill in the session.
 * - git commit, git push, and gh pr create require verification first.
 *
 * It records skill loads and Boost calls from permission events, then denies
 * edits and publishing commands that would skip the required workflow. A
 * denial message is a routing instruction: the model can satisfy it and retry.
 *
 * Set OPENCODE_SKILL_ROUTER=off for a deliberate, stated exception.
 */

import { registerLaravelLsp } from './lsp.js';

const SESSION_TTL_MS = 12 * 60 * 60 * 1000;
const MAX_SESSIONS = 200;

const sessions = new Map();

const COREY_HAINES_SKILLS = [
    'copywriting',
    'copy-editing',
    'cro',
    'pricing',
    'onboarding',
    'signup',
];

const GATES = [
    {
        id: 'impeccable',
        matches: (path) =>
            /(^|\/)resources\/(js\/(pages|components|layouts)|css|views)\//.test(
                path,
            ),
        skills: ['impeccable'],
        message:
            'Load the `impeccable` skill (skill tool, id: impeccable), run its context/critique pass on this surface, then retry.',
    },
    {
        id: 'corey-haines',
        matches: (path) =>
            /(^|\/)resources\/js\/pages\/(welcome|marketplace|pricing|checkout|onboarding|auth)(\/|\.|$)/.test(
                path,
            ) || /(^|\/)resources\/js\/components\/marketing\//.test(path),
        skills: COREY_HAINES_SKILLS,
        message:
            'Load one Corey Haines skill (skill tool id: `copywriting` for new copy, `copy-editing` for existing copy, `cro` for conversion, or the matching `pricing` / `onboarding` / `signup` skill), then retry.',
    },
    {
        id: 'testing',
        matches: (path) => /(^|\/)tests\/.*\.php$/.test(path),
        skills: ['testing-best-practices', 'test-driven-development'],
        message:
            'Load `testing-best-practices` before editing tests, and `test-driven-development` when the change implements new behavior. Then retry.',
    },
    {
        id: 'laravel-schema',
        matches: (path) =>
            /(^|\/)(database\/migrations|app\/Models)\//.test(path),
        boostTool: 'database_schema',
        message:
            'Call `database-schema` (Laravel Boost) for the affected tables before editing a migration or model, name the tables, then retry.',
    },
    {
        id: 'laravel-boost',
        matches: (path) =>
            /(^|\/)(app|database|routes|config|bootstrap)\//.test(path) ||
            /(^|\/)tests\/.*\.php$/.test(path),
        boostAny: true,
        message:
            'Make at least one Laravel Boost call in this session first: `database-schema` for tables or models, `search-docs` for framework APIs, or `database-query` for data checks. Name the result, then retry.',
    },
];

/**
 * Metadata-only probe used by the local verification command.
 */
export function routeViolation(event) {
    if (process.env.OPENCODE_SKILL_ROUTER === 'off') {
        return null;
    }

    if (!event) {
        return null;
    }

    if (event.action === 'shell') {
        return shellViolation(event);
    }

    if (event.action !== 'edit') {
        return null;
    }

    const state = stateFor(event.sessionID);
    const paths = (event.resources ?? []).map(normalizePath);
    const missing = [];

    for (const path of paths) {
        for (const gate of GATES) {
            if (!gate.matches(path)) {
                continue;
            }

            if (gate.boostAny && state.boost.size === 0) {
                missing.push(gate.message);

                continue;
            }

            if (gate.boostTool && !state.boost.has(gate.boostTool)) {
                missing.push(gate.message);

                continue;
            }

            if (gate.boostAny || gate.boostTool) {
                continue;
            }

            if (!gate.skills.some((skill) => state.skills.has(skill))) {
                missing.push(gate.message);
            }
        }
    }

    if (missing.length === 0) {
        return null;
    }

    const unique = [...new Set(missing)];
    const target = paths.find((path) =>
        GATES.some((gate) => gate.matches(path)),
    );

    return [
        `DealFlow routing gate: \`${target ?? 'this file'}\` needs the routed workflow before it can be edited.`,
        ...unique.map((message) => `- ${message}`),
        'See AGENTS.md → Mandatory Skill Routing. This gate is intentional; satisfy it and retry.',
    ].join('\n');
}

function shellViolation(event) {
    const state = stateFor(event.sessionID);
    const commands = (event.resources ?? []).map(String);
    const publishing = commands.find((command) =>
        /(^|[\s;&|])(?:\S*\/)?git(?:\.exe)?\s+(commit|push)\b|(^|[\s;&|])(?:\S*\/)?gh(?:\.exe)?\s+pr\s+create\b/.test(
            command,
        ),
    );

    if (publishing === undefined) {
        return null;
    }

    if (state.skills.has('verification-before-completion')) {
        return null;
    }

    return [
        `DealFlow routing gate: \`${publishing}\` publishes work and needs verification first.`,
        '- Load the `verification-before-completion` skill (skill tool, id: verification-before-completion), run the test, lint, type, and build checks, then retry.',
        'See AGENTS.md → Mandatory Skill Routing. This gate is intentional; satisfy it and retry.',
    ].join('\n');
}

/**
 * Record a permission event: a skill load or a Laravel Boost call.
 */
export function recordPermission(event) {
    if (!event?.sessionID) {
        return;
    }

    const state = stateFor(event.sessionID);

    if (event.action === 'skill') {
        for (const resource of event.resources ?? []) {
            state.skills.add(String(resource));
        }
    }

    const tool = boostTool(event.action);

    if (tool !== null) {
        state.boost.add(tool);
    }
}

function boostTool(action) {
    if (typeof action !== 'string') {
        return null;
    }

    const match = action.match(/laravel[-_]boost[_-]?(.*)$/i);

    if (match === null) {
        return null;
    }

    return (match[1] || 'call').replaceAll('-', '_');
}

export function resetRouterState() {
    sessions.clear();
}

function stateFor(sessionID) {
    prune();

    const key = String(sessionID ?? 'unknown');
    let state = sessions.get(key);

    if (!state) {
        state = { skills: new Set(), boost: new Set(), seen: Date.now() };
        sessions.set(key, state);
    }

    state.seen = Date.now();

    return state;
}

function prune() {
    if (sessions.size < MAX_SESSIONS) {
        return;
    }

    const cutoff = Date.now() - SESSION_TTL_MS;

    for (const [key, state] of sessions) {
        if (state.seen < cutoff) {
            sessions.delete(key);
        }
    }
}

function normalizePath(resource) {
    return String(resource).replaceAll('\\', '/').replace(/^\.\//, '');
}

export default {
    id: 'dealflow.skill-router',
    async setup(ctx) {
        const permission = await ctx.permission.hook('evaluate', (event) => {
            try {
                recordPermission(event);

                const message = routeViolation(event);

                if (message !== null) {
                    event.effect = 'deny';
                    event.message = message;
                }
            } catch (error) {
                // Never block work because the router itself failed.
                console.error(
                    `[dealflow.skill-router] gate error: ${
                        error?.message ?? error
                    }`,
                );
            }
        });

        let disposeLsp = async () => {};

        try {
            disposeLsp = await registerLaravelLsp(ctx);
        } catch (error) {
            // LSP is an enhancement; routing must keep working without it.
            console.error(
                `[dealflow.skill-router] lsp setup failed: ${
                    error?.message ?? error
                }`,
            );
        }

        return async () => {
            if (typeof permission?.dispose === 'function') {
                await permission.dispose();
            }

            await disposeLsp();
        };
    },
};
