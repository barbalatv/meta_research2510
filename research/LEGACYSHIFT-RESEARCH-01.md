# LEGACYSHIFT-RESEARCH-01 — выбор PHP benchmark

Дата исследования: **25 октября 2026**, Europe/Chisinau. Объект: выбор входного OSS-приложения для LegacyShift v1.1. Архитектура LegacyShift и миграция приложений в эту работу не входят.

## 1. Executive Recommendation

**Рекомендую MarionForm/Tech2Teach-ExamSuite с условиями принятия ниже.** Это небольшое учебное MVC-приложение: создание дидактического пакета из текста, подбор экзамена, проверка ответов, накопление статистики и экспорт. Это реальный опубликованный OSS-репозиторий, а не созданный для данного исследования fixture. Наличие production deployment, длительной эксплуатации или независимого аудита автора не установлено. Проект опубликован в 2026 году; слово «legacy» здесь означает исходную платформу PHP 7.4, а не доказанную многолетнюю историю продукта. [Source и setup][T01]

| Параметр | Рекомендация и статус |
|---|---|
| GitHub | [MarionForm/Tech2Teach-ExamSuite](https://github.com/MarionForm/Tech2Teach-ExamSuite) |
| Зафиксировать | **`444a2f71491b755ab3f84dbaa3e4ebf5f2227805`**, commit от 11.02.2026 17:52:03 UTC; tag отсутствует в `git ls-remote --tags` на дату исследования |
| Source archive SHA-256 | `11d9bf5d2413bc9ca917c6c2a35101c8bbe73883fa867a8d15fb419e2883e1ec` |
| Лицензия | MIT, Copyright (c) 2026 MarionForm — **VERIFIED** по файлу точной ревизии [LICENSE][T02] |
| PHP | Upstream заявляет 7.4+; чтение source это поддерживает. **STATICALLY_SUPPORTED; PHP 7.4 lint/startup NOT_RUN** [README][T04], [Router][T05] |
| Production PHP | **615 SLOC**: 521 core + 94 PHP в представлениях; ещё 109 SLOC setup/seed, итого 724 с setup. Комментарии, docblocks, пустые строки, PHP-теги и inline HTML исключены |
| Файлы | 17 core PHP + 7 views, из которых одна полностью HTML; отдельно 2 setup PHP. Это больше предпочтительных 8–12 файлов |
| HTTP | **10 method/path registrations на 9 путях**; 7 бизнес-срезов и 3 UI/form routes. Это формовый HTTP-интерфейс, не JSON REST CRUD [routing][T03] |
| БД | SQLite через PDO; **5 таблиц**, а не желательные 1–3 [connection][T06], [schema][T07] |
| Runtime dependencies | Нет Composer/vendor/framework; нужны PDO SQLite, mbstring, JSON и стандартные PHP-функции [source inventory][T01], [Unicode processing][T11] |
| Оценка | **83/100**, экспертная статическая оценка; не вероятность успешного запуска |
| Уверенность | Высокая в SHA, лицензии, структуре и определённом LOC; средняя в PHP 7.4 feasibility; недостаточная для заявления о повторяемом full-app replay |

Выбор основан на содержании: проверка ответов, вычисление оценки и изменение счётчиков дают наблюдаемую вычислительную и stateful семантику. Отдельные service/model/controller слои позволяют оценивать 6–10 единиц, не превращая каждый файл в фиктивно независимый HTTP benchmark. [grading][T18], [analytics][T20], [controllers][T14]

**Главное ограничение — случайный состав и порядок экзамена.** SQLite `ORDER BY RANDOM()` нельзя устранить нормализацией UUID. Для сравнения проверки/экспорта можно клонировать уже подготовленный экзамен в обе БД; для точного HTTP differential теста самого генератора решение пока отсутствует. Полную HTTP-совместимость генерации нельзя объявлять по успешным тестам остальных маршрутов. [selection][T15], [generator][T16]

Условия перед принятием benchmark: запуск неизменённой ревизии в разрешённом disposable worker с PHP 7.4; повторяемость детерминированных сценариев; утверждение границы покрытия генерации. Если обязательна точная проверка всех генерационных HTTP-сценариев и её нельзя обеспечить без изменения baseline, следует перейти на fallback **sebcode/b**. Синтетическое приложение создавать не требуется.

### Эпистемический статус

- **VERIFIED** — непосредственно прочитанный source/manifest/license, измеренный статический count или полученный HTTP/Git metadata. Это не результат исполнения приложения.
- **STATICALLY_SUPPORTED** — вывод из source и требований языка без PHP runtime-проверки.
- **INFERRED** — проектная оценка, например удобство переноса в TypeScript или ожидаемая стоимость работы.
- **UNKNOWN / NOT_VERIFIED** — доказательств недостаточно. **NOT_RUN** — конкретная исполняемая проверка не проводилась.

На хосте обнаружены Git и Python; `php`, `docker`, `podman`, `cloc` не найдены в PATH. Наличие скрытого runtime вне PATH не утверждается и специально не разыскивалось. Подходящее уже готовое ограниченное окружение не установлено. Поэтому запуск PHP, Composer, Docker build, HTTP smoke, stateful replay и проверка reset/repeatability — **NOT_RUN для всех кандидатов**. Никакой скачанный PHP, batch, Makefile, Composer script или тест кандидата на хосте не исполнялся.

## 2. Candidate Comparison

Воронка: **30 уникальных репозиториев** на discovery; **18 уникальных репозиториев / 20 точных snapshots** прочитаны локально; **8** включены в сопоставимую shortlist; подробный разбор выполнен для Tech2Teach, sebcode/b, lgg/tasks.php, lodev09/php-todo и Azlux. Дополнительные старые snapshots — PrivateBin 0.22/1.3.5 и Kanboard v1.0.0. Это ограниченное исследование, не исчерпывающий поиск всего GitHub.

| Репозиторий / точный SHA | PHP | Production SLOC | Routes | БД | Зависимости | Семантика | Воспроизводимость | Лицензия | Балл | Риск |
|---|---|---:|---|---|---|---|---|---|---:|---|
| [MarionForm/Tech2Teach-ExamSuite](https://github.com/MarionForm/Tech2Teach-ExamSuite/tree/444a2f71491b755ab3f84dbaa3e4ebf5f2227805)<br>`444a2f71491b755ab3f84dbaa3e4ebf5f2227805` | 7.4+; README + синтаксис; NOT_RUN | 615 | 10 method/path; 7 бизнес + 3 UI | SQLite; 5 таблиц | 0 Composer; PDO SQLite, mbstring, JSON | оценка, Unicode, ключи массивов, аналитика | локальный seed; RANDOM()/UUID/время требуют контроля | MIT | 83 | случайный состав/порядок экзамена; нет тестов |
| [sebcode/b](https://github.com/sebcode/b/tree/d0d240bd99d12a43eb9740033854baeea09bf797)<br>`d0d240bd99d12a43eb9740033854baeea09bf797` | 7.4 синтаксически; Dockerfile 8.2; NOT_RUN | 392 | 2 method/path + bookmarklet; 4 POST actions + GET query | SQLite; 1 таблица на пользователя | 0 runtime packages; Composer autoload; curl, mbstring, PDO SQLite | empty, int casts, Unicode, SQL LIKE, JSON_FORCE_OBJECT | нужен локальный title server, Apache auth/rewrite | MIT | 78 | 392 SLOC; сеть при add; PHP7.4 образ не upstream |
| [lgg-archive/tasks.php](https://github.com/lgg-archive/tasks.php/tree/d01f9366b8e1f53ea63d26054346ad866f1ec57f)<br>`d01f9366b8e1f53ea63d26054346ad866f1ec57f` | 7.x правдоподобно по исходникам; NOT_RUN | 684 | 2 entrypoints; 11 action values | JSON-файлы; без SQL | 0 Composer; sessions, JSON, date | loose equality, recursive merges, даты, статусы | снимок JSON; second-based ID и часы | MIT + MIT Kickstart | 76 | коллизии id в одну секунду; общая языковая настройка; без SQLite |
| [lodev09/php-todo](https://github.com/lodev09/php-todo/tree/95be4451c8c46ad7f25a3baa773118200ab3224e)<br>`95be4451c8c46ad7f25a3baa773118200ab3224e` | >=7.3 manifest; lock допускает 7.4; NOT_RUN | 369 | 5 AJAX actions + 2 UI страницы | SQLite; 1 таблица | 3 direct / 6 locked runtime packages | truthiness, сортировки, defaults, статусы | lock + SQL есть; Apache и helper dependencies | MIT; dependencies MIT/Apache-2.0/BSD-3-Clause | 73 | ядро невелико; init diagnostics; внешние модели |
| [kiswa/TaskBoard](https://github.com/kiswa/TaskBoard/tree/857583e4bb508c7b449a8e45bb0747d22d88abdb)<br>`857583e4bb508c7b449a8e45bb0747d22d88abdb` | README 7.x; CI matrix 7.4; lock допускает7.4; NOT_RUN | 2353 | 39 method/pattern registrations, optional segments в одном pattern | SQLite/RedBean; динамическая схема | 10 direct / 50 locked packages; Angular frontend | состояния доски, JWT, пользователи, действия | Docker и lock есть; старые image/build dependencies | MIT | 73 | 2353 SLOC core + 493 tools; 39 registrations и большой стек |
| [azlux/Simple-URL-Shortener](https://github.com/azlux/Simple-URL-Shortener/tree/acba0eaf5b38edff285abec164e202a897ace07d)<br>`acba0eaf5b38edff285abec164e202a897ace07d` | пример php7.2-fpm + статический код; NOT_RUN | 300 | 5 runtime scripts; 6+ действий; installer отдельно | SQLite или MySQL; 2 таблицы | 0 Composer; PDO, sessions | empty/loose comparisons, rand, null, counter | schema/config есть; HTTPS sessions, RNG/time | MIT | 70 | 300 PHP SLOC; HTML, случайные slug, secure cookies |
| [PHPixie/Demo-Todo](https://github.com/PHPixie/Demo-Todo/tree/399483d98fb9f11f99781c444533734f1b35df6b)<br>`399483d98fb9f11f99781c444533734f1b35df6b` | PHPixie 3.x; разрешение зависимостей 7.4 UNKNOWN | 382 | 2 route patterns; 7 domain actions; method mapping NOT_VERIFIED | SQLite; 2 application tables в bundled DB | 2 direct runtime PHPixie packages; lock нет | статусы задач и ORM relationships | bundled DB; framework + symlink + unlocked deps | BSD-3-Clause | 61 | framework glue заметнее доменного ядра |
| [yui0/php-db-api](https://github.com/yui0/php-db-api/tree/db55be7e94d16d54e37b5c233fda3785419ba8aa)<br>`db55be7e94d16d54e37b5c233fda3785419ba8aa` | README >=7.0; статически правдоподобно; NOT_RUN | 455 | 4 CRUD verbs + login/schema commands; динамические таблицы | SQLite; users создаётся в source | 0 Composer; PDO SQLite; mbstring для vacuum | numeric/string/null, dynamic SQL, JSON serialization | schema users есть; ошибки фильтров/кодирования | MIT; встроенная auth provenance требует отдельной сверки | 56 | универсальный CRUD без предметного workflow; не основной benchmark |

Полные supporting URLs для каждой строки находятся в [candidate-comparison.json](<C:/Users/user/Documents/meta php/research/candidate-comparison.json>), поле `source_urls`. Там же сохранены компоненты оценок. LOC получен одинаковым скриптом; размер GitHub-архива или число PHP-файлов не использовались как production LOC.

### Взвешенные оценки

| Кандидат | Runtime /20 | Business /20 | Differential /20 | Units/TS /15 | License /10 | Semantics /10 | Setup /5 | Итого |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Tech2Teach | 16 | 18 | 13 | 14 | 9 | 9 | 4 | **83** |
| sebcode/b | 15 | 14 | 17 | 12 | 9 | 8 | 3 | **78** |
| lgg/tasks.php | 15 | 16 | 14 | 11 | 8 | 9 | 3 | **76** |
| lodev09/php-todo | 14 | 12 | 17 | 11 | 9 | 6 | 4 | **73** |
| TaskBoard | 15 | 19 | 14 | 6 | 9 | 8 | 2 | **73** |
| Azlux shortener | 15 | 13 | 12 | 10 | 9 | 8 | 3 | **70** |
| PHPixie Demo-Todo | 10 | 12 | 14 | 9 | 9 | 5 | 2 | **61** |
| yui0/php-db-api | 14 | 7 | 10 | 9 | 6 | 8 | 2 | **56** |

Runtime points отражают статическую подготовленность, не успешный smoke. Размер выше/ниже предпочтений не является автоматическим hard reject. TaskBoard теряет баллы за объём целого приложения, PHPixie — за unlocked framework setup, yui0 — за отсутствие предметного workflow и baseline-дефекты. yui0 рассматривается как работающий по замыслу CRUD gateway, а не как полноценная замена бизнес-приложению.

У TaskBoard действительно есть composer.lock на 50 packages и заявленная CI matrix с PHP7.4; успешный historical CI run не проверялся. В нём 39 method/pattern registrations, поэтому его исключает из первого места объём, а не отсутствие lock. У PHPixie найдены два routing patterns и семь project/task actions; полный method mapping framework не верифицирован. [TaskBoard CI][K02], [lock][K03]

**Чувствительность рейтинга.** Если нужна точная differential проверка каждого production HTTP-пути, генерация снижает оценку Tech2Teach по differential с 13 до 7, итог до 77: тогда sebcode/b с 78 становится первым. Если PHP 7.4 baseline не запускается без существенного исправления приложения, это hard disqualifier независимо от 83 баллов. Поддержка 7.4 не считается доказанной одинаково для source и непроверенных зависимостей.

### Проверка десяти вопросов для глубоко изученных кандидатов

| Проверка | Tech2Teach | sebcode/b | lgg/tasks.php | lodev09/php-todo | Azlux |
|---|---|---|---|---|---|
| Entrypoint найден | VERIFIED: public/index.php | VERIFIED: htdocs/index.php | VERIFIED: index/action.php | VERIFIED: public/index.php, ajax/index.php | VERIFIED: index/login/shorten/list.php |
| PHP7.4 plausible | STATICALLY_SUPPORTED | STATICALLY_SUPPORTED; upstream Docker8.2 | STATICALLY_SUPPORTED | Manifest/lock support; runtime NOT_RUN | STATICALLY_SUPPORTED |
| Без внешнего network | Source поддерживает | Add fetch требует local fixture server | Да, статически | После dependency provisioning | Backend да; browser assets отдельно |
| Локальная инициализация state | DDL + seed VERIFIED | DDL в DB constructor VERIFIED | JSON snapshot VERIFIED | SQL VERIFIED | installation.php VERIFIED |
| Детерминированные запросы | Для фиксированных exams; generator gap | При fixture HTTP и контроле dates/order | При контроле clock/ID; коллизии | При fixture DB и времени | Custom slug, session и clock/RNG control |
| Независимый TS port | INFERRED: прямые services/SQL | INFERRED: 2 domain classes | INFERRED: процедурные branches | INFERRED: helper library contracts | INFERRED: PDO + sessions + HTML |
| Реальные semantic risks | VERIFIED: source patterns | VERIFIED: casts/empty/JSON/Unicode | VERIFIED: merges/loose checks/dates | VERIFIED: truthiness/sort/defaults | VERIFIED: empty/null/RNG/count |
| Manageable units | 9 предложены | 6–7 возможны | 6–8 возможны | ~6, но ядро небольшое | ~6, меньше бизнес-логики |
| Held-out tests | Feasible для ограниченного coverage; INFERRED | Feasible с локальным HTTP; INFERRED | Feasible с file snapshots; INFERRED | Feasible с DB snapshots; INFERRED | Feasible с session/slug controls; INFERRED |
| License / contest | MIT VERIFIED; конкретная eligibility UNKNOWN | MIT VERIFIED; eligibility UNKNOWN | MIT notices VERIFIED; eligibility UNKNOWN | MIT и lock licenses VERIFIED; eligibility UNKNOWN | MIT VERIFIED; eligibility UNKNOWN |

### Почему ближайшие альтернативы уступают

**sebcode/b** проще сделать повторяемым: одна SQLite-таблица, add/settitle/setlink/delete и JSON query. Но source меньше предпочтений — 392 SLOC, а title enrichment всегда вызывает cURL; нужен локальный HTTP fixture. Из коробки Dockerfile выбирает PHP8.2 и не подтверждает PHP7.4. [domain/actions][B01], [DB][B02], [entrypoint][B03], [manifest][B04], [Dockerfile][B05]

**lgg/tasks.php** — более исторически правдоподобный legacy: open/closed/deleted, restore и permanent delete, due dates, priority. Однако это JSON storage; ID основан на секундном `time()`, а запись нескольких задач в одну секунду может привести к коллизии и recursive merge. Такая сложность полезна для семантики, но ухудшает baseline. [actions][L01], [dates/sort][L02], [config][L03], [licenses][L04]

**lodev09/php-todo** имеет lock на шесть runtime packages и локальный SQL для одной таблицы, что лучше unlocked frameworks. Его пять AJAX-действий полезны, но предметная логика существенно меньше заявленного целевого размера; значительная часть кода — helpers/bootstrap. [handlers][D01], [schema][D02], [lock][D03]

**Azlux** предлагает настоящий stateful shortener и историю пользователей. Его 300 production PHP SLOC не следует выдавать за ~700 строк HTML/PHP файлов; случайные slug, HTTPS-only session cookies и HTML-ответы усложняют comparator. [shortening][A01], [schema][A02]

## 3. Best Candidate Technical Audit

### Структура и объём

```text
Tech2Teach-ExamSuite/
  public/index.php                 front controller / autoload / route declarations
  public/assets/app.css            local CSS
  app/config.php                   paths, timezone, error mode
  app/helpers.php                  req, view, redirect, uuid, directories
  app/db.php                       shared PDO connection
  app/Router.php                   method/path dispatcher
  app/Controllers/                 Home, Intake, Pack, Exam
  app/Models/                      Intake, Question, Exam
  app/Services/                    DidacticPack, QuestionBank, ExamGenerator,
                                  Analytics, Export
  app/Views/                       7 handwritten views
  scripts/init_db.php              five-table DDL
  scripts/seed_questions.php       five public seed questions
  storage/uploads/.gitkeep
  storage/exports/.gitkeep
  portable/START.bat                optional launcher, not used in recommended setup
  README.md, LICENSE, .gitignore
```

На этой ревизии нет `composer.json`, vendor, Dockerfile, CI workflow или автоматических тестов. Это проверка полного file list, а не утверждение о других ветках. [Inventory/provenance](<C:/Users/user/Documents/meta php/research/evidence/MarionForm__Tech2Teach-ExamSuite/file-list.json>)

| Группа | PHP SLOC | Что включено |
|---|---:|---|
| Bootstrap, config, helpers, router, public entrypoint | 95 | Операционный PHP, не framework-generated code |
| Controllers | 99 | Четыре контроллера |
| Models | 120 | Intake, Question, Exam |
| Services | 207 | Пять services, включая export PHP |
| PHP в views | 94 | Только PHP fragments; HTML/CSS не считается |
| **Production** | **615** | 521 core + 94 presentation |
| Setup/seed | 109 | 52 init + 57 seed; приводится отдельно |
| **С setup** | **724** | Не 724 строки business logic |

Метод: Python 3.11, установленный Pygments **2.21.0**, `PhpLexer`; исключены Comment и String.Doc, whitespace, delimiters, inline HTML, tests/vendor/examples и явно перечисленные scaffold/debug файлы альтернатив. SQL и другие многострочные PHP literals считаются по непустым PHP-строкам. У основного кандидата нет исключений vendor/scaffold; setup выделен отдельно. Все 26 его PHP файлов пересчитаны вторым независимым простым lexer — результаты совпали. Все 1208 извлечённых source-файлов в 20 snapshots побайтно совпали с архивами; это integrity check, не тест приложений. [LOC](<C:/Users/user/Documents/meta php/research/evidence/php-loc.json>), [скрипт](<C:/Users/user/Documents/meta php/research/scripts/count_php.py>), [независимая проверка](<C:/Users/user/Documents/meta php/research/evidence/integrity-verification.json>)

Первый промежуточный count ошибочно включал docblocks, которые Pygments классифицирует как String.Doc. Итоговые 615/724 уже исправлены; все таблицы и JSON используют исправленный метод.

### Реальные маршруты

Все registrations находятся в [public/index.php:33–64][T03]. «7 бизнес» ниже — явная классификация, не результат HTTP-вызовов.

| Метод и путь | Назначение | Класс |
|---|---|---|
| GET `/` | Главная страница | UI |
| GET `/intake/nuevo` | Форма intake | UI |
| POST `/intake/crear` | Создать intake, построить и сохранить pack | Business |
| GET `/pack/ver?id=…` | Получить pack | Business |
| GET `/exam/generar` | Форма генерации | UI |
| POST `/exam/generar` | Выбрать вопросы, сохранить exam | Business |
| GET `/exam/tomar?id=…` | Получить экзамен для ответа | Business |
| POST `/exam/corregir` | Проверить form answers, сохранить attempt, обновить counters | Business |
| GET `/exam/resultados?id=…` | Получить последний attempt | Business |
| GET `/exam/export?id=…&type=…` | JSON, GIFT либо printable HTML | Business |

Входные тела — `application/x-www-form-urlencoded`, включая PHP nested form keys для ответов. `req()` ищет POST прежде GET и использует default при null/missing. JSON body parser для write routes отсутствует. Redirects и HTML составляют часть наблюдаемого контракта. [helpers][T09], [controller][T14]

### Схема БД

| Таблица | Ключ и существенные поля |
|---|---|
| `intakes` | TEXT id; title, raw_text, nullable pack_json, created_at |
| `questions` | TEXT id; topic/tags/difficulty/text; choices_json, correct_choice, explanation; times_seen, times_wrong |
| `exams` | TEXT id; nullable intake_id FK, variant, created_at |
| `exam_questions` | Composite PK `(exam_id, question_id)`; q_order; два FK с cascade |
| `attempts` | TEXT id; exam_id FK; REAL score_10; correct, total; created_at, detail_json |

DDL полностью локальный; SQLite server не нужен. Connection включает foreign keys. Само наличие FK не делает несколько записей атомарной операцией. [schema][T07], [connection][T06]

### Dependency и provenance inventory

Production PHP использует PHP/PDO SQLite, JSON, mbstring, preg/PCRE, random_bytes, date/time и filesystem mkdir. Bootstrap собственный. Frontend ссылается на локальный CSS; сетевые API, почтовые credentials и vendor packages в проверенном execution path не найдены. Это статический вывод из полного дерева, не network trace. [bootstrap][T03], [helpers][T09], [Unicode][T11], [layout][T23]

В корне есть MIT notice MarionForm. Отдельных bundled third-party library directories или сторонних license headers в PHP/CSS не обнаружено; независимая проверка происхождения каждой строки за пределами публичного repository не проводилась. Сохранять LICENSE, авторство, upstream URL, SHA и сведения об изменениях в распространяемых benchmark/input и производных артефактах. Нельзя приписывать команде исходное приложение. [license][T02]

### Фактические semantic risks

«VERIFIED» в этой таблице означает присутствие конструкции в source. Описанные cross-language последствия — STATICALLY_SUPPORTED, их runtime-примеры ещё не получены.

| Риск | Точное место | Что должен сохранить TS |
|---|---|---|
| Null и missing; POST precedence | [helpers.php:22–24][T09] | PHP `??` и последовательность POST → GET → default; не заменять проверкой JS truthiness |
| Числовая конверсия формы и clamp | [ExamController.php:19–26][T14] | PHP `(int)` и порядок trim/upper/default/whitelist; Number/parseInt не эквивалентны для всех входов |
| Associative keys и списки | [DidacticPackService.php:60–71][T11] | Числовые строковые keywords могут стать integer keys; `array_keys` возвращает типизированные значения. `array_values` после unique восстанавливает список |
| Равные частоты при сортировке | [DidacticPackService.php:68–71][T11] | PHP7.4 arsort не гарантирует stable tie ordering; нельзя молча перенести предположение PHP8/JS sort [PHP RFC][R03] |
| Byte length против Unicode length | [DidacticPackService.php:48–76][T11] | Сосуществуют strlen, mb_strlen, mb_substr и regex с/без `u`; UTF-16 JS length не универсальная замена |
| Numeric-string keys rubric | [DidacticPackService.php:22–26][T12] | Последовательные integer keys в PHP arrays могут сериализоваться JSON lists; JS object literal не всегда даст тот же shape [PHP arrays][R04] |
| Проверка ответа и score | [Exam.php:39–60][T18] | Missing/null проверяются отдельно; затем string casts и strict equality; score округляется до двух знаков, detail сохраняет исходное given |
| Булевы значения и счётчики | [AnalyticsService.php:7–12][T20] | `ok`, `is_correct` и инкременты times_seen/times_wrong; повторный grade — ещё одно изменение состояния |
| Merge, de-dup, fallback | [ExamGeneratorService.php:19–38][T16] | Сохранить порядок и критерии seen; не обещать ровно n вопросов и не «исправлять» выборку при переносе |
| Текстовые export contracts | [ExportService.php:32–56][T21], [114–116][T22] | Порядок choices, префиксы ответа, пробелы и конкретные replacements; JSON/HTML/GIFT требуют разных сравнений |
| Время и identity | [config.php:12][T10], [helpers.php:26–27][T09], [Exam.php:72–78][T19] | PHP использует Europe/Madrid, SQLite datetime('now') — отдельные часы/UTC; random UUID; lastAttempt при равных timestamps неоднозначен |
| Ошибки и HTTP | [IntakeController.php:16–18][T24], [ExamController.php:33–79][T25] | Ошибка формы, 404, redirect и download error.txt — разные branches; отсутствует единый JSON error schema |

Не найдены денежные операции, специальные Decimal-правила или подтверждённые операции над integers за пределами JS safe range; их нельзя приписывать приложению. Тесты таких различий относятся к отдельному **PHP Semantic Conformance Pack**, который в данном исследовании не реализован. Предсказаний «Spark/Wonder обязательно ошибётся» нет.

### Обнаруженные ограничения baseline

- Exam и строки exam_questions создаются отдельными statements, без явной transaction. Intake/pack и attempt/analytics также не обёрнуты общим transaction. Нельзя выдать желаемую атомарность за существующий контракт или исправить source до записи baseline. [intake][T13], [exam writes][T17], [analytics][T20]
- Seed создаёт только пять публичных вопросов, с новыми UUID при каждом запуске. Повторный seed не является reset: он добавляет новые строки. [seed][T08]
- Ошибки PDO/TypeError могут уйти в PHP diagnostic output; `display_errors` включён. Нужна characterization, не выдуманная схема 400/422/500. [config][T10], [controller][T25]
- Возможны частично записанные exams при сбое FK/insert; latest attempt выбирается только по секундам. Это подтверждённые source conditions, не воспроизведённые аварии. [writes][T17], [lastAttempt][T19]
- `portable/START.bat` меняет cwd на папку portable, тогда как public расположен уровнем выше. Этот optional launcher статически сомнителен; рекомендован запуск из корня по README. Launcher не выполнялся и не исправлялся. [launcher][T28]

Неизвестны: фактический lint и runtime на PHP7.4.33; SQLite/PDO fetch types конкретной сборки; повторяемость php7.4 tied sorts; реальный набор error responses; стоимость миграции и escalation; readiness двух разработчиков/их worker. Для принятия они не заменяются уверенностью автора README.

## 4. Migration Unit Map

Это предложение организации benchmark, **INFERRED**, а не начатая миграция. Одна единица может охватывать части нескольких файлов; shared functions требуют опубликованных unit contracts. Unit-level PASS не означает HTTP PASS.

| Unit | Source / контракт | Зависимости | Независимая проверка | Сложность / возможная ценность escalation |
|---|---|---|---|---|
| U1 Transport/bootstrap | public/index.php, Router, helpers/config; method/path, form input, status/headers/redirect | Нет доменных units | Table-driven request/dispatch contracts; HTTP только после wiring | Низкая–средняя; неверные casts/defaults могут потребовать repair |
| U2 Persistence и question-bank import | db.php, init_db.php, QuestionBankService, Intake persistence; rows, defaults, JSON, FK | Config U1 | SQL/DB snapshot tests в worker, импорт с фиксированными IDs | Средняя; SQL constraints и partial writes полезнее размера кода |
| U3 Didactic pack transformation | DidacticPackService: raw/title → structured pack | Без БД; Unicode runtime | Pure unit characterization PHP → TS; сравнение shape/order/строк | Средняя–высокая; regex, Unicode и PHP arrays дают основания для escalation |
| U4 Intake и получение pack | IntakeController, PackController, Intake model, intake/pack views | U1–U3 | HTTP create → Location → GET + intake row/pack_json | Средняя; complete route slice, не отдельный файл |
| U5 Exam selection/composition | Question::pickRandom, ExamGeneratorService, Exam::create/findWithQuestions | U2 | Deterministic unit oracle со scripted repository results; реальный SQL отдельно property tests | Высокая; основной риск randomness и merge semantics, не гарантия model failure |
| U6 Grading и attempts | Exam::grade/lastAttempt; exam snapshot + answer map → result/attempt | U2 и exam schema U5 | Unit/DB differential на одинаковом заранее созданном exam | Средняя–высокая; casts, round и сохранённые detail |
| U7 Analytics и grade route | AnalyticsService + ExamController::grade | U1, U2, U5, U6 | POST grade → attempt + exact counters; повторный POST; fault characterization | Средняя; отсутствие transaction нельзя «улучшать» незаметно |
| U8 Export | ExportService + export controller; snapshot/type → headers/name/body | U1, U2, U5 | JSON структурно; GIFT/HTML по format contract; missing exam отдельно | Средняя; escaping/order/time дают ценность verifier/repair |
| U9 Exam UI и whole-app integration | HomeController, generate/take/results views, generate/take/results handlers | U1–U8 | HTTP regression по состояниям + все DB tables + граница stochastic coverage | Средняя; wiring и observable behavior, не самостоятельный pure unit |

Исходные границы подтверждены [routing][T03], [persistence][T13], [pack service][T11], [generation][T16], [grading][T18], [analytics][T20], [export][T21], [result view][T26].

Dependency-aware порядок: **U1 → U2 → U3 → U4 → U5 → U6 → U7 → U8 → U9**. U3 допустимо изучать параллельно U2 организационно, но это не запуск дополнительных агентов в данном исследовании. Контракт результата U3 можно заморозить до контроллера. U5 имеет три различных verdict: composition-unit, SQL selection properties, full HTTP differential. Их нельзя объединить одним PASS.

Unit runner может подставлять scripted `Question`/`Exam` collaborators при загрузке неизменённого generator service в изоляции. Такой тест проверяет PHP merge-алгоритм, но не управляет SQLite RANDOM() в реальном HTTP handler. Реализация runner не выполнялась. Принятие U9 требует явно показать оставшиеся NOT_ASSESSED части.

## 5. Differential Testing Plan

### Два контура проверки

1. **Точное differential ядро:** одинаковые SQLite snapshots с фиксированными question/exam rows; HTTP чтение, grade, counters, export, invalid paths; отдельно intake → pack с узкой identity/time normalization.
2. **Стохастическая генерация:** composition-unit с фиксированными collaborator outputs и свойства реальной SQL-выборки. До решения paired randomness это не точный full-route differential benchmark генератора.

Первый контур достаточно содержателен для исследования независимого verifier, repair и стоимости моделей. Но он не доказывает миграцию всего приложения. Если frozen acceptance LegacyShift требует второй контур как точное равенство реальных HTTP trace, основной кандидат нельзя принимать без решения этого пробела; fallback сохраняет архитектуру проекта.

```text
Immutable fixture.sqlite + manifest
              |
       две независимые копии
        /                  \
 PHP7.4 worker          TypeScript worker
 HTTP scenario          тот же logical scenario
 response + DB rows     response + DB rows
        \                  /
        независимый comparator
       PASS / FAIL / NOT_ASSESSED
```

### Подготовка и reset

- Выполнить upstream init/seed **один раз в worker**, либо создать fixture data независимым автором по опубликованной DDL. Новые вопросы — тестовые данные, не увеличение исходного приложения ради LOC.
- Для exact grading/export один раз подготовить exams с определёнными question IDs и q_order, остановить PHP и сохранить seed snapshot + SHA-256. Скопировать одни и те же байты в PHP и TS workspaces. Запретить повторный seed между сторонами.
- Перед каждым независимым сценарием остановить request processing, закрыть SQLite connections, восстановить чистую копию. Если используются WAL/journal, snapshot должен быть получен после чистого закрытия/корректного SQLite backup; копирование одного активного `.sqlite` недостаточно.
- Сравнивать **логические данные**, SQLite types/NULL и constraints по согласованному manifest, а не побайтный файл после записи: эквивалентные БД могут иметь разное размещение страниц. Снимок до запуска, напротив, проверяется digest.

### Открытые сценарии разработки

| Сценарий | Наблюдения и финальное состояние |
|---|---|
| Создать intake → прочитать pack | Redirect/Location, HTML, JSON pack shape; одна intake row и её update |
| Фиксированный exam → grade → results → export | Attempt, оценка/detail, вопросы/counters, JSON/GIFT/HTML contracts |
| Повторный grade | Дополнительный attempt и повторное изменение counters; отсутствие idempotency является текущим поведением |
| Отсутствующий pack/exam | Различия take/results/export/grade; изменения БД должны соответствовать референсу |
| Невалидный intake / unknown route | Форма с ошибкой или 404, точные body/status; отсутствие лишних записей |
| FK/неполный write в отдельном fault scenario | Зафиксировать реальное поведение; не ожидать rollback, которого source не реализует |

Маршруты для update/query/invalid есть через сохранение pack и counters; **DELETE endpoint отсутствует**. Нельзя изображать полный REST CRUD набор. Источники workflow: [intake][T24], [exam controller][T25], [analytics][T20], [export][T21].

### Nondeterminism и правила comparator

| Источник | Правило и предел |
|---|---|
| UUID intake/exam/attempt | Бижективная карта новых identities, согласованная между Location, body и всеми FK. Existing/invalid fixture IDs сравнивать точно. Нельзя просто вырезать все ID поля |
| Seed question UUID | Общий immutable snapshot до двух запусков, а не два независимых seed |
| SQLite RANDOM() | Не нормализовать произвольный состав и порядок как «эквивалентные». Это скроет регрессию selection; до управления источником результат full differential генератора NOT_ASSESSED |
| PHP date / SQLite datetime | Либо управляемые часы worker, либо строго перечисленные generated timestamp fields с проверкой формата/диапазона/связей; исходные fixture dates сравнивать точно |
| LastAttempt timestamp ties | Один новый attempt на isolated scenario либо управляемое разнесение времени; отдельный тест ties с честной оговоркой SQL ambiguity |
| mbstring/PCRE и arsort | Зафиксировать PHP patch, architecture, mbstring encoding и locale; равные частоты не удалять из проверки сортировки |
| JSON | Objects сравнивать структурно; сохранять distinction list/object, null/missing, string/number и порядок массивов. Pretty whitespace несущественен только для JSON |
| GIFT/HTML/text errors | Точные content type, disposition, redirects и значимые bytes/escaping; не сортировать options и не вырезать diagnostic body без отдельного правила |
| DB result ordering | `q_order` и другие явно заданные orders значимы. Строки таблиц для final-state dump сортирует comparator по PK, а не случайному natural scan |

Часы, UUID и map normalization можно проектировать без переписывания бизнес-логики. SQLite randomness остаётся отдельным blocker. Unit mocks, property checks и нормализация identity не являются доказательством точной full-app repeatability.

### Controls, held-out и измерения

**Positive control:** два независимых запуска того же PHP SHA на одинаковом snapshot в детерминированном контуре должны совпасть после заранее описанной минимальной normalization. Сначала измерить self-diff; при его нестабильности не оценивать модели.

**Negative controls:** отдельные намеренно повреждённые target-варианты с неверным score, пропущенным counter update, неправильным null/default handling или изменённым response status. Comparator обязан обнаруживать каждый такой дефект. Ни mutants, ни контролы в этой работе не создавались.

**Held-out:** второй разработчик хранит закрытые fixtures/oracle outputs вне migration context. Публикуется manifest/version/hash и критерии покрытия, не содержимое. Migration/repair agent видит только training/dev tests и разрешённые failure summaries; verifier не обращается к той же модели как к единственному oracle. Учитывать явные ограничения inference про силу секретности, если оба разработчика имеют доступ к evaluator. Закрытый набор в этой работе не создавался и не раскрывался.

Для hypothesis заранее фиксируются: исходный SHA, unit definitions, model/version, prompt, compiler result, разрешённые repair attempts и escalation policy. Считать first-pass и post-repair acceptance, число regressions, стоимость токенов/вызовов и время по units. Раздельно показывать unit, HTTP и full-app coverage; не складывать метрики conformance pack с приложением так, будто это один OSS workload. Ценность Spark/Wonder escalation — экспериментальная гипотеза, не установленный результат.

## 6. Setup Feasibility

### Статус и необходимые средства

Ниже **план, все команды запуска NOT_RUN**. Исполнять только внутри уже разрешённого disposable Linux VM/container worker с ограниченными filesystem/network и без пользовательских credentials. Хост Windows использовался только для downloads/static inspection. Новая инфраструктура не устанавливалась.

Нужны PHP **7.4.33**, PDO SQLite, mbstring, JSON, writable private storage; Git нужен только для получения snapshot, curl — для открытого smoke. Composer, Node frontend build, SQLite daemon и внешние services основному приложению не нужны. Typed properties и arrow functions соответствуют PHP7.4; это подтверждает plausible syntax, но не заменяет lint. [PHP7.4 features](https://www.php.net/manual/en/migration74.new-features.php), [release7.4.33](https://www.php.net/releases/7_4_33.php), [upstream requirements][T04]

**Docker reference проверен только через Registry API:** `php:7.4.33-cli` существует; multiarch index digest `sha256:620a6b9f4d4feef2210026172570465e9d0c1de79766418d3affd09190a7fda5`, Linux/amd64 manifest `sha256:691f9ae2a3639de11d95f507bc29c723a7f27b79bdf91317aef8ded35f9864ce`. Image не скачивался и не запускался; состав extensions не проверен. Это основа worker, не готовый протестированный образ приложения. [Official PHP images](https://hub.docker.com/_/php), [saved manifest](<C:/Users/user/Documents/meta php/research/evidence/php-7.4.33-cli-manifest.json>)

Source не читает application env vars и не имеет `.env` loader. `DB_PATH` здесь **PHP constant**, не env setting. Для worker фиксировать только имена `TZ`, `LANG`, `LC_ALL` в manifest; учитывать, что source сам устанавливает Europe/Madrid. Секретные env vars для baseline не требуются. [config][T10]

### Получение и предварительная проверка в worker

Система команд ниже — shell внутри одноразового Linux workspace; это не команды для запуска PHP на пользовательском Windows-хосте.

```sh
git clone --no-checkout https://github.com/MarionForm/Tech2Teach-ExamSuite.git benchmark-input
git -C benchmark-input checkout --detach 444a2f71491b755ab3f84dbaa3e4ebf5f2227805
git -C benchmark-input rev-parse HEAD
cd benchmark-input
php -v
php -m
php -r 'exit((PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION === 4 && extension_loaded("pdo_sqlite") && extension_loaded("mbstring") && extension_loaded("json")) ? 0 : 1);'
find app public scripts -type f -name '*.php' -print0 | xargs -0 -n 1 php -l
```

Получение snapshot требует сети до runtime-фазы; replay — с внешним egress выключенным и разрешённым loopback. На worker code copy отдельный от архивного input, storage отдельный и одноразовый. Нельзя монтировать домашние каталоги/credentials или применять команды к Casper/другим проектам. При отсутствии подходящего worker остановиться на NOT_RUN, не устанавливать новую инфраструктуру ради исследования.

### Инициализация и startup

Из корня свежей рабочей копии выполнить upstream команды:

```sh
php scripts/init_db.php
php scripts/seed_questions.php
php -S 127.0.0.1:8080 -t public
```

README использует localhost, а здесь выбран loopback IP. Front controller соответствует document root; не использовать сомнительный portable launcher. Отдельный router script не добавлен к приложению. [upstream setup][T04]

**Открытый smoke, не held-out:** во втором терминале того же worker:

```sh
curl -sS -D home.headers -o home.html http://127.0.0.1:8080/
curl -sS -D missing.headers -o missing.txt 'http://127.0.0.1:8080/pack/ver?id=missing'
curl -sS -D invalid.headers -o invalid.html --data 'title=&raw_text=' http://127.0.0.1:8080/intake/crear
curl -sS -D create.headers -o create.html --data-urlencode 'title=Open smoke' --data-urlencode 'raw_text=Redes redes seguridad seguridad procedimientos' http://127.0.0.1:8080/intake/crear
```

Ожидания **STATICALLY_SUPPORTED**: home 200; missing pack 404; invalid intake обычно 200 с формой ошибки и без insert; valid create обычно 302 с Location. Это не измеренные ответы. Затем извлечь Location, запросить созданный pack и проверить intake/pack_json. Для grade/export smoke использовать заранее подготовленный exam fixture; точные fixture IDs определяет его manifest, поэтому команды с вымышленными существующими IDs не приводятся. [intake controller][T24], [pack behavior][T27]

Reset — восстановление копии immutable fixture **после остановки worker/соединений**, например `cp /fixtures/seed.sqlite storage/tech2teach.sqlite` внутри одноразовой рабочей копии. `/fixtures/seed.sqlite` — предложенный путь будущего evaluator, такого файла в upstream и в текущем исследовании нет. Не заменять reset повторным `seed_questions.php`.

Записывать stdout/stderr и exit code каждого шага, PHP/SQLite/extensions/image digest, request/response headers/body, DB before/after и нормализацию. Первые gates: lint 26 файлов, startup, успешный request, invalid request, stateful sequence, reset, два self-replay. Сейчас каждый из них **NOT_RUN**, готовой команды upstream test suite нет.

### Конкретные blockers 26 октября

| Blocker | Критерий разрешения |
|---|---|
| Нет разрешённого PHP7.4 disposable worker | Команда предоставляет существующий worker; без него runtime readiness не заявлять |
| Extensions старого PHP image неизвестны | php -m и capability guard проходят в worker; иначе согласованная подготовленная сборка или fallback |
| Нет lint/smoke evidence | Неизменённый source проходит первые gates; существенный repair означает смену кандидата |
| RANDOM() selection | Либо проверяемый paired control без существенной переделки source, либо заранее принятая ограниченная coverage; если full-route exact обязателен — fallback |
| Clock/UUID/order | PHP self-diff проходит; comparator не скрывает регрессии и проходит negative controls |
| Правила/публичная заявка | Повторно прочитать актуальные условия; отделить OSS input от новой работы и оформить notices |

### Лицензия и hackathon provenance

Публичный [official waiver](https://cerebralvalley.ai/e/meta-ai-developer-hackathon/waiver) допускает OSS при соблюдении лицензий и развитии исходного продукта, одновременно требуя оригинальности и прав на заявку. Текст извлечён из JSON payload; HTML/hash сохранены. [Страница Meta](https://dev.meta.ai/events/global-hackathon) всё ещё обещает публикацию правил до старта, хотя waiver уже доступен.

Обязательный формат раскрытия reuse, требование публичного GitHub и полный запрет заранее выбранного benchmark в просмотренном тексте не найдены; это не разрешение любого reuse. **Рекомендация:** раскрыть Tech2Teach как input, сохранить URL/SHA/LICENSE и описать новый вклад LegacyShift; перепроверить условия перед подачей. Это исследовательская рекомендация оформления. Eligibility конкретной submission остаётся **UNKNOWN**: MIT её не доказывает.

## 7. Fallback Recommendation

**Второй выбор — [sebcode/b](https://github.com/sebcode/b), SHA `d0d240bd99d12a43eb9740033854baeea09bf797`** от 29.12.2022, MIT. Архив SHA-256 `8cd0aec78f53c6ef414abccf56c245835574ffdce6541bd20aa6efcd5eeb9cf7`. Production PHP 392 SLOC; меньше целевого диапазона, но это настоящий SQLite bookmark workflow с query, четырьмя изменяющими actions, unique-link validation, tags и Unicode title extraction. [source][B01], [schema][B02], [license][B06]

Он безопаснее именно по **повторяемости данных**: нет SQL RANDOM(), UUID или model-selected sampling; ID — SQLite integer key. Есть CURRENT_TIMESTAMP и ordering по date, поэтому даже здесь snapshot и управление ties обязательны. Источник title fetch заменять не требуется: worker может обслуживать заранее подготовленные страницы на локальном fixture HTTP endpoint. Внешний egress запретить; `force=true` не отменяет сам fetch и не является общим решением. [DB][B02], [fetch][B01]

Минимальные окруженческие адаптации, **не внесённые в source**:

1. Подготовленный PHP7.4 Apache worker с PDO SQLite, curl и mbstring; source typed properties статически подходят для7.4, upstream Dockerfile8.2 не использовать как доказательство7.4.
2. Composer autoload без runtime packages; сохранить manifest/lock. Полный dependency-install в этой работе NOT_RUN.
3. Создать writable test-user directory и Basic auth fixture по Apache config; имена env: `BASE_DIR`, `BASE_URI`, `INFINITE_SCROLLING`, `USERID`, `GID`, `APACHE_RUN_USER`, `APACHE_RUN_GROUP`. Не придумывать app JWT/API secrets.
4. Закрепить response bodies локальных title pages, даты и DB snapshots. Проверять add → query → settitle/setlink → invalid duplicate → delete → final rows.

Источники setup: [composer][B04], [Dockerfile][B05], [Apache auth][B07], [config/actions][B01]. Baseline fallback тоже не исполнялся; его переход означает новый smoke gate, не автоматическую готовность.

**Триггеры fallback:** основной source не проходит7.4 без существенного ремонта; paired generation — обязательное условие и не решено к окончанию согласованного setup timebox 26 октября; либо повторный PHP self-diff остаётся нестабилен после честного контроля identity/time. Предлагаемый timebox — первые 2–3 часа доступной командной работы; это план, не требование конкурса. Выбирая меньший fallback, не наращивать source искусственно и честно отделить Semantic Conformance Pack.

## Приложение A. Broad discovery и исключения

Следующая таблица содержит ровно 30 реальных кандидатов. Для строк **DISCOVERY_ONLY** размер оставлен NOT_VERIFIED, manifest/README читались по HEAD и не доказывают совместимость исторических tags. Полный журнал уточняет stage и доступность каждого файла. Часть исключений — ранняя приоритизация, а не формально доказанный hard disqualifier.

| Репозиторий | Поведение | PHP evidence | Размер production PHP | БД | License evidence | Решение |
|---|---|---|---|---|---|---|
| [MarionForm/Tech2Teach-ExamSuite](https://github.com/MarionForm/Tech2Teach-ExamSuite) | учебные пакеты, экзамены, оценка, экспорт | README 7.4+ + static support | 615 | SQLite | MIT | shortlist: основной условный выбор |
| [sebcode/b](https://github.com/sebcode/b) | закладки, tags/filter, title extraction | 7.4 static syntax; Docker8.2 | 392 | SQLite | MIT | shortlist: fallback |
| [lgg-archive/tasks.php](https://github.com/lgg-archive/tasks.php) | task lifecycle, dates, trash, priority | 7.x static support | 684 | JSON files | MIT + MIT Kickstart | shortlist: резерв без SQLite |
| [lodev09/php-todo](https://github.com/lodev09/php-todo) | task state/priority/sorting | composer >=7.3; lock допускает7.4 | 369 | SQLite | MIT | shortlist |
| [azlux/Simple-URL-Shortener](https://github.com/azlux/Simple-URL-Shortener) | short links, users/history/views | 7.2 example + static support | 300 | SQLite/MySQL | MIT | shortlist |
| [kiswa/TaskBoard](https://github.com/kiswa/TaskBoard) | Kanban, users, comments, automatic actions | README 7.x; resolver UNKNOWN | 2353 | SQLite/RedBean | MIT | shortlist; объём выше предпочтений |
| [PHPixie/Demo-Todo](https://github.com/PHPixie/Demo-Todo) | projects/tasks | PHPixie3 deps; 7.4 UNKNOWN | 382 | SQLite | BSD-3-Clause | shortlist; unlocked dependencies |
| [yui0/php-db-api](https://github.com/yui0/php-db-api) | schema-driven CRUD/auth API | README >=7.0; NOT_RUN | 455 | SQLite | MIT | shortlist; infrastructure-only, низкая предметная ценность |
| [coskunsoysal/rest-api-sample](https://github.com/coskunsoysal/rest-api-sample) | users CRUD + auth | composer >=5.4, wildcards | 276 | SQLite | MIT License.txt | не выбран: 276SLOC, без lock, только 4 CRUD routes |
| [TyMick/php-todo](https://github.com/TyMick/php-todo) | однофайловый todo | 7.4 STATICALLY_SUPPORTED | 73 | SQLite | MIT LICENSE.md; UI footer CC-BY4 | не выбран: 73 PHP SLOC; преимущественно HTML |
| [simplonco/php-rest-sqlite](https://github.com/simplonco/php-rest-sqlite) | single-file CRUD | 7.4 UNKNOWN | 60 | SQLite | UNKNOWN; manifest/LICENSE отсутствуют | HARD REJECT: verifiable redistribution license отсутствует |
| [seikan/Cart](https://github.com/seikan/Cart) | shopping cart class + example | 7.4 static plausible | 216 | PHP session | MIT | не выбран: standalone library; HTTP adapter пришлось бы создавать |
| [sen-ltd/short-url](https://github.com/sen-ltd/short-url) | REST shortener, base62/clicks | composer >=8.2 | 474 | SQLite | MIT | HARD REJECT этой ревизии: PHP8.2 |
| [Mohamedajab/transaction-ledger-api](https://github.com/Mohamedajab/transaction-ledger-api) | atomic transfers/idempotency | PHP8.2 README; readonly/never source | 230 | SQLite | MIT | HARD REJECT этой ревизии: PHP8 syntax |
| [Alespfer/wisenomy](https://github.com/Alespfer/wisenomy) | shared expenses/settlement | composer >=8.1 | 2575 | SQLite/PostgreSQL | MIT | HARD REJECT этой ревизии: PHP8.1; 2575SLOC |
| [PrivateBin/PrivateBin](https://github.com/PrivateBin/PrivateBin) | encrypted paste/discussion lifecycle | historical 0.22/1.3.5 checked statically; runtime NOT_RUN | 1953 (0.22); 2720 (1.3.5) | filesystem/SQL | Zlib core + mixed bundled licenses | не выбран: 1953SLOC в0.22, 2720 в1.3.5; frontend crypto + licenses/time/state |
| [kanboard/kanboard](https://github.com/kanboard/kanboard) | Kanban projects/tasks | v1.0.0 README >=5.3.7 | 2349 (v1.0.0) | SQLite | v1.0.0 AGPL-3.0; metadata main MIT | не выбран: историческая лицензия не permissive, 2349SLOC |
| [ronaldaug/sim-rest](https://github.com/ronaldaug/sim-rest) | JSON DB/routing/auth toolkit | README PHP7+ | 492 | JSON files | MIT | не основной: framework/demo posts/cars, 492SLOC |
| [amirsanni/Mini-Inventory-and-Sales-Management-System](https://github.com/amirsanni/Mini-Inventory-and-Sales-Management-System) | склад, продажи, скидки/VAT | README >=5.4; 7.4 NOT_VERIFIED | NOT_VERIFIED | MySQL/SQLite | MIT по GitHub discovery; файл license не получен | не short-list: CodeIgniter и полный UI; размер/схема NOT_VERIFIED |
| [NexusForgeLab/StockTrack](https://github.com/NexusForgeLab/StockTrack) | склад и audit log | README 8.2+, manual 8.0+ | NOT_VERIFIED | SQLite | UNKNOWN; общая фраза о redistribution без проверенного LICENSE | не выбран: 7.4 и license не подтверждены |
| [JuneNouh/php-Hairdresser-Full-UI-Admin](https://github.com/JuneNouh/php-Hairdresser-Full-UI-Admin) | бронирование парикмахерской | README PHP8+ | NOT_VERIFIED | SQLite | All Rights Reserved по README | HARD REJECT: proprietary license |
| [scrow/PHP-Inventory](https://github.com/scrow/PHP-Inventory) | домашний inventory, вложения | README PHP5+; 7.4 NOT_VERIFIED | NOT_VERIFIED | MySQL | GPL-2.0 LICENSE.md | не выбран: не permissive; MySQL/Imagick/zip stack |
| [mevdschee/php-crud-api](https://github.com/mevdschee/php-crud-api) | универсальный SQL REST gateway | composer >=7.2.0 | NOT_VERIFIED | SQLite/MySQL/PostgreSQL/SQL Server | MIT LICENSE | не основной: инфраструктура, собственный domain workflow отсутствует |
| [dspinozz/PHP-Rest-API](https://github.com/dspinozz/PHP-Rest-API) | REST framework | composer ^8.2 | NOT_VERIFIED | PDO several DBs | MIT LICENSE | HARD REJECT этой ревизии: PHP8.2 framework |
| [Inventoros/Inventoros](https://github.com/Inventoros/Inventoros) | полный WMS | composer ^8.4.1 | NOT_VERIFIED | SQLite/MySQL/PostgreSQL | AGPL-3.0-only LICENSE/composer, несмотря на старый MIT search snippet | HARD REJECT текущего source: PHP8 + объёмный Laravel stack; не permissive |
| [WebEngineBlueprints/todo-list](https://github.com/WebEngineBlueprints/todo-list) | CRUD todo | UNKNOWN; phpgt/webengine dev-master | NOT_VERIFIED | SQLite | UNKNOWN; LICENSE не найден в проверенных путях | не выбран: license и unlocked framework compatibility |
| [MarionForm/HelpdeskPortablePHP](https://github.com/MarionForm/HelpdeskPortablePHP) | helpdesk, assets, hash/export | README PHP8; 7.4 NOT_VERIFIED | NOT_VERIFIED | SQLite | MIT LICENSE | резерв вне shortlist; PHP8 profile и dompdf ^2.0 |
| [SolaraCZ/php-native-rest-api](https://github.com/SolaraCZ/php-native-rest-api) | native JSON API | composer >=8.1 | NOT_VERIFIED | SQLite | MIT LICENSE | HARD REJECT этой ревизии: PHP8.1 constraint |
| [Falcowoski/API_REST_PHP_SQLITE](https://github.com/Falcowoski/API_REST_PHP_SQLITE) | REST interview exercise | README PHP8; 7.4 UNKNOWN | NOT_VERIFIED | SQLite | MIT LICENSE | не выбран: PHP7.4 и достаточная business logic не установлены |
| [MahnoorPortfolio/Inventory_Management_System](https://github.com/MahnoorPortfolio/Inventory_Management_System) | small inventory CRUD | UNKNOWN | NOT_VERIFIED | SQLite по README | MIT LICENSE | резерв discovery; PHP7.4/semantic depth NOT_VERIFIED |


Важный provenance результат: исторический Kanboard v1.0.0 имеет **AGPL**, хотя metadata нынешней ветки говорит MIT; license нельзя переносить между ревизиями. У Inventoros raw HEAD LICENSE/composer сейчас указывает AGPL/PHP8.4.1, несмотря на MIT/PHP8.3 в поисковом представлении. PrivateBin core Zlib не отменяет bundled mixed licenses. [Kanboard historical LICENSE][K01], [PrivateBin license inventory][P01], [Inventoros source manifest](https://github.com/Inventoros/Inventoros/blob/HEAD/composer.json)

### Поиск и доступность

Использованы web/GitHub repository queries по PHP SQLite REST/CRUD, native JSON API, shopping cart, inventory, booking/reservation, expense/invoice и историческим7.x приложениям; отдельно examined tags/old commits. Четыре GitHub Search API response сохранены в evidence/github_search_1.json…4.json. Полный discovery ledger: [discovery-ledger.json](<C:/Users/user/Documents/meta php/research/discovery-ledger.json>).

Прямой GitHub code-search API вернул **HTTP401** без authentication. Это ограничение явно сохранено; успешный authenticated code search не заявляется. Дополнительно применялись web code-string queries и локальный поиск `PDO`, SQLite DSN, `json_encode/json_decode`, `php://input`, request method, superglobals, transactions/status/routing по уже полученным source snapshots. README-only snippets не использовались как доказательство core behavior shortlist.

`RaymiiOrg/tasks.php` не получен через GitHub API (404); вместо него изучен публичный lgg-archive fork с собственным MIT notice и attribution исходного автора. Первые console prints столкнулись с Windows encoding; JSON уже был сохранён, затем использован PYTHONIOENCODING=utf-8. Неудачи сохранены как ограничения исследования, не как ошибки runtime кандидатов. [access checks](<C:/Users/user/Documents/meta php/research/evidence/access-checks.json>), [validation status](<C:/Users/user/Documents/meta php/research/evidence/validation-status.json>)

## Приложение B. Evidence pack

- [Evidence ledger: 57 claims, URLs, SHA, method, status, confidence](<C:/Users/user/Documents/meta php/research/evidence-ledger.json>).
- [Shortlist README/license/dependency/Docker/CI/test inventory](<C:/Users/user/Documents/meta php/research/evidence/shortlist-inventory.json>).
- [Shortlist и component scores](<C:/Users/user/Documents/meta php/research/candidate-comparison.json>).
- [30 repository discovery ledger](<C:/Users/user/Documents/meta php/research/discovery-ledger.json>).
- [Per-file PHP counts/hashes](<C:/Users/user/Documents/meta php/research/evidence/php-loc.json>).
- [20 archive/source integrity checks и второй LOC lexer](<C:/Users/user/Documents/meta php/research/evidence/integrity-verification.json>).
- [Результаты/NOT_RUN](<C:/Users/user/Documents/meta php/research/evidence/validation-status.json>).
- [Основной raw provenance/commit/API](<C:/Users/user/Documents/meta php/research/evidence/MarionForm__Tech2Teach-ExamSuite/provenance.json>).
- [Замороженный выбор и gates](<C:/Users/user/Documents/meta php/research/benchmark-selection.json>).

Архивы и извлечённые source snapshots сохранены только под research/evidence и research/candidates. Upstream source не правился; byte verification подтверждает это для извлечённых файлов. Existing user projects/Casper не изменялись. Публикация, push, deployment и реализация LegacyShift не выполнялись.


[T01]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/README.md#L1-L103
[T02]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/LICENSE#L1-L21
[T03]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/public/index.php#L33-L64
[T04]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/README.md#L47-L98
[T05]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Router.php#L4-L19
[T06]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/db.php#L4-L14
[T07]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/scripts/init_db.php#L10-L59
[T08]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/scripts/seed_questions.php#L15-L65
[T09]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/helpers.php#L22-L32
[T10]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/config.php#L4-L15
[T11]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/DidacticPackService.php#L48-L77
[T12]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/DidacticPackService.php#L11-L44
[T13]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Models/Intake.php#L8-L26
[T14]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Controllers/ExamController.php#L18-L30
[T15]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Models/Question.php#L8-L40
[T16]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/ExamGeneratorService.php#L9-L38
[T17]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Models/Exam.php#L8-L28
[T18]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Models/Exam.php#L31-L69
[T19]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Models/Exam.php#L72-L78
[T20]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/AnalyticsService.php#L5-L13
[T21]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/ExportService.php#L9-L56
[T22]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Services/ExportService.php#L60-L117
[T23]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Views/layout.php#L1-L32
[T24]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Controllers/IntakeController.php#L12-L27
[T25]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Controllers/ExamController.php#L33-L79
[T26]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Views/exam_results.php#L1-L19
[T27]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/app/Controllers/PackController.php#L7-L15
[T28]: https://github.com/MarionForm/Tech2Teach-ExamSuite/blob/444a2f71491b755ab3f84dbaa3e4ebf5f2227805/portable/START.bat#L1-L8
[B01]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/src/BookmarkManager.php#L5-L198
[B02]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/src/DB.php#L9-L96
[B03]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/htdocs/index.php#L5-L68
[B04]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/composer.json#L1-L12
[B05]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/Dockerfile#L1-L11
[B06]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/LICENSE#L1-L21
[B07]: https://github.com/sebcode/b/blob/d0d240bd99d12a43eb9740033854baeea09bf797/conf/apache-extra.conf#L1-L15
[L01]: https://github.com/lgg-archive/tasks.php/blob/d01f9366b8e1f53ea63d26054346ad866f1ec57f/action.php#L49-L278
[L02]: https://github.com/lgg-archive/tasks.php/blob/d01f9366b8e1f53ea63d26054346ad866f1ec57f/core/functions/global.php#L9-L32
[L03]: https://github.com/lgg-archive/tasks.php/blob/d01f9366b8e1f53ea63d26054346ad866f1ec57f/core/data.php#L8-L28
[L04]: https://github.com/lgg-archive/tasks.php/blob/d01f9366b8e1f53ea63d26054346ad866f1ec57f/LICENSE#L1-L38
[D01]: https://github.com/lodev09/php-todo/blob/95be4451c8c46ad7f25a3baa773118200ab3224e/public/ajax/requests.php#L1-L52
[D02]: https://github.com/lodev09/php-todo/blob/95be4451c8c46ad7f25a3baa773118200ab3224e/db/todo.sql#L1-L8
[D03]: https://github.com/lodev09/php-todo/blob/95be4451c8c46ad7f25a3baa773118200ab3224e/composer.lock#L1
[A01]: https://github.com/azlux/Simple-URL-Shortener/blob/acba0eaf5b38edff285abec164e202a897ace07d/shorten.php#L4-L115
[A02]: https://github.com/azlux/Simple-URL-Shortener/blob/acba0eaf5b38edff285abec164e202a897ace07d/installation.php#L1-L38
[K01]: https://github.com/kanboard/kanboard/blob/c2f8e1c4360cbdd0c740b747d017034d97d8e053/LICENSE#L1-L18
[K02]: https://github.com/kiswa/TaskBoard/blob/857583e4bb508c7b449a8e45bb0747d22d88abdb/.travis.yml#L1-L16
[K03]: https://github.com/kiswa/TaskBoard/blob/857583e4bb508c7b449a8e45bb0747d22d88abdb/src/api/composer.lock#L1
[P01]: https://github.com/PrivateBin/PrivateBin/blob/d65bf02d7819a530c3c2a88f6f9947651fe5258d/LICENSE.md#L1-L31
[S01]: https://github.com/sen-ltd/short-url/blob/c2bead05235909e8e6d8052d9e5c8d133b95d968/composer.json#L1
[M01]: https://github.com/Mohamedajab/transaction-ledger-api/blob/9c152c26fd8bfc8c204e4b0d33c7616e42f6756c/src/TransferService.php#L10-L14
[E03]: https://registry-1.docker.io/v2/library/php/manifests/7.4.33-cli
[R01]: https://cerebralvalley.ai/e/meta-ai-developer-hackathon/waiver
[R02]: https://dev.meta.ai/events/global-hackathon
[R03]: https://wiki.php.net/rfc/stable_sorting
[R04]: https://www.php.net/manual/en/language.types.array.php

## Первые пять действий двух разработчиков 26 октября

1. **Вместе:** заморозить входной SHA и архивный hash, подтвердить scope генерации и прочитать текущие contest terms. Пометить OSS input и новый вклад LegacyShift в provenance.
2. **Разработчик A:** в существующем разрешённом worker проверить PHP7.4.33/extensions, lint и upstream startup; сохранить команды, exit codes и smoke outputs. При существенном repair переключить кандидата.
3. **Разработчик B:** независимо подготовить fixture snapshot и manifest для intake/grade/export, правила узкой normalization и закрытое хранение held-out. Содержимое закрытых данных не передавать migration agent.
4. **A и B:** выполнить PHP-vs-PHP repeatability и negative controls; отдельно решить generation gap. Если обязательное exact full-route coverage не достигнуто в setup timebox — проверить fallback sebcode/b.
5. **После gates:** зафиксировать unit contracts U1–U9 и начальные compiler/HTTP/DB/cost метрики. Только затем начинать benchmark integration и миграционный эксперимент по уже утверждённой архитектуре.

**CONDITIONAL GO**
