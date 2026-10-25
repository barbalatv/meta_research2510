"""Assemble research metadata from static inspections; does not run OSS apps."""
from pathlib import Path
import json
import hashlib

ROOT = Path(__file__).resolve().parents[1]
E = ROOT / 'evidence'
loc = json.loads((E / 'php-loc.json').read_text(encoding='utf-8'))['repos']
provenance = {}
for p in E.glob('*/provenance.json'):
    provenance[p.parent.name] = json.loads(p.read_text(encoding='utf-8'))

ROWS = [
    ('MarionForm__Tech2Teach-ExamSuite', '7.4+; README + синтаксис; NOT_RUN', '10 method/path; 7 бизнес + 3 UI', 'SQLite; 5 таблиц', '0 Composer; PDO SQLite, mbstring, JSON', 'оценка, Unicode, ключи массивов, аналитика', 'локальный seed; RANDOM()/UUID/время требуют контроля', 'MIT', [16,18,13,14,9,9,4], 'случайный состав/порядок экзамена; нет тестов', 'PRIMARY_CONDITIONAL', ['public/index.php','app/Models/Exam.php','app/Models/Question.php','scripts/init_db.php','LICENSE','README.md']),
    ('sebcode__b', '7.4 синтаксически; Dockerfile 8.2; NOT_RUN', '2 method/path + bookmarklet; 4 POST actions + GET query', 'SQLite; 1 таблица на пользователя', '0 runtime packages; Composer autoload; curl, mbstring, PDO SQLite', 'empty, int casts, Unicode, SQL LIKE, JSON_FORCE_OBJECT', 'нужен локальный title server, Apache auth/rewrite', 'MIT', [15,14,17,12,9,8,3], '392 SLOC; сеть при add; PHP7.4 образ не upstream', 'FALLBACK', ['src/BookmarkManager.php','src/DB.php','htdocs/index.php','composer.json','composer.lock','Dockerfile','LICENSE']),
    ('lgg-archive__tasks.php', '7.x правдоподобно по исходникам; NOT_RUN', '2 entrypoints; 11 action values', 'JSON-файлы; без SQL', '0 Composer; sessions, JSON, date', 'loose equality, recursive merges, даты, статусы', 'снимок JSON; second-based ID и часы', 'MIT + MIT Kickstart', [15,16,14,11,8,9,3], 'коллизии id в одну секунду; общая языковая настройка; без SQLite', 'RESERVE', ['action.php','core/data.php','core/core.php','core/functions/global.php','LICENSE']),
    ('lodev09__php-todo', '>=7.3 manifest; lock допускает 7.4; NOT_RUN', '5 AJAX actions + 2 UI страницы', 'SQLite; 1 таблица', '3 direct / 6 locked runtime packages', 'truthiness, сортировки, defaults, статусы', 'lock + SQL есть; Apache и helper dependencies', 'MIT; dependencies MIT/Apache-2.0/BSD-3-Clause', [14,12,17,11,9,6,4], 'ядро невелико; init diagnostics; внешние модели', 'RESERVE', ['composer.json','composer.lock','public/ajax/requests.php','public/ajax/index.php','db/todo.sql','LICENSE']),
    ('azlux__Simple-URL-Shortener', 'пример php7.2-fpm + статический код; NOT_RUN', '5 runtime scripts; 6+ действий; installer отдельно', 'SQLite или MySQL; 2 таблицы', '0 Composer; PDO, sessions', 'empty/loose comparisons, rand, null, counter', 'schema/config есть; HTTPS sessions, RNG/time', 'MIT', [15,13,12,10,9,8,3], '300 PHP SLOC; HTML, случайные slug, secure cookies', 'RESERVE', ['README.md','inc/config.example.php','inc/bdd.php','installation.php','shorten.php','list.php','login.php','LICENSE']),
    ('kiswa__TaskBoard', 'README 7.x; CI matrix 7.4; lock допускает7.4; NOT_RUN', '39 method/pattern registrations, optional segments в одном pattern', 'SQLite/RedBean; динамическая схема', '10 direct / 50 locked packages; Angular frontend', 'состояния доски, JWT, пользователи, действия', 'Docker и lock есть; старые image/build dependencies', 'MIT', [15,19,14,6,9,8,2], '2353 SLOC core + 493 tools; 39 registrations и большой стек', 'DEPRIORITIZED', ['src/api/composer.json','src/api/composer.lock','.travis.yml','src/api/index.php','src/api/controllers/Tasks.php','Dockerfile','README.md','LICENSE']),
    ('PHPixie__Demo-Todo', 'PHPixie 3.x; разрешение зависимостей 7.4 UNKNOWN', '2 route patterns; 7 domain actions; method mapping NOT_VERIFIED', 'SQLite; 2 application tables в bundled DB', '2 direct runtime PHPixie packages; lock нет', 'статусы задач и ORM relationships', 'bundled DB; framework + symlink + unlocked deps', 'BSD-3-Clause', [10,12,14,9,9,5,2], 'framework glue заметнее доменного ядра', 'DEPRIORITIZED', ['composer.json','assets/config/database.php','bundles/app/src/Project/App/Tracker.php','bundles/app/assets/config/routeResolver.php','database.sqlite','LICENSE']),
    ('yui0__php-db-api', 'README >=7.0; статически правдоподобно; NOT_RUN', '4 CRUD verbs + login/schema commands; динамические таблицы', 'SQLite; users создаётся в source', '0 Composer; PDO SQLite; mbstring для vacuum', 'numeric/string/null, dynamic SQL, JSON serialization', 'schema users есть; ошибки фильтров/кодирования', 'MIT; встроенная auth provenance требует отдельной сверки', [14,7,10,9,6,8,2], 'универсальный CRUD без предметного workflow; не основной benchmark', 'INFRASTRUCTURE_ONLY', ['README.md','api.php','config.php','_router.php','LICENSE']),
]

