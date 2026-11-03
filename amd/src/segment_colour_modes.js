/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
const system = window.matchMedia('(prefers-color-scheme: dark)');
const modeFor = source => {
    for (let node = source; node; node = node.parentElement) {
        for (const key of ['data-bs-theme', 'data-theme']) {
            const mode = node.getAttribute(key);
            if (mode === 'dark' || mode === 'light') return mode;
        }
    }
    const scheme = source ? getComputedStyle(source).colorScheme.split(/\s+/) : [];
    if (scheme.includes('dark') && !scheme.includes('light')) return 'dark';
    if (scheme.includes('dark') && scheme.includes('light')) return system.matches ? 'dark' : 'light';
    return 'light';
};

let started = false;
export const init = () => {
    if (started) { return; }
    started = true;
    const refresh = () => document.querySelectorAll('[data-frontpage-colour-mode]').forEach(segment => {
        const explicit = segment.closest('[data-fp-theme]')?.dataset.fpTheme;
        const mode = ['light', 'dark'].includes(explicit) ? explicit : modeFor(segment);
        if (segment.dataset.frontpageColourMode !== mode) { segment.dataset.frontpageColourMode = mode; }
    });
    refresh();
    new MutationObserver(refresh).observe(document.documentElement, {subtree: true, childList: true,
        attributes: true, attributeFilter: ['data-fp-theme', 'data-bs-theme', 'data-theme', 'class', 'style']});
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', refresh);
};
