# LEGACYSHIFT-RESEARCH-01 — Executive Recommendation

**Дата исследования:** 25 октября 2026  
**Режим:** read-only source audit; скачанный код не выполнялся.

## Рекомендуемый репозиторий

**`tanjilaafsarirubina/car-workshop`**  
**Зафиксированная ревизия:** `d272170d0aecbd28eb5b13e272d9dd11d545b66c`  
**Commit-pinned URL:** https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c

Это публичное MIT-licensed приложение записи клиентов автосервиса: SQLite, пять JSON API actions, две таблицы, проверка дат, ограничение «один клиент в день», ёмкость механика, переназначение и отмена записей, а также write-lock при создании и изменении бронирования.

## Почему выбран именно он

1. **Differential testing уже имеет полезный фундамент.** В ревизии есть HTTP test driver с 36 проверками при наличии `curl`: валидация, оба бизнес-ограничения, поиск, редактирование, отмена и параллельные бронирования. Существование тестов подтверждено по исходникам; их успешный запуск нами не выполнялся и не заявляется. [README](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L81-L96), [test driver](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/tests/api_test.php).
2. **Поведение наблюдаемо на двух уровнях:** JSON-ответы и побочные эффекты в SQLite. Для последовательных сценариев можно требовать точную эквивалентность, а для гонки — инварианты вместо недетерминированного списка победителей.
3. **Хорошая миграционная форма:** пять содержательных actions естественно раскладываются на восемь migration units, не раздувая benchmark искусственными файлами.
4. **Минимальная инфраструктура:** production backend не использует Composer или framework; SQLite выбран по умолчанию, а путь к базе и часовой пояс задаются через окружение. [config.php](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/config.php#L17-L29).
5. **Лицензия и происхождение прозрачны:** корневой MIT LICENSE проверен на pinned revision. [LICENSE](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/LICENSE).

## Сознательный компромисс

Числовой профиль не идеален:

| Метрика | Результат аудита | Целевой ориентир |
|---|---:|---:|
| Production PHP LOC | **416** | 1 000–1 400; допустимо 500–1 500 |
| PHP-файлы в scope | **4** (`api.php`, `config.php`, два template-файла с тремя PHP-строками суммарно) | 8–12 |
| Содержательные HTTP actions | **5** | 5–7 |
| Migration units | **8** | 6–10 |
| SQLite tables | **2** | 1–3 |
| Backend dependencies | **0 Composer/runtime packages** | минимум |

Мы **не** стали считать HTML, CSS, JavaScript или tests как production PHP LOC и не стали искусственно включать maintenance-файлы. Меньший объём принят осознанно: воспроизводимость, наличие HTTP-ориентированного набора проверок, точная бизнес-логика и безопасная инициализация важнее достижения красивого LOC-числа. Взвешенный результат — **8,825/10**, против **7,725/10** у ближайшего fallback; баллы являются экспертной оценкой (`INFERRED`), а не runtime benchmark.

## Блокирующие оговорки перед использованием

- Upstream заявляет **PHP 8**, а не PHP 7.4. Статический scan четырёх production-файлов не нашёл очевидных PHP-8-only конструкций, но это **не доказывает** совместимость с 7.4. [README](https://github.com/tanjilaafsarirubina/car-workshop/blob/d272170d0aecbd28eb5b13e272d9dd11d545b66c/README.md#L52-L60).
- Runtime, lint, upstream tests и differential tests в этом исследовании: **NOT_RUN**. В investigation environment не было PHP или уже авторизованной disposable isolation с PHP; никакой скачанный код на host не запускался.
- Нужно явно утвердить исключение по размеру: 416 LOC и четыре файла. Если минимум 500 production PHP LOC является жёстким gate, основной кандидат не проходит независимо от качества тестирования.
- Admin panel не имеет аутентификации. Benchmark должен оставаться локальным/изолированным; миграция не должна незаметно «исправлять» это поведение вне согласованного scope.

## Финальный статус

**CONDITIONAL GO** — использовать pinned `car-workshop` только после того, как команда 26 октября подтвердит size exception, воспроизведёт baseline в disposable isolation и примет решение о PHP 7.4 на основании фактического запуска. До этого момента репозиторий является рекомендацией по статическим доказательствам, а не проверенной runtime-конфигурацией.

## Первые пять действий команды — 26 октября 2026

1. **Зафиксировать scope и атрибуцию.** Сохранить commit SHA, MIT notice и список из четырёх production PHP-файлов в LegacyShift evidence pack; отдельно зафиксировать согласие на 416 LOC/4 файла. Не менять upstream и Casper checkout.
2. **Поднять два disposable baseline-окружения.** Developer A: PHP 8.3 с `pdo_sqlite` и `curl` для upstream-supported baseline; отдельно PHP 7.4 только для compatibility probe. Записать image digests, `php -v`, extensions и результаты lint/tests. Никаких запусков на host или против существующих данных.
3. **Снять воспроизводимый legacy baseline.** Developer B: через `DB_DRIVER=sqlite`, отдельный `SQLITE_FILE` и `APP_TIMEZONE=Asia/Dhaka` создать чистую базу; сохранить responses, status/headers и snapshots `mechanics`, `appointments`, `sqlite_sequence`. Не использовать MySQL `database.sql` для SQLite setup.
4. **Заморозить parity contract и восемь units.** Зафиксировать JSON types, порядок массивов, HTTP 200 у бизнес-ошибок, удаление при cancel, timezone/date rules и lock-release после ранних выходов. Public upstream tests использовать как smoke, а новые held-out vectors хранить отдельно от migration agent.
5. **Провести тонкий differential vertical slice.** Сначала `get_slots → book → duplicate/capacity rejection → cancel → get_appointments`, затем остальные units. Продолжать полную миграцию только после нуля необъяснённых расхождений и проверки, что отклонённая транзакция не оставляет SQLite lock.

Подробности: [сравнение](02-candidate-comparison.md), [технический аудит](03-technical-audit-car-workshop.md), [migration map](04-migration-units.md), [differential plan](05-differential-testing-plan.md), [setup](06-non-destructive-setup.md), [fallback](07-fallback.md), [evidence ledger](../evidence-ledger.md).
