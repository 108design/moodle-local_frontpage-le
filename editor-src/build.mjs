// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license 108design source-available software license; see LICENSE.md
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {build} from 'esbuild';
const root=path.dirname(fileURLToPath(import.meta.url));
const result=await build({entryPoints:[path.join(root,'frontpage-source-editor.js')],target:'es2022',
    bundle:true,write:false,minify:true,format:'iife',platform:'browser',legalComments:'inline',
    sourcemap:'external',outfile:'source_editor.min.js'});
const code=result.outputFiles.find(f=>f.path.endsWith('.min.js')).text.trimEnd()+'\n//# sourceMappingURL=source_editor.min.js.map\n';
const map=JSON.parse(result.outputFiles.find(f=>f.path.endsWith('.map')).text);
map.sources=map.sources.map(s=>{const n=s.replaceAll('\\','/');return n.includes('/node_modules/')
    ?'thirdparty/'+n.split('/node_modules/').at(-1):'editor-src/'+n.split('/').at(-1);});
map.x_108design_code_sha256_lf=createHash('sha256').update(code).digest('hex');
await fs.writeFile(path.join(root,'../amd/build/source_editor.min.js'),code);
await fs.writeFile(path.join(root,'../amd/build/source_editor.min.js.map'),JSON.stringify(map)+'\n');
