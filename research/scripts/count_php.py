"""Physical PHP SLOC from static tokens; no PHP source is executed.

Excludes whitespace, comments, PHP delimiters and inline HTML. Multiline PHP
string literals (SQL, etc.) count on each nonblank line. Category totals keep
handwritten presentation separate from core and setup; manual scaffolding and
third-party exclusions are explicit below. Uses installed Pygments, not cloc.
"""
from pathlib import Path
import json
import hashlib
import pygments
from pygments.lexers import PhpLexer
from pygments.token import Comment, Text, Other, Punctuation, String

ROOT = Path(__file__).resolve().parents[1]
SCAFFOLD = {
    'lodev09__php-todo': {'debug.php', 'lib/models/_SomeTable.php', 'public/_config.php',
                        'public/_database.php', 'public/_install.php', 'public/_permissions.php'},
    'lgg-archive__tasks.php': {'debug.php', 'web/debug.php'},
}

def sloc(text):
    marked = set()
    line = 1
    for _, kind, value in PhpLexer(startinline=False).get_tokens_unprocessed(text):
        if kind not in Comment and kind not in String.Doc and kind not in Other and not (kind in Punctuation and value in ('<?php', '<?', '<?=', '?>')):
            for offset, fragment in enumerate(value.split('\n')):
                if fragment.strip():
                    marked.add(line + offset)
        line += value.count('\n')
    return len(marked)

def category(slug, rel):
    low = rel.lower()
    parts = low.split('/')
    if any(p in ('vendor', 'tests', 'test') for p in parts) or low in ('test.php', 'example.php') or parts[0] == 'example':
        return 'excluded_test_vendor_example'
    if rel in SCAFFOLD.get(slug, set()):
        return 'excluded_scaffolding_debug'
    if slug.startswith('PrivateBin__') and low == 'lib/raintpl.php':
        return 'excluded_third_party'
    if any(p in ('views', 'templates', 'tpl', 'locales', 'lang', 'includes') for p in parts) or low in ('inc/header.php', 'inc/footer.php', 'htdocs/bookmarklet.php'):
        return 'presentation_php'
    if parts[0] in ('scripts', 'tools', 'db', 'cfg') or low in ('installation.php', 'check_setup.php'):
        return 'setup_php'
    return 'core_php'

if __name__ == '__main__':
    result = {'method': __doc__, 'pygments_version': pygments.__version__, 'repos': {}}
    for directory in sorted((ROOT / 'candidates').iterdir()):
        files = []
        totals = {}
        for path in sorted(directory.rglob('*.php')):
            rel = path.relative_to(directory).as_posix()
            blob = path.read_bytes()
            cat = category(directory.name, rel)
            n = sloc(blob.decode('utf-8', errors='replace'))
            files.append({'path': rel, 'category': cat, 'php_sloc': n, 'sha256': hashlib.sha256(blob).hexdigest()})
            totals[cat] = totals.get(cat, 0) + n
        production = sum(totals.get(c, 0) for c in ('core_php', 'presentation_php'))
        setup = totals.get('setup_php', 0)
        result['repos'][directory.name] = {'production_php_sloc': production, 'production_plus_setup_sloc': production + setup,
                                          'totals': totals, 'files': files}
        print(directory.name, 'production=', production, 'with_setup=', production + setup, totals)
    (ROOT / 'evidence' / 'php-loc.json').write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding='utf-8')
