# 06 — Non-destructive Setup Feasibility

**Оценка:** `STATICALLY_SUPPORTED` — конфигурация позволяет запустить приложение на новой SQLite DB без изменения source. Фактический setup: `NOT_RUN`.

## Почему setup выглядит безопасным

- SQLite выбран по умолчанию: [`config.php:17`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L17).
- Путь к DB читается из `SQLITE_FILE`: [`config.php:24`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L24).
- Schema создаётся через `CREATE TABLE IF NOT EXISTS`; seed выполняется только при пустой `mechanics`: [`config.php:79–124`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L79-L124).
- Backend не требует Composer install или внешнего сервиса.
- Tests предназначены для fresh DB и изменяют appointments, поэтому isolated path является обязательным, а не optional convenience.

## Будущий runbook для авторизованной disposable isolation

> Эти команды являются инструкцией для команды 26 октября, **не отчётом о выполненных действиях**. Не запускать их на host с пользовательскими проектами.

### 1. Отдельный source checkout

```bash
git clone https://github.com/tanjilaafsarirubina/car-workshop.git legacyshift-car-workshop
cd legacyshift-car-workshop
git checkout --detach d272170d0aecbd28eb5b13e272d9dd11d545b66c
git rev-parse HEAD
test ! -e config.local.php
```

`config.local.php` отсутствует в pinned tree и не следует создавать: он загружается до env defaults и может изменить конфигурацию.

### 2. Новый DB path вне source tree

```bash
RUN_DIR="$(mktemp -d /tmp/legacyshift-car-XXXXXX)"
export DB_DRIVER=sqlite
export SQLITE_FILE="$RUN_DIR/workshop.sqlite"
export APP_TIMEZONE=Asia/Dhaka
test ! -e "$SQLITE_FILE"
printf 'run_dir=%s\ndb=%s\n' "$RUN_DIR" "$SQLITE_FILE"
```

`test ! -e` — deliberate guard: baseline не должен начать работу с существующей базой. `RUN_DIR` и image/container digest записываются в evidence manifest.

### 3. PHP 8.3 reference run

В уже авторизованном container с PHP 8.3, `pdo_sqlite` и `curl`:

```bash
php -v
php -m | grep -Ei 'pdo|sqlite|curl'
PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8000
```

Запуск должен быть доступен только внутри private isolation. Не публиковать admin/API через live preview: upstream не имеет admin authentication.

### 4. Non-destructive smoke

```bash
curl --fail-with-body \
  'http://127.0.0.1:8000/api.php?action=get_slots'
```

Ожидаемая инициализация проверяется через response и отдельный read-only DB inspection: две application tables, пять mechanics, 20 available slots. Сам факт HTTP 200 без проверки rows недостаточен.

### 5. Upstream tests на отдельной fresh DB

Остановить server, создать новый `RUN_DIR`/`SQLITE_FILE`, перезапустить и только затем выполнить:

```bash
php tests/api_test.php http://127.0.0.1:8000
```

Нельзя повторно использовать DB от предыдущего прогона: tests создают реальные appointments и рассчитаны на fresh state.

### 6. Snapshot и cleanup

Остановить/drain server перед копированием DB или логическим export. Cleanup ограничивается созданным `RUN_DIR`; не выполнять `rm` по шаблону в repository root и не трогать Casper или другие checkouts.

## Чего не делать

1. **Не импортировать `database.sql`.** Это MySQL schema, начинающийся с `DROP TABLE IF EXISTS`; для SQLite он не нужен.
2. **Не направлять `SQLITE_FILE` на существующие данные.** Даже `CREATE IF NOT EXISTS` не делает полный test suite non-destructive, потому что tests пишут и удаляют appointments.
3. **Не запускать с host PHP.** В investigation environment PHP отсутствовал; скачанный код не выполнялся. Любой запуск — только в заранее авторизованной disposable isolation.
4. **Не включать публичный сетевой доступ.** Admin и POST actions не имеют полноценной auth/CSRF защиты.
5. **Не объявлять PHP 7.4 совместимость заранее.** Upstream требует PHP 8; 7.4 probe выполняется отдельно и не меняет reference environment без review.

## PHP 7.4 compatibility probe

Если заказчик настаивает на PHP 7.4:

- использовать отдельный изолированный image и новый DB path;
- сначала lint всех production PHP files, затем public smoke, затем differential corpus;
- зафиксировать не только fatal errors, но и PDO JSON types, `lastInsertId`, `checkdate`, `BEGIN IMMEDIATE` и поведение `exit` после lock;
- не использовать EOL runtime с доступом к реальным данным или публичной сетью;
- при любом несоответствии пометить 7.4 как failed probe, сохранить PHP 8.3 reference и не подменять результат предположением из syntax scan.

## Оценка рисков

| Риск | Митигaция | Остаточный статус |
|---|---|---|
| Существующая DB повреждена/изменена | Новый `SQLITE_FILE`, `test ! -e`, отдельный run per suite | Контролируется статически |
| MySQL DDL случайно очищает таблицы | Не использовать `database.sql`; `DB_DRIVER=sqlite` | Исключается runbook |
| Concurrency не действительно параллельна | Linux container, `PHP_CLI_SERVER_WORKERS=8`, `curl` | Требует runtime check |
| PHP 7.4 отличается | Отдельный probe; reference остаётся 8.3 | `UNKNOWN` |
| Dependencies отсутствуют | Проверить `php -m` до corpus; не устанавливать на host | `NOT_RUN` |
| Source/upstream изменён | Detached SHA, read-only source где возможно, `git diff --exit-code` после прогона | Планируемый gate |

**Вывод:** non-destructive setup технически правдоподобен и не требует патча upstream. Его нельзя назвать проверенным до первого isolated run, но риск сводится к стандартной discipline fresh DB + fixed env + evidence manifest.
