"""Fetch public source snapshots for STATIC inspection. Never runs candidate code."""
import concurrent.futures
import hashlib
import json
import pathlib
import urllib.request
import zipfile
import io
import datetime

ROOT = pathlib.Path(__file__).resolve().parents[1]
REPOS = [
    'azlux/Simple-URL-Shortener', 'lodev09/php-todo', 'TyMick/php-todo',
    'coskunsoysal/rest-api-sample', 'yui0/php-db-api', 'kiswa/TaskBoard',
    'Alespfer/wisenomy', 'Mohamedajab/transaction-ledger-api', 'sen-ltd/short-url',
    'seikan/Cart', 'PHPixie/Demo-Todo', 'simplonco/php-rest-sqlite',
]

def get(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'LegacyShift-Research-01', 'Accept': 'application/vnd.github+json'})
    with urllib.request.urlopen(req, timeout=60) as response:
        return response.read()

def fetch(repo, ref=None):
    slug = repo.replace('/', '__') + ('__' + ref.replace('/', '_') if ref else '')
    evidence = ROOT / 'evidence' / slug
    evidence.mkdir(parents=True, exist_ok=True)
    meta = json.loads(get('https://api.github.com/repos/' + repo))
    commit = json.loads(get('https://api.github.com/repos/' + repo + '/commits/' + (ref or meta['default_branch'])))
    sha = commit['sha']
    record = {'repo': repo, 'sha': sha, 'ref': ref or meta['default_branch'], 'branch': meta['default_branch'], 'commit_date': commit['commit']['committer']['date'],
              'retrieved_at_utc': datetime.datetime.now(datetime.timezone.utc).isoformat(),
              'metadata': meta, 'commit': commit, 'runtime': 'NOT_RUN'}
    blob = get(f'https://codeload.github.com/{repo}/zip/{sha}')
    if len(blob) > 100_000_000:
        raise ValueError('archive too large')
    record['zip_sha256'] = hashlib.sha256(blob).hexdigest()
    (evidence / 'source.zip').write_bytes(blob)
    dest = (ROOT / 'candidates' / slug).resolve()
    dest.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(io.BytesIO(blob)) as archive:
        if sum(x.file_size for x in archive.infolist()) > 200_000_000:
            raise ValueError('expanded archive too large')
        for item in archive.infolist():
            parts = pathlib.PurePosixPath(item.filename).parts[1:]
            if not parts:
                continue
            if any(x in ('..', '.', '') or ':' in x or '\\' in x for x in parts):
                raise ValueError('unsafe archive path')
            target = dest.joinpath(*parts).resolve()
            if not target.is_relative_to(dest):
                raise ValueError('archive escape')
            if ((item.external_attr >> 16) & 0o170000) == 0o120000:
                # Record symlinks but do not create/follow them.
                record.setdefault('skipped_symlinks', []).append(item.filename)
                continue
            if item.is_dir():
                target.mkdir(parents=True, exist_ok=True)
            else:
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(archive.read(item))
    (evidence / 'provenance.json').write_text(json.dumps(record, ensure_ascii=False, indent=2), encoding='utf-8')
    files = sorted(str(p.relative_to(dest)).replace('\\', '/') for p in dest.rglob('*') if p.is_file())
    (evidence / 'file-list.json').write_text(json.dumps(files, ensure_ascii=False, indent=2), encoding='utf-8')
    return {'repo': repo, 'sha': sha, 'date': record['commit_date'], 'license_metadata': (meta.get('license') or {}).get('spdx_id'), 'php_files': len([p for p in files if p.endswith('.php')]), 'zip_sha256': record['zip_sha256']}

if __name__ == '__main__':
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        pending = {pool.submit(fetch, repo): repo for repo in REPOS}
        for future in concurrent.futures.as_completed(pending):
            try:
                print(json.dumps(future.result(), ensure_ascii=False), flush=True)
            except Exception as error:
                print(json.dumps({'repo': pending[future], 'error': repr(error)}), flush=True)
