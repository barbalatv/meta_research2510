"""Independent source-byte verification and small lexer for the primary app.

The second lexer supports normal PHP strings/comments/tags and intentionally
rejects heredoc; primary candidate has no heredoc. It never executes PHP.
"""
from pathlib import Path, PurePosixPath
import json
import hashlib
import zipfile

ROOT=Path(__file__).resolve().parents[1]

def independent_sloc(text):
    line=1; marked=set(); i=0; mode='html'; quote=None
    while i<len(text):
        c=text[i]
        if mode=='html':
            if text.startswith('<?php',i):i+=5;mode='php';continue
            if text.startswith('<?=',i):i+=3;mode='php';continue
        elif mode=='block':
            if text.startswith('*/',i):i+=2;mode='php';continue
        elif mode=='line':
            if c=='\n':mode='php'
        elif mode=='string':
            if not c.isspace():marked.add(line)
            if c=='\\' and i+1<len(text):
                if text[i+1]=='\n':line+=1
                i+=2;continue
            if c==quote:mode='php';quote=None
        else:
            if text.startswith('<<<',i):raise ValueError('heredoc unsupported by independent checker')
            if text.startswith('?>',i):i+=2;mode='html';continue
            if text.startswith('/*',i):i+=2;mode='block';continue
            if text.startswith('//',i):i+=2;mode='line';continue
            if c=='#':mode='line'
            elif c in ("'",'"'):marked.add(line);quote=c;mode='string'
            elif not c.isspace():marked.add(line)
        if c=='\n':line+=1
        i+=1
    return len(marked)

results=[]
for p in sorted((ROOT/'evidence').glob('*/provenance.json')):
    metadata=json.loads(p.read_text(encoding='utf-8'));directory=ROOT/'candidates'/p.parent.name
    source=p.parent/'source.zip';blob=source.read_bytes()
    assert hashlib.sha256(blob).hexdigest()==metadata['zip_sha256']
    count=0
    with zipfile.ZipFile(source) as archive:
        for item in archive.infolist():
            parts=PurePosixPath(item.filename).parts[1:]
            if not parts or item.is_dir() or ((item.external_attr>>16)&0o170000)==0o120000:continue
            assert archive.read(item)==directory.joinpath(*parts).read_bytes(),(p.parent.name,parts)
            count+=1
    results.append({'snapshot':p.parent.name,'archive_sha256':metadata['zip_sha256'],'files_verified':count,'result':'MATCH'})
counts=json.loads((ROOT/'evidence/php-loc.json').read_text(encoding='utf-8'))['repos']['MarionForm__Tech2Teach-ExamSuite']
checked=[]
for row in counts['files']:
    path=ROOT/'candidates/MarionForm__Tech2Teach-ExamSuite'/row['path']
    n=independent_sloc(path.read_text(encoding='utf-8'))
    assert n==row['php_sloc'],(row['path'],n,row['php_sloc'])
    checked.append({'path':row['path'],'both_sloc_counts':n,'result':'MATCH'})
report={'snapshot_integrity':results,'primary_independent_loc':checked,'application_runtime':'NOT_RUN'}
(ROOT/'evidence/integrity-verification.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps({'archives_verified':len(results),'source_files_verified':sum(x['files_verified'] for x in results),'primary_php_files_double_counted':len(checked),'runtime':'NOT_RUN'}))
