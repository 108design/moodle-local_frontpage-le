// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
export const init = () => {
    const root = document.querySelector('[data-frontpage-segment-picker]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';
    const search = root.querySelector('input[type="search"]');
    const preview = root.querySelector('[data-picker-preview]');
    const heading = root.querySelector('[data-picker-heading]');
    const purpose = root.querySelector('[data-picker-purpose]');
    const empty = root.querySelector('[data-picker-empty]');
    const entries = [...root.querySelectorAll('[data-picker-entry]')];
    const show = entry => {
        entries.forEach(node => node.querySelector('button').setAttribute('aria-pressed', String(node === entry)));
        if (!entry) { preview.replaceChildren(); heading.textContent = ''; purpose.textContent = ''; return; }
        heading.textContent = entry.dataset.label;
        purpose.textContent = entry.dataset.purpose;
        preview.replaceChildren(entry.querySelector('template').content.cloneNode(true));
    };
    root.querySelectorAll('[data-picker-entry] button').forEach(control => {
        control.addEventListener('click', () => show(control.closest('[data-picker-entry]')));
        control.addEventListener('keydown', event => {
            if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
            const visible = entries.filter(entry => !entry.hidden);
            const current = visible.indexOf(control.closest('[data-picker-entry]'));
            const next = visible[(current + (event.key === 'ArrowDown' ? 1 : -1) + visible.length) % visible.length];
            if (next) { event.preventDefault(); next.querySelector('button').focus(); show(next); }
        });
    });
    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase();
        entries.forEach(entry => { entry.hidden = !(entry.dataset.label + ' ' + entry.dataset.purpose).toLocaleLowerCase().includes(query); });
        const first = entries.find(entry => !entry.hidden);
        empty.hidden = !!first;
        show(first);
    });
    show(entries[0]);
};
