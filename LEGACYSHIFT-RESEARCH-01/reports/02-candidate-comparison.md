# 02 — Сравнение кандидатов и взвешенный scoring

## Метод отбора

Discovery охватил GitHub Repository Search и независимые веб-поиски. В [candidate-registry.csv](../candidate-registry.csv) сохранены 30 проверенных repository leads с точными SHA; для каждого shortlisted кандидата исходники были склонированы в отдельный investigation workspace и прочитаны без выполнения.

Перед scoring применялись статические qualifiers:

1. публичный репозиторий и точная ревизия;
2. разрешительная лицензия в дереве исходников;
3. реальное приложение, а не только generic database library;
4. локальная SQLite persistence;
5. достаточно связный production scope для миграции и наблюдаемые side effects.

Это **static viability**, а не подтверждение runtime. Для всех кандидатов локальный runtime остаётся `NOT_RUN`.

## Как посчитаны LOC

Production PHP LOC — физическая строка внутри PHP block с хотя бы одним token, кроме whitespace, comment, HTML вне PHP и open/close tag. Markup внутри PHP string остаётся token content и не переклассифицируется вручную; это особенно важно для Helpdesk monolith. Исключены `vendor/`, third-party libraries, tests, generated code и файлы других проектов в monorepo. Setup/initializer включён только когда он нужен приложению; отдельные duplicate repair scripts не включены. Метод реализован в [tools/php_loc.py](../tools/php_loc.py), scopes — в [tools/scopes.json](../tools/scopes.json), построчные результаты — в [loc-results.json](../loc-results.json). Полный пересчёт: `python tools/reproduce_loc.py` ([script](../tools/reproduce_loc.py), Pygments 2.21.0). Подсчёт является `VERIFIED` static computation, не исполнением PHP.

## Shortlist: шесть viable кандидатов

