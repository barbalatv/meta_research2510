# 04 — Карта из восьми migration units

Это **план декомпозиции**, не начало миграции и не изменение замороженной архитектуры LegacyShift. Границы соответствуют наблюдаемым контрактам, а не желаемой новой структуре классов.

| ID | Unit и source | Вход / выход | Побочные эффекты и parity contract | Сложность |
|---|---|---|---|---|
| **MU-01** | **Environment + PDO connection** — [`config.php:9–77`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L9-L77) | `DB_DRIVER`, `SQLITE_FILE`, `APP_TIMEZONE`, optional local config → shared PDO | SQLite по умолчанию; ERRMODE_EXCEPTION и FETCH_ASSOC; static connection в request; MySQL fallback исключён из benchmark. Не добавлять FK pragma или другие «улучшения» без отдельного контракта. | Низкая |
| **MU-02** | **Schema + seed** — [`config.php:79–124`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L79-L124) | Пустая/существующая SQLite DB → две таблицы и пять mechanics | `CREATE TABLE IF NOT EXISTS`; seed только при пустой mechanics; IDs 1–5 и max slots 4; повторная инициализация не должна дублировать или очищать данные. | Низкая |
| **MU-03** | **Date/time semantics** — [`api.php:30–35`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L30-L35) + [`config.php:26–29`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L26-L29) | Date string и configured timezone → boolean/today | Regex + `checkdate`, leap years, string comparison с today; `get_slots` fallback к today отделён от строгой validation в write actions. | Низкая |
| **MU-04** | **Availability projection** — [`api.php:52–89`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L52-L89) | `date` → JSON с пятью mechanics | Нет записи. Порядок по mechanic id; исключить `status='cancelled'` из count; `available=max(0,max-booked)`; точные int/bool types и invalid-date fallback. | Средняя |
| **MU-05** | **Create booking + lock** — [`api.php:43–50`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L43-L50), [`91–201`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L91-L201) | Form fields + mechanic/date → success или stable error JSON | `BEGIN IMMEDIATE`; duplicate по phone/plate; capacity; INSERT approved; `lastInsertId` без cast; COMMIT только после успешного INSERT. Ошибки после lock не должны оставлять lock в новой execution model. | **Высокая** |
| **MU-06** | **Appointment query/search** — [`api.php:203–246`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L203-L246) | Optional search/date/mechanic → JSON array | Нет записи. Четыре LIKE-поля, JOIN mechanic name/specialty, ORDER BY date DESC/id DESC, сырые PDO types; отсутствие date validation сохранить. | Средняя |
| **MU-07** | **Reschedule/reassign + lock** — [`api.php:248–337`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L248-L337) | `id`, new date, mechanic → success/error | Lock; not-found и invalid-target behavior; duplicate/capacity исключают текущий id; UPDATE только двух полей; проверить self-update и lock-release на каждом early exit. | **Высокая** |
| **MU-08** | **Cancel/delete** — [`api.php:339–360`](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/api.php#L339-L360) | `id` → success/error JSON | DELETE row, а не status update; slot освобождается; нет `rowCount` check; AUTOINCREMENT sequence не должен незаметно меняться новой реализацией. | Низкая |

## Dependency graph

```text
MU-01 ──> MU-02 ──> MU-04
   │         ├────> MU-06
   │         ├────> MU-08
   │         └────> MU-05 ──> MU-07
   └──────────────> MU-03 ──> MU-04/05/07

Shared lock primitive: MU-05 и MU-07.
Read model MU-04 служит внешним oracle для MU-05/07/08, но не подменяет DB snapshots.
```

## Рекомендуемый порядок миграции

1. **Foundation:** MU-01, MU-02, MU-03.
2. **Read-only vertical slices:** MU-04 и MU-06 — быстро дают observable baseline.
3. **Simple write:** MU-08 — проверяет DB snapshots и slot oracle.
4. **Transactional writes:** MU-05, затем MU-07.
5. **Concurrency gate:** отдельный прогон MU-05/MU-07 после завершения sequential parity.

Такой порядок не требует менять unit boundaries и позволяет локализовать расхождение до того, как транзакционные ошибки будут masked последующими запросами.

## Acceptance для каждого unit

- совпадают HTTP status, Content-Type, parsed JSON, JSON types и порядок arrays;
- совпадают application tables после каждого request, с отдельным контролем `sqlite_sequence`;
- все разрешённые нормализации ограничены заранее объявленными volatile fields;
- для write units проверяется следующий read через MU-04/MU-06;
- для MU-05/MU-07 дополнительно проверяются early-exit lock release и concurrency invariants;
- любое преднамеренное исправление upstream-дефекта оформляется как отдельный новый контракт, а не как эквивалентная миграция.