assessment=[]
for slug,php,routes,db,deps,semantics,repro,license_,scores,risk,decision,files in ROWS:
    p=provenance[slug]
    row=dict(repo=p['repo'],revision=p['sha'],php=php,production_php_sloc=loc[slug]['production_php_sloc'],
             core_php_sloc=loc[slug]['totals'].get('core_php',0),routes=routes,database=db,dependencies=deps,
             semantics=semantics,reproducibility=repro,license=license_,criteria_scores=scores,score=sum(scores),risk=risk,
             decision=decision,runtime='NOT_RUN',source_urls=[f"https://github.com/{p['repo']}/blob/{p['sha']}/{f}" for f in files])
    assessment.append(row)
assessment.sort(key=lambda row: -row['score'])
(ROOT/'candidate-comparison.json').write_text(json.dumps({'date':'2026-10-25','weights':[20,20,20,15,10,10,5],'candidates':assessment},ensure_ascii=False,indent=2),encoding='utf-8')

CLAIMS = [
 ('T01','Публичный source, SHA и отсутствие tag', 'MarionForm__Tech2Teach-ExamSuite','README.md',1,103,'VERIFIED','GitHub API commit + pinned codeload; ls-remote --tags exit 0 без строк'),
 ('T02','MIT и copyright 2026 MarionForm', 'MarionForm__Tech2Teach-ExamSuite','LICENSE',1,21,'VERIFIED','прочитан pinned файл'),
 ('T03','10 method/path registrations; 7 бизнес-route slices', 'MarionForm__Tech2Teach-ExamSuite','public/index.php',33,64,'VERIFIED','статический подсчёт; группировка 7+3 аналитическая'),
 ('T04','PHP7.4 заявлен upstream; runtime не проверен', 'MarionForm__Tech2Teach-ExamSuite','README.md',47,98,'STATICALLY_SUPPORTED','README плюс чтение всех PHP файлов; PHP lint NOT_RUN'),
 ('T05','typed property совместимо с 7.4', 'MarionForm__Tech2Teach-ExamSuite','app/Router.php',4,19,'STATICALLY_SUPPORTED','чтение source + PHP 7.4 manual; не parser check'),
 ('T06','SQLite PDO + foreign_keys ON', 'MarionForm__Tech2Teach-ExamSuite','app/db.php',4,14,'VERIFIED','чтение connection setup; соединение NOT_RUN'),
 ('T07','5 таблиц и FK доступны локально', 'MarionForm__Tech2Teach-ExamSuite','scripts/init_db.php',10,59,'VERIFIED','статическое чтение DDL; SQL NOT_RUN'),
 ('T08','seed из 5 вопросов; UUID каждый раз', 'MarionForm__Tech2Teach-ExamSuite','scripts/seed_questions.php',15,65,'VERIFIED','прочитан массив; seed NOT_RUN'),
 ('T09','POST precedence, null/missing default; случайный UUID', 'MarionForm__Tech2Teach-ExamSuite','app/helpers.php',22,32,'VERIFIED','операторы присутствуют; результаты на PHP7.4 STATICALLY_SUPPORTED'),
 ('T10','Europe/Madrid; display_errors включён', 'MarionForm__Tech2Teach-ExamSuite','app/config.php',4,15,'VERIFIED','чтение source'),
 ('T11','Unicode, regex, keyed frequency, arsort', 'MarionForm__Tech2Teach-ExamSuite','app/Services/DidacticPackService.php',48,77,'VERIFIED','статическое чтение; trap outcomes не исполнялись'),
 ('T12','JSON rubric numeric keys', 'MarionForm__Tech2Teach-ExamSuite','app/Services/DidacticPackService.php',11,44,'STATICALLY_SUPPORTED','source + PHP array-key rules; runtime NOT_RUN'),
 ('T13','Intake insert и pack update отдельные statements', 'MarionForm__Tech2Teach-ExamSuite','app/Models/Intake.php',8,26,'VERIFIED','нет transaction в просмотренных функциях'),
 ('T14','Поля формы, variant whitelist и clamp count', 'MarionForm__Tech2Teach-ExamSuite','app/Controllers/ExamController.php',18,30,'VERIFIED','статическое чтение'),
 ('T15','ORDER BY RANDOM и LIKE selection', 'MarionForm__Tech2Teach-ExamSuite','app/Models/Question.php',8,40,'VERIFIED','статическое чтение SQL'),
 ('T16','Merge/de-dup/fallback не гарантирует n вопросов', 'MarionForm__Tech2Teach-ExamSuite','app/Services/ExamGeneratorService.php',9,38,'STATICALLY_SUPPORTED','разбор алгоритма; не исполнен'),
 ('T17','Exam insert и question rows без transaction', 'MarionForm__Tech2Teach-ExamSuite','app/Models/Exam.php',8,28,'VERIFIED','разбор функций; SQL NOT_RUN'),
 ('T18','Строковое сравнение ответа, round score, attempt insert', 'MarionForm__Tech2Teach-ExamSuite','app/Models/Exam.php',31,69,'VERIFIED','разбор source; outputs NOT_RUN'),
 ('T19','lastAttempt упорядочен только по created_at', 'MarionForm__Tech2Teach-ExamSuite','app/Models/Exam.php',72,78,'VERIFIED','чтение SQL'),
 ('T20','Счётчики seen/wrong обновляются отдельно', 'MarionForm__Tech2Teach-ExamSuite','app/Services/AnalyticsService.php',5,13,'VERIFIED','чтение source'),
 ('T21','JSON/GIFT/HTML export; missing exam возвращает error.txt', 'MarionForm__Tech2Teach-ExamSuite','app/Services/ExportService.php',9,56,'VERIFIED','чтение service, outputs NOT_RUN'),
 ('T22','HTML export использует date и отдельный sanitizer', 'MarionForm__Tech2Teach-ExamSuite','app/Services/ExportService.php',60,117,'VERIFIED','статическое чтение'),
 ('T23','HTML и CSS локальные; основной layout', 'MarionForm__Tech2Teach-ExamSuite','app/Views/layout.php',1,32,'VERIFIED','прочитаны layout и local asset references'),
 ('T24','Некорректный intake показывает форму, не 422 JSON', 'MarionForm__Tech2Teach-ExamSuite','app/Controllers/IntakeController.php',12,27,'STATICALLY_SUPPORTED','нет установки статуса в этом handler; runtime NOT_RUN'),
 ('T25','Missing take/results выставляет 404; grade/export иначе', 'MarionForm__Tech2Teach-ExamSuite','app/Controllers/ExamController.php',33,79,'STATICALLY_SUPPORTED','сравнение route branches; status/body не измерены'),
 ('T26','Набор views показывает результат из lastAttempt', 'MarionForm__Tech2Teach-ExamSuite','app/Views/exam_results.php',1,19,'VERIFIED','чтение view'),
 ('T27','Missing pack выставляет 404', 'MarionForm__Tech2Teach-ExamSuite','app/Controllers/PackController.php',7,15,'STATICALLY_SUPPORTED','ветка source; HTTP NOT_RUN'),
 ('T28','Optional portable launcher меняет cwd не на корень source', 'MarionForm__Tech2Teach-ExamSuite','portable/START.bat',1,8,'STATICALLY_SUPPORTED','проверка относительного public path; batch NOT_RUN'),
 ('B01','Fallback env names, typed properties, actions, fetch', 'sebcode__b','src/BookmarkManager.php',5,198,'VERIFIED','прочитан source'),
 ('B02','Одна таблица; UNIQUE link; list ordering/date', 'sebcode__b','src/DB.php',9,96,'VERIFIED','статическое чтение'),
 ('B03','GET JSON и POST JSON dispatch', 'sebcode__b','htdocs/index.php',5,68,'VERIFIED','прочитан entrypoint'),
 ('B04','Нет runtime Composer require, dev PHPUnit', 'sebcode__b','composer.json',1,12,'VERIFIED','прочитан manifest/lock'),
 ('B05','Upstream Docker PHP8.2', 'sebcode__b','Dockerfile',1,11,'VERIFIED','прочитан Dockerfile, build NOT_RUN'),
 ('B06','MIT fallback', 'sebcode__b','LICENSE',1,21,'VERIFIED','прочитан license'),
 ('B07','Basic auth вне приложения', 'sebcode__b','conf/apache-extra.conf',1,15,'VERIFIED','прочитан Apache config'),
 ('L01','Жизненный цикл tasks, empty, equality, recursive merge, time ID', 'lgg-archive__tasks.php','action.php',49,278,'VERIFIED','прочитан switch; runtime NOT_RUN'),
 ('L02','Сортировка и дни', 'lgg-archive__tasks.php','core/functions/global.php',9,32,'VERIFIED','прочитан source'),
 ('L03','JSON path, auth и date settings', 'lgg-archive__tasks.php','core/data.php',8,28,'VERIFIED','прочитан source'),
 ('L04','MIT и Kickstart notices', 'lgg-archive__tasks.php','LICENSE',1,38,'VERIFIED','прочитан license'),
 ('D01','Todo 5 AJAX handlers', 'lodev09__php-todo','public/ajax/requests.php',1,52,'VERIFIED','прочитаны все handlers'),
 ('D02','Todo SQLite schema', 'lodev09__php-todo','db/todo.sql',1,8,'VERIFIED','прочитан SQL'),
 ('D03','Todo production dependency versions', 'lodev09__php-todo','composer.lock',1,None,'VERIFIED','JSON parse packages; Composer NOT_RUN'),
 ('A01','shortener RNG/validation/defaults', 'azlux__Simple-URL-Shortener','shorten.php',4,115,'VERIFIED','чтение source'),
 ('A02','shortener schema', 'azlux__Simple-URL-Shortener','installation.php',1,38,'VERIFIED','чтение setup'),
 ('K01','Исторический Kanboard v1.0.0 AGPL, не нынешний MIT', 'kanboard__kanboard__v1.0.0','LICENSE',1,18,'VERIFIED','прочитан license точной исторической ревизии'),
 ('K02','TaskBoard CI объявляет PHP7.4, но успешное CI выполнение не проверено', 'kiswa__TaskBoard','.travis.yml',1,16,'VERIFIED','статическое чтение CI matrix и before_script; CI NOT_VERIFIED'),
 ('K03','TaskBoard имеет lock с 50 packages, constraints допускают PHP7.4', 'kiswa__TaskBoard','src/api/composer.lock',1,None,'STATICALLY_SUPPORTED','JSON parse package versions/require.php; install NOT_RUN'),
 ('P01','PrivateBin 1.3.5 смешанные licenses', 'PrivateBin__PrivateBin__1.3.5','LICENSE.md',1,31,'VERIFIED','прочитан license inventory'),
 ('S01','PHP8.2 constraint short-url', 'sen-ltd__short-url','composer.json',1,None,'VERIFIED','JSON parse manifest'),
 ('M01','PHP8.1 readonly/promoted constructor ledger', 'Mohamedajab__transaction-ledger-api','src/TransferService.php',10,14,'VERIFIED','source несовместим с PHP7.4'),
]
ledger=[]
for key,claim,slug,path,start,end,status,method in CLAIMS:
    p=provenance[slug];suffix=f'#L{start}'+(f'-L{end}' if end and end!=start else '')
    full=ROOT/'candidates'/slug/path
    ledger.append(dict(id=key,claim=claim,url=f"https://github.com/{p['repo']}/blob/{p['sha']}/{path}{suffix}",revision=p['sha'],
                       method=method,status=status,confidence='HIGH' if status=='VERIFIED' else 'MEDIUM',source_sha256=hashlib.sha256(full.read_bytes()).hexdigest()))