| Кандидат и pinned revision | Production PHP scope | HTTP surface | SQLite / dependencies | Differential и setup | Score / решение |
|---|---|---|---|---|---:|
| **[car-workshop](https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c)** | **416 LOC / 4 файла** | **5 actions**, JSON | 2 таблицы; backend без Composer | 36 potential HTTP checks, включая concurrency; auto-init/seed; лучший oracle | **8,825 — primary** |
| **[Sari-Sari Store](https://github.com/x1n-Q/Sari-Sari-Store-System/tree/985a5183db7c16f675d397e23c7fa29e4605bfa4)** | **955 LOC / 6 файлов** | 9 method handlers на 4 API paths | 4 таблицы; backend без Composer; frontend CDN | Транзакции продаж и складские side effects; тестов не найдено; initializer удаляет существующую DB | **7,725 — fallback** |
| **[client-billing-manager](https://github.com/DEV-PHP-MAX/client-billing-manager/tree/cd6fc51dbcb4493933cdf3920100e2b97cb8c1a8)** | **575 LOC / 13 файлов** | 13 named routes, HTML/forms | 2 таблицы; без Composer | Auto-init/seed; invoices и notification log, но session/HTML comparison и нет test suite | **7,500** |
| **[HelpdeskPortablePHP](https://github.com/MarionForm/HelpdeskPortablePHP/tree/07e47b9c01400c2700d850473d2140b306696b91)** | **892 LOC / 9 файлов** | 17 routes, HTML/CSV/files | 5 таблиц; optional Dompdf | Богатые side effects, но auth/CSRF/uploads/random filenames усложняют oracle; `never` требует PHP 8.1 | **7,225** |
| **[php-native-rest-api](https://github.com/SolaraCZ/php-native-rest-api/tree/8789251481a8e769844d248c79ba000b31698f91)** | **393 LOC / 9 файлов** | **6 JSON routes** | 1 таблица; Composer autoload; PHP ≥8.1 | Очень простой oracle, но преимущественно generic task CRUD и мало межфайловой доменной логики | **6,850** |
| **[Listas / 03MVC](https://github.com/JavierQuinan/Listas/tree/2b143ae682e475c2ff285f49e7834001ce48c827/Proyectos/03MVC)** | **1 324 LOC / 19 файлов** | 42 action handlers в 7 controllers | 10 таблиц; bundled FPDF; Angular frontend | Auth smoke/CI есть, но нет полного domain pack; слишком широкий API и third-party scope | **6,725** |

### Важные различия, которые скрывает средний балл

- **Sari-Sari лучше по объёму и вычислительному разнообразию:** REAL money, totals/profit, invoice sequence, stock decrement/restoration, reports и exports. Но canonical initializer содержит `unlink()` существующей базы ([lines 26–30](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/init-database.php#L26-L30)), а seed values в `init-database.php` и `database-sqlite.sql` различаются. Это повышает стоимость воспроизводимого fixture.
- **client-billing ближе к желаемым 1–3 таблицам и 8–12 файлам**, но 13 routes и преимущественно CRUD/HTML делают его менее ценным для проверки тонкой PHP-семантики. [route table](https://github.com/DEV-PHP-MAX/client-billing-manager/blob/cd6fc51dbcb4493933cdf3920100e2b97cb8c1a8/public/index.php#L24-L39), [schema](https://github.com/DEV-PHP-MAX/client-billing-manager/blob/cd6fc51dbcb4493933cdf3920100e2b97cb8c1a8/src/Database.php#L38-L62).
- **Helpdesk почти идеален по LOC/files**, однако пять таблиц, 17 routes, file uploads, CSRF/session и optional PDF dependency увеличивают harness. [schema](https://github.com/MarionForm/HelpdeskPortablePHP/blob/07e47b9c01400c2700d850473d2140b306696b91/app/db.php#L19-L103), [composer](https://github.com/MarionForm/HelpdeskPortablePHP/blob/07e47b9c01400c2700d850473d2140b306696b91/composer.json).
- **Solara проще запустить и сравнивать**, но шесть CRUD routes не дают достаточной stateful/computational глубины. [routes](https://github.com/SolaraCZ/php-native-rest-api/blob/8789251481a8e769844d248c79ba000b31698f91/public/index.php#L20-L37), [composer](https://github.com/SolaraCZ/php-native-rest-api/blob/8789251481a8e769844d248c79ba000b31698f91/composer.json).
- **03MVC попадает в желаемый LOC-band**, но это лишь подкаталог multi-project repository; 42 actions, 10 таблиц и bundled third-party компоненты делают девятидневный scope рискованным. [controllers](https://github.com/JavierQuinan/Listas/tree/2b143ae682e475c2ff285f49e7834001ce48c827/Proyectos/03MVC/controllers), [schema](https://github.com/JavierQuinan/Listas/blob/2b143ae682e475c2ff285f49e7834001ce48c827/Proyectos/03MVC/database/schema.sql).

## Веса и баллы

Веса deliberately ставят воспроизводимость и differential quality выше числового сходства:

| Критерий | Вес |
|---|---:|
| Differential-test readiness: oracle, side effects, существующие checks | 25% |
| Reproducible и non-destructive setup | 20% |
| Stateful/computational depth | 15% |
| Scope fit: LOC, files, routes, migration units | 15% |
| PHP / SQLite / dependency fit | 15% |
| License и provenance | 10% |
| **Итого** | **100%** |

Каждый критерий оценён от 0 до 10. Итог — `Σ(score × weight) / 100`, шкала 0–10. Подробные значения: [scoring.csv](../scoring.csv). Баллы — `INFERRED`; LOC, routes, schema и license под ними — `VERIFIED` по pinned source.

| Ранг | Кандидат | Differential 25% | Setup 20% | Domain 15% | Scope 15% | Runtime/DB 15% | License 10% | Итог |
|---:|---|---:|---:|---:|---:|---:|---:|---:|
| 1 | car-workshop | 9,5 | 9,5 | 9,0 | 6,0 | 9,0 | 9,5 | **8,825** |
| 2 | Sari-Sari | 7,0 | 6,5 | 9,0 | 8,5 | 8,0 | 8,5 | **7,725** |
| 3 | client-billing | 6,0 | 9,0 | 6,5 | 7,0 | 8,5 | 9,0 | **7,500** |
| 4 | Helpdesk | 6,5 | 7,0 | 8,0 | 8,0 | 6,0 | 9,0 | **7,225** |
| 5 | Solara | 7,0 | 7,5 | 4,5 | 6,5 | 7,0 | 9,0 | **6,850** |
| 6 | 03MVC | 6,5 | 6,5 | 8,0 | 6,5 | 5,5 | 8,0 | **6,725** |

При другой стратегии, где LOC/files важнее oracle и setup, Sari-Sari может выйти вперёд. Здесь это не принято, потому что задание прямо запрещает числовым предпочтениям перевешивать качество differential testing.

## Почему остальные не вошли в shortlist

- **Слишком большие:** LavaGest (2 290 LOC/45 файлов), office-tracker (1 614), turkiye-api (3 881), VanillaSaaS booking tracker (3 180), rricajos task-manager (5 490), bilet-satin-alma (3 333).
- **Слишком маленькие или преимущественно generic CRUD:** transaction-ledger-api (249 LOC и только health + два business routes), grocery-broker (341), Solara оставлен только как контрастный viable CRUD, ArrestDB, php-db-api, asilvafx, Falcowoski, NinoMiquelino, IasminRDS.
- **Runtime/provenance/setup несовместимы с окном:** FarrelAD — production MySQL и `php >=8.3`; coskunsoysal — старые wildcard dependencies; Sanix — bundled third-party компоненты доминируют над first-party scope; josephfranca — 20 PHP-файлов, committed DB files и широкая схема.

Полный список и точные revisions: [candidate-registry.csv](../candidate-registry.csv).
