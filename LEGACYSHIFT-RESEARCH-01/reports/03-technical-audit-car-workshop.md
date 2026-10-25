# 03 — Технический аудит выбранного `car-workshop`

**Revision:** [`d272170d0aecbd28eb5b13e272d9dd11d545b66c`](https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c)  
**Audit type:** static, commit-pinned. **Runtime:** `NOT_RUN`.

## 1. Architecture и production scope

Приложение состоит из browser UI (`index.php` + `app.js`, `admin.php` + `admin.js`) и form-encoded → JSON API (`api.php`). `config.php` выбирает PDO driver, создаёт SQLite schema и seed data. Для benchmark рекомендуется backend/API scope; UI остаётся необязательной демонстрационной оболочкой.

| Файл | Production PHP LOC | Назначение |
|---|---:|---|
| `api.php` | 306 | dispatch, validation, availability, booking, list, update, cancel |
| `config.php` | 107 | env settings, PDO connection, schema, seed |
| `index.php` | 2 | две PHP date substitutions в booking template |
| `admin.php` | 1 | одна PHP date substitution в admin template |
| **Итого** | **416** | **4 файла; 413 LOC в двух substantive backend files** |

LOC получен lexer-based static подсчётом без tests/JS/HTML/comments/blank lines. [Метод](../tools/php_loc.py), [результат](../loc-results.json).

Backend не имеет `composer.json`, `vendor/` или framework. UI подключает Font Awesome через CDN, поэтому полностью offline UI не принимается как доказанный факт; API harness не зависит от этих assets.

## 2. Точная карта API

Все действия проходят через `api.php?action=…`; dispatcher подтверждён в [api.php, lines 9–28](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L9-L28).

| Action | Method | Поведение | DB side effects | Source |
|---|---|---|---|---|
| `get_slots` | GET по контракту | Для каждого механика считает booked/available/full на дату | Нет; читает обе таблицы | [52–89](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L52-L89) |
| `book` | POST | Валидирует клиента/авто/дату; проверяет mechanic, duplicate и capacity | `BEGIN IMMEDIATE`, INSERT appointment, COMMIT/ROLLBACK | [91–201](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L91-L201) |
| `get_appointments` | GET по контракту | JOIN + search/date/mechanic filters, сортировка date DESC/id DESC | Нет | [203–246](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L203-L246) |
| `update_appointment` | POST | Проверяет запись, дату, mechanic, duplicate и capacity с исключением текущей записи | Lock + UPDATE + COMMIT/ROLLBACK | [248–337](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L248-L337) |
| `cancel_appointment` | POST | Удаляет запись | DELETE; освобождает slot | [339–360](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L339-L360) |

Unknown action возвращает JSON `success:false`, а не routing exception. Production API не вызывает `http_response_code`; поэтому ожидаемый baseline для бизнес-ошибок — обычный HTTP 200, который нельзя «исправлять» при миграции без изменения контракта.

## 3. SQLite schema и initialization

[config.php, lines 79–103](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L79-L103) создаёт:

- `mechanics`: id, name, specialty, phone, `max_daily_slots` (default 4), created_at;
- `appointments`: id, client/vehicle fields, appointment_date, mechanic_id, status (default `approved`), created_at; объявлен FK `ON DELETE CASCADE`.

Seed добавляет пять механиков только когда `COUNT(mechanics)==0` ([lines 105–121](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L105-L121)).

Важные детали parity:

