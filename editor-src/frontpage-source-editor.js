// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license 108design source-available software license; see LICENSE.md
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
import {basicSetup, EditorView} from 'codemirror';
import {EditorState} from '@codemirror/state';
import {css} from '@codemirror/lang-css';
define('local_frontpage/source_editor', [], () => ({init: config => {
    const textarea=document.getElementById(config.textareaId);
    if(!textarea||textarea.dataset.frontpageSourceEditor==='true'){return;}
    const container=document.createElement('div');container.className='local-frontpage-code-editor';
    textarea.insertAdjacentElement('afterend',container);
    const label=document.querySelector('label[for="'+CSS.escape(config.textareaId)+'"]');
    const state=EditorState.create({doc:textarea.value,extensions:[basicSetup,css(),EditorState.tabSize.of(2),
        EditorView.lineWrapping,EditorView.contentAttributes.of({'aria-label':label?.textContent?.trim()||'Page CSS','aria-multiline':'true'}),
        EditorView.updateListener.of(update=>{if(update.docChanged){textarea.value=update.state.doc.toString();
            textarea.dispatchEvent(new Event('input',{bubbles:true}));}})]});
    const view=new EditorView({state,parent:container});textarea.hidden=true;textarea.dataset.frontpageSourceEditor='true';
    textarea.closest('form')?.addEventListener('reset',()=>requestAnimationFrame(()=>view.dispatch({
        changes:{from:0,to:view.state.doc.length,insert:textarea.value}})));
}}));
