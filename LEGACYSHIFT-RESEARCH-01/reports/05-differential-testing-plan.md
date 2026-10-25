# 05 — Differential Testing Plan

**Состояние на 25 октября:** план составлен по исходникам; выполнение — `NOT_RUN`.

## 1. Цель и границы

Для одного и того же начального SQLite state каждый request выполняется сначала против pinned legacy, затем против новой реализации. Сравниваются не только ответы, но и побочные эффекты. Цель — **behavioral parity**, а не улучшение API, безопасности или схемы.

В v1.1 входят SQLite, пять API actions и восемь migration units. Не входят MySQL matrix, публичный deployment, добавление auth/CSRF и redesign frontend.

## 2. Два независимых контура

### Legacy reference

- exact commit `d272170d0aecbd28eb5b13e272d9dd11d545b66c`;
- upstream-supported PHP 8.3 baseline;
- `DB_DRIVER=sqlite`, отдельный `SQLITE_FILE`, `APP_TIMEZONE=Asia/Dhaka`;
- `pdo_sqlite`; `curl` и несколько workers для concurrency;
- отдельный disposable container/worktree, никаких существующих DB.

### Candidate implementation

- тот же request corpus, initial fixture и timezone;
- отдельный процесс и отдельная DB;
- никакого чтения legacy responses во время генерации кода агентом;
- held-out runner остаётся детерминированным компонентом LegacyShift, а не частью migration agent.

PHP 7.4 probe — отдельный compatibility experiment; он не должен молча заменять PHP 8.3 reference, пока команда не примет его результат.

## 3. Fixture protocol

1. Остановить все writers; создать новый пустой DB path для каждого сценария.
2. Первый `get_slots` инициализирует schema/seed; проверить ровно две application tables и пять mechanics.
3. Сохранить pre-state: `mechanics`, `appointments`, `sqlite_sequence`, schema SQL и SHA256 manifest.
4. Выполнить один request или обозначенную последовательность.
5. Сохранить status, Content-Type, raw body, parsed JSON и post-state.
6. Повторить с теми же logical inputs на candidate; не копировать legacy DB в candidate.
7. Перед snapshot остановить/drain writers, чтобы не захватить частичную транзакцию.

Даты в requests вычисляются относительно `Asia/Dhaka`, а не зашиваются на 26 октября. Если прогон пересекает полночь в этом timezone, весь сценарий пересоздаётся, чтобы избежать смешения двух «today».

## 4. Public smoke и held-out suites

### A. Upstream public smoke

Запустить существующий `tests/api_test.php` как baseline smoke. Он покрывает 36 checks при наличии `curl`; эти проверки публичны и **не считаются held-out**.

### B. Sequential differential suite

| Группа | Что сравнивать |
|---|---|
| Initialization | Пять mechanics, порядок id, 20 свободных slots, schema/defaults |
| Routing/method quirks | Unknown action; GET вместо POST; отсутствие action; HTTP 200 + `success:false` там, где это делает legacy |
| Validation | Порядок объединённых messages, пустые/граничные поля, phone/engine regex, invalid mechanic |
| Calendar | Невалидный формат, невозможная дата, прошлое, leap-day; различие fallback в `get_slots` и ошибки в write actions |
| Successful booking | Точный JSON, фактический type `appointment_id`, INSERT approved, последующий slots/list result |
| Duplicate rules | Одинаковый phone, одинаковая plate, другой mechanic, другой день; проверка `OR`, а не `AND` |
| Capacity | Четыре допустимые записи, отказ следующей, независимость mechanics, восстановление slot после cancel |
| Search/list | Каждый LIKE-filter, date/mechanic combinations, пустой result, date DESC/id DESC, raw PDO JSON types |
| Update | Self-update, not-found, unknown mechanic, move to full mechanic, duplicate on target day, успешное изменение только date/mechanic |
| Cancel | Success, invalid id, несуществующий id, повторный cancel, AUTOINCREMENT после удаления |
| Lock release | После duplicate/full/not-found/invalid target немедленно выполнить валидную запись и исключить зависший SQLite lock |

