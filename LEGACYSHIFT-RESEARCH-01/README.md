# LEGACYSHIFT-RESEARCH-01

Evidence-based выбор публичного PHP-репозитория для LegacyShift v1.1. Исследование выполнено 25 октября 2026 в read-only режиме; скачанный код не выполнялся.

## Рекомендация

**Primary:** [`tanjilaafsarirubina/car-workshop@d272170d0aecbd28eb5b13e272d9dd11d545b66c`](https://github.com/tanjilaafsarirubina/car-workshop/tree/d272170d0aecbd28eb5b13e272d9dd11d545b66c)  
**Fallback:** [`x1n-Q/Sari-Sari-Store-System@985a5183db7c16f675d397e23c7fa29e4605bfa4`](https://github.com/x1n-Q/Sari-Sari-Store-System/tree/985a5183db7c16f675d397e23c7fa29e4605bfa4)

Окончательное решение, условия и первые пять действий команды: **[01 — Executive Recommendation](reports/01-executive-recommendation.md)**.

## Семь отчётов

1. [Executive Recommendation](reports/01-executive-recommendation.md)
2. [Candidate Comparison + weighted scoring](reports/02-candidate-comparison.md)
3. [Technical Audit выбранного репозитория](reports/03-technical-audit-car-workshop.md)
4. [Карта восьми Migration Units](reports/04-migration-units.md)
5. [Differential Testing Plan](reports/05-differential-testing-plan.md)
6. [Non-destructive Setup Feasibility](reports/06-non-destructive-setup.md)
7. [Fallback](reports/07-fallback.md)

## Evidence и воспроизводимые данные

- [Evidence ledger с VERIFIED / STATICALLY_SUPPORTED / INFERRED / UNKNOWN / NOT_RUN](evidence-ledger.md)
- [30-candidate registry](candidate-registry.csv)
- [Weighted scoring](scoring.csv)
- [LOC results](loc-results.json)
- [LOC counter](tools/php_loc.py)
- [LOC scopes](tools/scopes.json)
- [One-command LOC reproduction](tools/reproduce_loc.py)

## Главный результат аудита

Primary имеет 416 production PHP LOC, 4 PHP-файла, 5 JSON actions, 2 SQLite tables, 8 migration units, MIT license и существующий HTTP test driver. Он выбран не за числовое сходство, а за oracle quality, stateful booking rules, concurrency coverage и non-destructive fresh-SQLite configuration. PHP 7.4 и любой runtime остаются непроверенными до isolated probe 26 октября.
