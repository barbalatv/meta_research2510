# 07 — Fallback: Sari-Sari Store Inventory & Sales System

## Точный fallback

**`x1n-Q/Sari-Sari-Store-System`**  
**Revision:** `985a5183db7c16f675d397e23c7fa29e4605bfa4`  
**Pinned URL:** https://github.com/x1n-Q/Sari-Sari-Store-System/tree/985a5183db7c16f675d397e23c7fa29e4605bfa4  
**License:** [MIT](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/LICENSE)

## Когда переключаться

Fallback рассматривается только в двух случаях:

1. команда не принимает 416 production PHP LOC / 4 файла у primary и требует объём ближе к исходному диапазону;
2. для demo нужны денежные вычисления, inventory, sales, reports и exports, а не только booking workflow.

Переключение должно произойти после isolated baseline probe, а не из-за одного красивого README. Если единственная проблема primary — неподтверждённый PHP 7.4, автоматический переход на Sari-Sari проблему не решает: upstream Sari-Sari также заявляет PHP 8.0+, а его 7.4 runtime не проверен.

## Почему это сильный запасной вариант

| Свойство | Static audit |
|---|---|
| Production scope | **955 LOC / 6 PHP files** с canonical initializer; **822 LOC / 5 files** request-serving runtime |
| API | 9 method handlers: products GET/POST/PUT/DELETE, sales GET/POST/DELETE, reports GET, export GET |
| Schema | `products`, `sales`, `sales_items`, `invoice_sequence` — 4 таблицы |
| Dependencies | Backend без Composer; PDO SQLite/MySQL branches; frontend использует CDN |
| Домен | Stock decrement/restoration, sale transaction, totals/cost/profit, invoice sequence, receipt number, daily/monthly aggregates |
| License/provenance | Root MIT; tracked DB files не обнаружены |
| PHP | README требует 8.0+; scan не нашёл очевидных PHP-8-only constructs; 7.4 `UNKNOWN` |
| Tests | First-party test suite в pinned tree не найдена; runtime `NOT_RUN` |

Key sources: [products API](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/products.php), [sales API](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/sales.php), [reports](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/api/reports.php), [schema/seed initializer](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/init-database.php).

## Девять migration units fallback

1. PDO configuration и SQLite connection.
2. Schema + canonical sample products.
3. Products list/detail/create/update/delete.
4. Sales list/detail с nested items.
5. Invoice sequence и receipt generation.
6. Sale totals + transaction + stock decrement.
7. Sale deletion + stock restoration + cascade behavior.
8. Summary/daily/monthly reports и SQL aggregation types.
9. Excel-like HTML и printable export, включая `number_format`.

Это даёт более широкий computational benchmark, чем primary, но требует больше normalization и самостоятельного test authoring.

## Риски, которые нужно принять явно

### 1. Destructive initializer

[`init-database.php:26–30`](https://github.com/x1n-Q/Sari-Sari-Store-System/blob/985a5183db7c16f675d397e23c7fa29e4605bfa4/app/init-database.php#L26-L30) удаляет существующую DB через `unlink()`. `DB_FILE` жёстко задан в `app/config.php`, а не через env.

Поэтому fallback setup допустим только в новой writable копии внутри disposable isolation. Нельзя запускать initializer в существующем проекте или рядом с реальной `app/data/sari_sari_store.db`.

### 2. Два разных seed-источника

`app/init-database.php` и `app/database-sqlite.sql` содержат разные начальные stock values. Canonical fixture должен быть создан тем способом, который README называет для manual setup — `php init-database.php`; полученные logical rows фиксируются в baseline manifest. Нельзя смешивать SQL seed и initializer в разных прогонах.

### 3. Недетерминированные и числовые поля

- receipt использует `mt_rand` и текущую дату;
- sale date/time и export filename зависят от clock;
- money хранится как `REAL`, а totals вычисляются из client-supplied `subtotal`/`cost_subtotal`;
- invoice sequence изменяется до transaction sale insert;
- stock не имеет явного non-negative check.

Это полезные migration hazards, но comparator должен заранее определить, что является контрактом, а что volatile. Нельзя незаметно заменить REAL на integer cents, добавить stock validation или сделать numbering transactional и назвать результат эквивалентным.

### 4. Отсутствие готового oracle

В отличие от primary, нужно сначала создать собственный public smoke и private held-out pack. Это добавляет работу двум разработчикам, но даёт богатые DB side effects для differential testing.

## Timeboxed fallback procedure — 26 октября

1. Зафиксировать SHA и MIT notice; создать новую writable scratch-копию.
2. Запустить initializer только там; сохранить logical DB snapshot и подтвердить четыре таблицы/десять products.
3. Проверить products CRUD → sale create → stock change → sale delete → stock restoration → reports как vertical slice.
4. Записать фактические JSON types, numbering, timestamps и float formatting до передачи source migration agent.
5. Если baseline не воспроизводится в отведённом окне, не расширять поиск бесконечно: вернуться к primary при принятом size exception либо зафиксировать инфраструктурный blocker с `NOT_RUN` evidence.