ledger.extend([
 dict(id='E01',claim='615 production PHP SLOC; 521 core + 94 presentation; 109 setup отдельно',url='local:evidence/php-loc.json',revision=provenance['MarionForm__Tech2Teach-ExamSuite']['sha'],method='Pygments PHP lexer 2.21.0; scripts/count_php.py; per-file hashes; docblocks excluded',status='VERIFIED',confidence='HIGH_FOR_DEFINED_METHOD'),
 dict(id='E02',claim='Runtime validation недоступна; PHP/docker/podman не найдены в PATH',url='local:evidence/validation-status.json',revision=None,method='Get-Command git,python,php,docker,podman,cloc -ErrorAction SilentlyContinue',status='VERIFIED',confidence='HIGH_FOR_THIS_SESSION_PATH_ONLY'),
 dict(id='E03',claim='PHP7.4.33 CLI manifest существует; image не запущен',url='https://registry-1.docker.io/v2/library/php/manifests/7.4.33-cli',revision='sha256:620a6b9f4d4feef2210026172570465e9d0c1de79766418d3affd09190a7fda5',method='public Registry API read; evidence/php-7.4.33-cli-manifest.json',status='VERIFIED',confidence='HIGH'),
 dict(id='R01',claim='Official waiver допускает OSS с условиями; собственность/оригинальность заявки требуется',url='https://cerebralvalley.ai/e/meta-ai-developer-hackathon/waiver',revision='HTML SHA256 4ea568bf3eb9febf594eddc8c1c09833b49a6fad415a3d7d59e2d92bbd6fa5e8',method='HTTP GET; статическое JSON decoding Next.js payload; paragraphs 28-29',status='VERIFIED',confidence='HIGH_FOR_PUBLIC_TEXT_NOT_LEGAL_ELIGIBILITY'),
 dict(id='R02',claim='Marketing page по-прежнему обещает публикацию rules до 26 октября',url='https://dev.meta.ai/events/global-hackathon',revision='HTML SHA256 696f4d8207b5b8d13e43b49b4b20e496838a52ed24fc65b9023f9d4a3071c743',method='web open + HTTP GET',status='VERIFIED',confidence='HIGH'),
 dict(id='R03',claim='PHP7.4 sorts не гарантируют стабильный порядок равных элементов',url='https://wiki.php.net/rfc/stable_sorting',revision='checked 2026-10-25',method='official PHP RFC; implemented in 8.0',status='VERIFIED',confidence='HIGH'),
 dict(id='R04',claim='PHP преобразует некоторые строковые array keys в int',url='https://www.php.net/manual/en/language.types.array.php',revision='checked 2026-10-25',method='official PHP manual + relevant source',status='VERIFIED',confidence='HIGH'),
])
(ROOT/'evidence-ledger.json').write_text(json.dumps({'date':'2026-10-25','claims':ledger},ensure_ascii=False,indent=2),encoding='utf-8')

