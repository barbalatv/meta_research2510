# Evidence Ledger — LEGACYSHIFT-RESEARCH-01

## Шкала уверенности

- **VERIFIED** — факт прочитан из commit-pinned artifact или независимо вычислен read-only инструментом; не означает runtime success.
- **STATICALLY_SUPPORTED** — исходники поддерживают вывод, но нужен environment/runtime check.
- **INFERRED** — экспертная оценка, scoring или план на основе перечисленных доказательств.
- **UNKNOWN** — доказательств недостаточно для утверждения.
- **NOT_RUN** — выполнение не проводилось; это не pass и не fail.

| ID | Утверждение | Уровень | Доказательство / комментарий |
|---|---|---|---|
| E-001 | Primary существует как публичный repo; выбран exact SHA `d272170d…b66c` | VERIFIED | [Pinned tree](https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c); SHA также получен `git rev-parse HEAD` в отдельном clone |
| E-002 | Primary имеет MIT license | VERIFIED | [LICENSE](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/LICENSE) |
| E-003 | Primary production scope = 416 PHP LOC / 4 файла | VERIFIED | [LOC method](tools/php_loc.py), [scope](tools/scopes.json), [results](loc-results.json); исходники: [api](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php), [config](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php), [index](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/index.php), [admin](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/admin.php) |
| E-004 | API имеет пять содержательных actions | VERIFIED | [Dispatcher, lines 9–24](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L9-L24) |
| E-005 | SQLite default, env-configured path, две таблицы | VERIFIED | [Config lines 17–29](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L17-L29), [schema lines 79–103](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L79-L103) |
| E-006 | Capacity, duplicate-by-phone/plate и admin edit rules существуют в source | VERIFIED | [Book lines 91–201](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L91-L201), [update lines 248–337](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L248-L337) |
| E-007 | SQLite write transactions используют `BEGIN IMMEDIATE` | VERIFIED | [Lock helper lines 43–50](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L43-L50) |
| E-008 | Public HTTP test driver содержит 36 checks при `curl` | VERIFIED | [README lines 81–96](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L81-L96), [tests](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/tests/api_test.php); 34 call sites, один выполняется трижды |
| E-009 | CI описывает PHP 8.3 lint и SQLite/MySQL test matrix | VERIFIED | [Workflow](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/.github/workflows/ci.yml) |
| E-010 | Lint, public smoke и differential suite выполнялись в этом исследовании | NOT_RUN | PHP runtime и авторизованная PHP disposable isolation в investigation environment отсутствовали; логов выполнения нет |
| E-011 | Upstream заявляет PHP 8 | VERIFIED | [README lines 52–60](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L52-L60) |
| E-012 | Production source не содержит очевидных PHP-8-only constructs из согласованного scan | STATICALLY_SUPPORTED | Read-only regex scan над файлами E-003; это не полноценный PHPCompatibility audit и не parser/runtime proof |
| E-013 | Primary работает на PHP 7.4 | NOT_RUN | Фактическая совместимость `UNKNOWN`; требуется отдельный isolated probe |
| E-014 | Primary backend не требует Composer/runtime packages | VERIFIED | [Pinned root tree](https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c) не содержит `composer.json`/`vendor`; [README project layout](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L113-L125) |
| E-015 | Non-destructive fresh-SQLite setup возможен без source patch | STATICALLY_SUPPORTED | `SQLITE_FILE` и `DB_DRIVER` читаются из env (E-005); schema/seed не удаляют existing rows. Полный test suite всё равно требует fresh DB |
| E-016 | MySQL `database.sql` потенциально destructive | VERIFIED | [DROP TABLE lines 1–2](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/database.sql#L1-L2) |
| E-017 | Admin не имеет login; cancel удаляет row | VERIFIED | [README limitations](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L127-L140), [cancel source](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L339-L360) |
| E-018 | Primary weighted score 8,825; Sari-Sari 7,725 | INFERRED | [Scoring](scoring.csv); входные метрики verified, веса/оценки экспертные |
| E-019 | Выбор primary является testing-first компромиссом | INFERRED | Основан на E-003–E-015; меньший LOC сознательно не компенсировался включением tests/HTML |
| E-020 | Fallback exact SHA `985a5183…bfa4` и MIT | VERIFIED | [Pinned tree](https://github.com/x1n-Q/Sari-Sari-Store-System/tree/985a5183db7c16f675d397e23c7fa29e4605bfa4), [LICENSE](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/LICENSE) |
| E-021 | Fallback = 955 LOC / 6 файлов с initializer; 822 / 5 runtime-only | VERIFIED | [LOC results](loc-results.json), [method](tools/php_loc.py) |
| E-022 | Fallback имеет 9 method handlers и 4 SQLite tables | VERIFIED | [products](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/products.php), [sales](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/sales.php), [reports](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/reports.php), [export](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/export.php), [schema](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/database-sqlite.sql) |
| E-023 | Sale create/delete изменяет stock в transactions | VERIFIED | [Sales lines 152–310](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/sales.php#L152-L310) |
| E-024 | Fallback initializer удаляет existing DB | VERIFIED | [`unlink`, lines 26–30](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/init-database.php#L26-L30) |
| E-025 | Fallback seed values различаются между initializer и SQL file | VERIFIED | [Initializer lines 119–130](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/init-database.php#L119-L130) против [SQL lines 69–80](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/database-sqlite.sql#L69-L80) |
| E-026 | Fallback first-party test suite отсутствует в tracked tree | VERIFIED | [Pinned tree](https://github.com/x1n-Q/Sari-Sari-Store-System/tree/985a5183db7c16f675d397e23c7fa29e4605bfa4) содержит только перечисленные app/docs files; утверждение ограничено этой ревизией |
| E-027 | Fallback работает на PHP 7.4 | NOT_RUN | [README требует 8.0+](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/README.md#L115-L128); фактическая 7.4 compatibility `UNKNOWN` |
| E-028 | client-billing = 575 LOC/13 файлов, 13 routes, 2 таблицы | VERIFIED | [LOC results](loc-results.json), [routes](https://github.com/DEV-PHP-MAX/client-billing-manager/blob/cd6fc51dbcb4493933cdf3920100e2b97cb8c1a8/public/index.php#L24-L39), [schema](https://github.com/DEV-PHP-MAX/client-billing-manager/blob/cd6fc51dbcb4493933cdf3920100e2b97cb8c1a8/src/Database.php#L38-L62) |
| E-029 | Helpdesk = 892 LOC/9 файлов, 17 routes, 5 таблиц; PHP 8.1 syntax | VERIFIED | [LOC results](loc-results.json), [router](https://github.com/MarionForm/HelpdeskPortablePHP/blob/07e47b9c01400c2700d850473d2140b306696b91/public/index.php), [schema](https://github.com/MarionForm/HelpdeskPortablePHP/blob/07e47b9c01400c2700d850473d2140b306696b91/app/db.php), [`never`](https://github.com/MarionForm/HelpdeskPortablePHP/blob/07e47b9c01400c2700d850473d2140b306696b91/app/utils.php#L12) |
| E-030 | Solara = 393 LOC/9 файлов, 6 routes, 1 таблица, PHP ≥8.1 | VERIFIED | [LOC results](loc-results.json), [routes](https://github.com/SolaraCZ/php-native-rest-api/blob/8789251481a8e769844d248c79ba000b31698f91/public/index.php), [composer](https://github.com/SolaraCZ/php-native-rest-api/blob/8789251481a8e769844d248c79ba000b31698f91/composer.json), [migrator](https://github.com/SolaraCZ/php-native-rest-api/blob/8789251481a8e769844d248c79ba000b31698f91/src/Database/Migrator.php) |
| E-031 | 03MVC = 1 324 LOC/19 файлов, 42 actions, 10 таблиц | VERIFIED | [LOC results](loc-results.json), [controllers](https://github.com/JavierQuinan/Listas/tree/2b143ae682e475c2ff285f49e7834001ce48c827/Proyectos/03MVC/controllers), [schema](https://github.com/JavierQuinan/Listas/blob/2b143ae682e475c2ff285f49e7834001ce48c827/Proyectos/03MVC/database/schema.sql) |
| E-032 | MIT license не равна автоматической hackathon eligibility | INFERRED | Нужны attribution и собственный вклад; юридическое заключение не делается. См. [Official Rules](https://cerebralvalley.ai/e/meta-ai-developer-hackathon/waiver) |
| E-033 | Private held-out fixture contents не включены в отчёты | VERIFIED | Reports содержат только categories/protocol; [differential plan](reports/05-differential-testing-plan.md) прямо требует отдельный private pack |
| E-034 | Shortlisted upstream worktrees не изменялись | VERIFIED | `git status --porcelain` пуст по шести shortlisted checkouts после аудита; изменения создавались только в `/home/user/legacyshift-research` |

## Что остаётся неизвестным до 26 октября

- фактический PHP 7.4 runtime любого shortlisted кандидата;
- результат public smoke и concurrency на конкретной isolated инфраструктуре;
- фактические PDO JSON types и `lastInsertId` representation в выбранном runtime;
- отсутствие platform-specific lock/timing проблем;
- окончательное hackathon eligibility решение организаторов.

Эти пункты нельзя заполнять предположениями или превращать в «verified» на основании README/CI badge.
