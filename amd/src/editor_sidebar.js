// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
// Adapted from the full product's nonmodal rail, using only server-authorised page actions.
export const init = config => {
    const body = document.body;
    const drawer = document.querySelector('#theme_boost-drawers-blocks');
    const region = drawer?.querySelector('[data-blockregion], .block-region');
    const main = document.querySelector('#region-main');
    if (body.classList.contains('local-frontpage-editor-sidebar-ready') || !drawer || !region || !main
        || !region.querySelector('[data-frontpage-page-tools]')) return;
    let workspace = main.querySelector('[data-frontpage-editor-workspace]');
    if (!workspace) {
        workspace = document.createElement('div');
        workspace.className = 'local-frontpage-editor-workspace';
        workspace.dataset.frontpageEditorWorkspace = '1';
        while (main.firstChild) workspace.append(main.firstChild);
        main.append(workspace);
    }
    const button = (label, glyph) => {
        const control = document.createElement('button');
        control.type = 'button';
        control.className = 'local-frontpage-icon-button local-frontpage-sidebar-control';
        control.title = label;
        control.setAttribute('aria-label', label);
        const icon = document.createElement('i');
        icon.className = 'fa ' + glyph;
        icon.setAttribute('aria-hidden', 'true');
        control.append(icon);
        return control;
    };
    const rail = document.createElement('aside');
    rail.className = 'local-frontpage-editor-rail';
    rail.setAttribute('aria-label', config.title);
    const toggle = button(config.open, 'fa-angle-left');
    toggle.classList.add('local-frontpage-sidebar-expand');
    toggle.setAttribute('aria-controls', 'local-frontpage-editor-panel');
    toggle.setAttribute('aria-expanded', 'false');
    const actions = document.createElement('nav');
    actions.className = 'local-frontpage-editor-rail-actions';
    actions.setAttribute('aria-label', config.title);
    region.querySelectorAll('[data-frontpage-page-tools] a, [data-frontpage-page-tools] button').forEach(source => {
        const label = source.getAttribute('aria-label') || source.textContent.trim();
        if (!label) return;
        const item = source.closest('form') || source;
        const copy = item.cloneNode(true);
        [copy, ...copy.querySelectorAll('[id]')].forEach(node => node.removeAttribute('id'));
        const control = copy.matches('a, button') ? copy : copy.querySelector('button');
        control.title = label;
        control.setAttribute('aria-label', label);
        control.classList.add('local-frontpage-icon-button', 'local-frontpage-sidebar-control');
        actions.append(copy);
    });
    const footer = document.createElement('div');
    footer.className = 'local-frontpage-editor-rail-footer';
    const helpSource = document.querySelector('[data-frontpage-help-open]');
    if (helpSource) {
        const help = button(helpSource.getAttribute('aria-label') || helpSource.title || config.title, 'fa-info-circle');
        help.setAttribute('data-frontpage-help-open', helpSource.getAttribute('data-frontpage-help-open'));
        help.setAttribute('aria-expanded', 'false');
        footer.append(help);
    }
    rail.append(toggle, actions, footer);
    const panel = document.createElement('section');
    panel.id = 'local-frontpage-editor-panel';
    panel.className = 'local-frontpage-editor-panel';
    panel.setAttribute('aria-label', config.title);
    panel.hidden = true;
    const header = document.createElement('div');
    header.className = 'local-frontpage-editor-panel-header';
    const title = document.createElement('h2');
    title.textContent = config.title;
    const close = button(config.close, 'fa-times');
    header.append(title, close);
    panel.append(header, region);
    body.append(rail, panel);
    const setOpen = (open, returnFocus = false) => {
        const inside = panel.contains(document.activeElement);
        panel.hidden = !open;
        rail.hidden = open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) close.focus();
        else if (returnFocus || inside) toggle.focus();
    };
    toggle.addEventListener('click', () => setOpen(true));
    close.addEventListener('click', () => setOpen(false, true));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !event.defaultPrevented && !panel.hidden
            && !document.querySelector('dialog[open], .modal.show')) setOpen(false, true);
    });
    // Help remains independently accessible and must not overlap this tools panel.
    document.addEventListener('click', event => {
        if (event.target.closest('[data-frontpage-help-open]')) setOpen(false);
    });
    const position = () => {
        const bottom = document.querySelector('nav.navbar.fixed-top')?.getBoundingClientRect().bottom || 0;
        body.style.setProperty('--local-frontpage-sidebar-top', Math.max(0, bottom) + 21 + 'px');
    };
    position();
    window.addEventListener('resize', position);
    body.classList.add('local-frontpage-editor-sidebar-ready');
};