Конкретные held-out input vectors в этот отчёт не включены. Они должны храниться в отдельном private fixture pack с version/hash и не передаваться migration agent.

### C. Concurrency / metamorphic suite

Для параллельных запросов нельзя требовать совпадения списка победителей: scheduling закономерно недетерминирован. Для каждой итерации проверяются инварианты:

- ровно четыре успешных booking для mechanic с четырьмя slots;
- остальные requests получают `MECHANIC_FULL` либо документированный infrastructure error, который является отдельным failure, а не silently accepted parity;
- в DB ровно четыре новых appointments у этого mechanic/date;
- нет duplicate phone/plate и нет отрицательных available slots;
- другие mechanics/dates не изменились;
- после завершения всех requests возможна следующая валидная операция без lock timeout.

Минимум — повторные итерации на fresh DB с несколькими workers; точное число фиксируется в run manifest до старта. Результат concurrency не смешивать с sequential exact-diff score.

## 5. Normalization contract

Нормализация утверждается **до** сравнения и версионируется вместе с corpus.

| Данные | Правило |
|---|---|
| JSON object key order | Игнорировать; сравнивать parsed objects |
| JSON array order | **Сохранять**; mechanics и appointments order являются контрактом |
| JSON scalar types | **Сохранять**; int/string/bool различия считаются mismatch |
| `created_at` в DB/JSON | Заменять на typed token только после проверки формата и монотонности внутри сценария; не удалять поле |
| `appointment_id` и foreign keys | По умолчанию exact match на fresh fixture; не нормализовать произвольно |
| Request dates | Одинаковые relative dates для обоих прогонов; formatted date в message сравнивать точно |
| HTTP `Date`, `Server`, request logs | Infrastructure metadata, не business response; исключить по списку |
| HTTP status и Content-Type | Сравнивать, включая HTTP 200 у бизнес-ошибок |
| SQLite internal freelist/page bytes | Не сравнивать binary DB; сравнивать logical rows и `sqlite_sequence` |
| Concurrency winner identities | Не сравнивать; использовать инварианты выше |

Новое правило нормализации после обнаруженного mismatch требует отдельного review; нельзя «чинить» failing diff ослаблением comparator.

## 6. Side-effect comparison

После каждого write request logical snapshot включает:

- все колонки `mechanics` и `appointments`;
- `sqlite_sequence` для обеих таблиц;
- отсутствие новых tables/triggers/indexes, если они не входят в contract;
- результат независимого read через `get_slots` и `get_appointments`.

Для failed write ожидается отсутствие partial appointment. Для successful update ожидается изменение только `appointment_date` и `mechanic_id`; остальные поля, включая `status` и `created_at`, сохраняются.

## 7. Mismatch taxonomy

1. **Response contract:** status, headers, body, scalar types, ordering.
2. **State mismatch:** missing/extra row, неверное поле, sequence divergence.
3. **Temporal mismatch:** timezone, today, timestamp format/order.
4. **Transaction mismatch:** partial write, lock не снят, capacity нарушена.
5. **Fixture mismatch:** разные initial rows/dates/config; сначала исправить harness, не candidate.
6. **Intentional upstream quirk:** задокументированное поведение сохранено; не классифицировать как дефект миграции.

## 8. Acceptance gates

- 100% sequential corpus выполнен на обеих системах; пропуски не считаются успехом.
- Ноль необъяснённых response/state mismatches.
- Все failed writes не оставляют partial state или lock.
- Concurrency invariants выполняются во всех запланированных итерациях; infrastructure failures отчётливо видны.
- Public smoke и private held-out results хранятся раздельно.
- Evidence pack содержит commit, environment/image digests, fixture hash, normalization version, raw artifacts и сводку; словесного «работает» недостаточно.

## 9. Артефакты для двух разработчиков

Developer A отвечает за baseline runner и environment manifest; Developer B — за corpus, comparator и DB snapshots. Оба reviewing первых расхождений. Migration agent получает production source и public contract, но **не** private held-out vectors и не результаты candidate до завершения независимого прогона.