1. SQLite connection в этой ревизии **не включает** `PRAGMA foreign_keys=ON`; наличие FK в DDL не следует автоматически считать включённым enforcement.
2. `created_at` заполняется SQLite `CURRENT_TIMESTAMP`, тогда как бизнес-правила «today» используют PHP date и `APP_TIMEZONE`.
3. `ensureSeeded` перехватывает `Exception` без записи ошибки; baseline должен фиксировать фактически созданные пять строк, а не предполагать успех по отсутствии exception.
4. MySQL-файл `database.sql` начинается с `DROP TABLE IF EXISTS`; он не нужен и не должен использоваться для non-destructive SQLite fixture. [database.sql](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/database.sql#L1-L2).

## 4. Доменная и PHP-семантика

### 4.1 Даты и timezone

`isValidDate` сочетает regex и `checkdate`, поэтому невозможная календарная дата отклоняется ([api.php, 30–35](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L30-L35)). Default timezone — `Asia/Dhaka`, переопределяется через env ([config.php, 26–29](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L26-L29)).

Особый случай: `get_slots` при невалидной дате **молча подставляет today**, тогда как `book` и `update` возвращают validation error. Это разное поведение следует сохранить.

### 4.2 Availability и duplicate rules

- Capacity = `max(0, max_daily_slots - booked_count)`; `is_full` — boolean.
- Duplicate ищется по `phone OR car_license` на ту же дату и не зависит от выбранного механика.
- При update текущая запись исключается через `id != ?`; иначе повторное сохранение без изменений было бы ложным duplicate/capacity conflict.

### 4.3 Транзакции и ранние выходы

SQLite write-lock — `BEGIN IMMEDIATE` ([api.php, 43–50](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L43-L50)). После lock есть ранние `exit` для unknown mechanic, duplicate и full capacity без явного `rollBack`. Upstream комментарий рассчитывает на закрытие connection при завершении request. Новый long-running worker обязан воспроизвести наблюдаемый результат, но не должен оставлять lock; это требует отдельного lock-release test, а не слепого копирования `exit`.

### 4.4 JSON types и идентификаторы

`get_slots` явно приводит IDs/counts к `int` и формирует boolean `is_full`. `get_appointments` возвращает сырые PDO rows без casts. `book` помещает `lastInsertId()` в ответ также без cast. Фактические JSON types в PHP/PDO baseline нужно записать и сохранить; статический анализ не должен подменять их предположением.

### 4.5 Cancel — удаление, а не status transition

Cancel выполняет `DELETE` и не проверяет `rowCount`. Поэтому удаление несуществующего ID может вернуть `success:true`; это должен подтвердить baseline test. Колонка `status` не переводится в `cancelled`, что также прямо описано в [limitations](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L127-L140).

## 5. Tests и CI evidence

- [tests/api_test.php](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/tests/api_test.php) обращается к реальному HTTP API, изменяет fresh DB и содержит 34 static call sites; один call site в цикле выполняется трижды, давая 36 checks при включённом `curl`.
- [CI workflow](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/.github/workflows/ci.yml) описывает PHP 8.3 lint и test matrix SQLite/MySQL с `PHP_CLI_SERVER_WORKERS=8`.
- Это подтверждает **существование и устройство** проверок (`VERIFIED`), но не их текущий успех. Локальные lint/tests: `NOT_RUN`; статус upstream CI на pinned SHA не принимался как наш runtime результат.

## 6. PHP compatibility

Upstream требует PHP 8, CI использует 8.3. Read-only scan production files не обнаружил `match`, constructor promotion, `readonly`, `enum`, `never`, nullsafe operator или `str_contains`/`str_starts_with`; используемый procedural syntax, `??`, PDO и `checkdate` исторически доступны в PHP 7.4.

Вывод ограничен:

- «Нет очевидных PHP-8-only constructs» — `STATICALLY_SUPPORTED`.
- «Приложение работает на PHP 7.4» — `UNKNOWN`, runtime `NOT_RUN`.
- PHP 7.4 compatibility probe должен выполняться только в изолированном окружении; PHP 8.3 остаётся reference baseline.

## 7. Security и operational boundary

Upstream прямо указывает отсутствие admin login и CSRF protection. Поэтому:

- запуск только на loopback/private disposable environment;
- никаких реальных персональных данных;
- auth/CSRF improvements не входят в behavior-preserving migration;
- MySQL fallback не входит в v1.1: `DB_DRIVER=sqlite` фиксируется, чтобы silent fallback не маскировал конфигурацию.

## 8. License, provenance и hackathon boundary

Корневой [MIT LICENSE](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/LICENSE) проверен. Репозиторий содержит course/AI-use declaration; это provenance information, не отдельное лицензионное ограничение. Для submission нужно сохранить copyright/license notice, указать upstream URL/SHA и показать собственный вклад LegacyShift. Hackathon eligibility отдельно от лицензии юридически не гарантируется этим аудитом.