validation={'date':'2026-10-25','source_execution':'NOT_RUN','php_lint':'NOT_RUN','composer_install':'NOT_RUN','docker_build':'NOT_RUN','http_smoke':'NOT_RUN','stateful_replay':'NOT_RUN','sqlite_reset_runtime':'NOT_RUN','repeatability':'NOT_RUN','host_tools_found':['git.exe','python.exe'],'host_tools_not_found_in_PATH':['php','docker','podman','cloc'],'notes':['No authorized ready disposable restricted runtime identified; no new infrastructure installed.','Public OSS source downloaded and statically read only.','Code search API HTTP401: no authenticated GitHub code search claimed.','RaymiiOrg/tasks.php API HTTP404; lgg fork inspected instead.','Initial console printing hit Windows encoding errors after JSON saves; files retained, subsequent PYTHONIOENCODING=utf-8.']}
(E/'validation-status.json').write_text(json.dumps(validation,ensure_ascii=False,indent=2),encoding='utf-8')

table='| Репозиторий / точный SHA | PHP | Production SLOC | Routes | БД | Зависимости | Семантика | Воспроизводимость | Лицензия | Балл | Риск |\n|---|---|---:|---|---|---|---|---|---|---:|---|\n'
for r in assessment:
    table+='| '+ ' | '.join([f"[{r['repo']}](https://github.com/{r['repo']}/tree/{r['revision']})<br>`{r['revision']}`",r['php'],str(r['production_php_sloc']),r['routes'],r['database'],r['dependencies'],r['semantics'],r['reproducibility'],r['license'],str(r['score']),r['risk']])+' |\n'
(ROOT/'candidate-comparison.md').write_text('# Сравнение кандидатов\n\nВсе runtime-проверки: **NOT_RUN**. Баллы экспертные, не измеренная вероятность успеха.\n\n'+table,encoding='utf-8')
refs='\n'.join(f"[{r['id']}]: {r['url']}" for r in ledger if r['url'].startswith('https://'))+'\n'
(E/'report-reference-definitions.md').write_text(refs,encoding='utf-8')
print(json.dumps({'shortlist':len(assessment),'pinned_snapshots':len(provenance),'unique_repositories':len(set(p['repo'] for p in provenance.values())),'claims':len(ledger)},ensure_ascii=False))
