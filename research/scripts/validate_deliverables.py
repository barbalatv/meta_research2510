"""Validate the report's metrics, local links, pinned line links and decisions."""
from pathlib import Path
import json,re,hashlib

ROOT=Path(__file__).resolve().parents[1]
reportpath=ROOT/'LEGACYSHIFT-RESEARCH-01.md'
report=reportpath.read_text(encoding='utf-8')
e=ROOT/'evidence'
ledger=json.loads((ROOT/'evidence-ledger.json').read_text(encoding='utf-8'))
assessment=json.loads((ROOT/'candidate-comparison.json').read_text(encoding='utf-8'))
discovery=json.loads((ROOT/'discovery-ledger.json').read_text(encoding='utf-8'))
selection=json.loads((ROOT/'benchmark-selection.json').read_text(encoding='utf-8'))
provs=[]
for p in e.glob('*/provenance.json'):
    d=json.loads(p.read_text(encoding='utf-8'));d['slug']=p.parent.name;provs.append(d)
line_links=0
for claim in ledger['claims']:
    m=re.search(r'https://github.com/([^/]+/[^/]+)/blob/([0-9a-f]{40})/(.+?)(?:#L(\d+)(?:-L(\d+))?)?$',claim['url'])
    if not m:continue
    repo,sha,rel,start,end=m.groups()
    p=next(p for p in provs if p['repo']==repo and p['sha']==sha)
    file=ROOT/'candidates'/p['slug']/rel
    assert hashlib.sha256(file.read_bytes()).hexdigest()==claim['source_sha256'],claim['id']
    n=len(file.read_bytes().splitlines())
    if start:assert int(start)<=n and (not end or int(end)<=n),(claim['id'],n)
    line_links+=1
definitions=re.findall(r'^\[([A-Z][0-9]+)\]:',report,re.M)
used=set(re.findall(r'\]\[([A-Z][0-9]+)\]',report))
assert len(definitions)==len(set(definitions)),'duplicate reference definitions'
assert used<=set(definitions),used-set(definitions)
for path in re.findall(r'\]\(<(C:/[^>]+)>\)',report):assert Path(path).exists(),path
assert '{{' not in report
assert report.rstrip().endswith('**CONDITIONAL GO**')
assert len(discovery['repositories'])==30
assert len(assessment['candidates'])==8
assert len(ledger['claims'])==57
assert len(provs)==20
weights=assessment['weights']
for row in assessment['candidates']:
    assert sum(row['criteria_scores'])==row['score']
    assert all(0<=x<=w for x,w in zip(row['criteria_scores'],weights))
    assert row['runtime']=='NOT_RUN'
primary=next(r for r in assessment['candidates'] if r['repo']==selection['primary']['repo'])
assert primary['production_php_sloc']==selection['primary']['production_php_sloc']==615
assert primary['revision']==selection['primary']['commit']=='444a2f71491b755ab3f84dbaa3e4ebf5f2227805'
assert primary['score']==83
assert selection['fallback']['production_php_sloc']==392
checked={'report_lines':len(report.splitlines()),'report_characters':len(report),'discovery_repositories':30,'shortlist':8,'pinned_snapshots':20,'claims':57,'pinned_source_links_checked':line_links,'source_runtime':'NOT_RUN','artifact_validation':'PASS'}
(e/'artifact-validation.json').write_text(json.dumps(checked,ensure_ascii=False,indent=2),encoding='utf-8')
manifest={}
for file in [reportpath,ROOT/'benchmark-selection.json',ROOT/'frozen-sources.json',ROOT/'candidate-comparison.json',ROOT/'discovery-ledger.json',ROOT/'evidence-ledger.json',e/'php-loc.json',e/'integrity-verification.json',e/'validation-status.json',e/'artifact-validation.json']:
    manifest[file.relative_to(ROOT).as_posix()]=hashlib.sha256(file.read_bytes()).hexdigest()
(e/'deliverable-sha256.json').write_text(json.dumps(manifest,indent=2),encoding='utf-8')
print(json.dumps(checked,ensure_ascii=False))
