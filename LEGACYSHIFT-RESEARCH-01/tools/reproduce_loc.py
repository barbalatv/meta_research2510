#!/usr/bin/env python3
"""Regenerate loc-results.json without executing candidate PHP code."""
from __future__ import annotations

import json
from pathlib import Path

import pygments

from php_loc import code_lines, iter_scope

ROOT = Path(__file__).resolve().parents[1]
CANDIDATES = ROOT / "candidates"
SCOPES = json.loads((ROOT / "tools" / "scopes.json").read_text(encoding="utf-8"))

ITEMS = [
    ("sari_runtime", "x1n-Q__Sari-Sari-Store-System"),
    ("sari_runtime_init", "x1n-Q__Sari-Sari-Store-System"),
    ("sari_maintenance", "x1n-Q__Sari-Sari-Store-System"),
    ("client_billing", "DEV-PHP-MAX__client-billing-manager"),
    ("car_workshop", "tanjilaafsarirubina__car-workshop"),
    ("solara", "SolaraCZ__php-native-rest-api"),
    ("transaction_ledger", "Mohamedajab__transaction-ledger-api"),
    ("helpdesk", "MarionForm__HelpdeskPortablePHP"),
    ("lavagest", "dmarck82__LavaGest"),
    ("javier_03mvc", "JavierQuinan__Listas"),
    ("joseph", "josephfranca__Projeto-Integrador-2-semestre"),
    ("coskun", "coskunsoysal__rest-api-sample"),
    ("grocery", "MarcoRuffati__grocery-broker"),
    ("office_tracker", "cdominik__office-tracker"),
    ("turkiye", "lupusoftco__turkiye-api"),
    ("vanilla", "VanillaSaaS__vanillasaas-booking-tracker"),
    ("task_tracker_pro", "subirkathuria__Task-Tracker-Pro"),
    ("mahnoor", "MahnoorPortfolio__Inventory_Management_System"),
    ("squirrel", "Squirrel1734__taskflow-php"),
    ("tasky", "Damianttje__Tasky"),
    ("chat", "PonrajkumarRajendran__php_chatapplication"),
    ("message_board", "mas886__Simple-Message-Board"),
    ("farrel", "FarrelAD__Basic-PHP-RESTful-API"),
    ("asilvafx", "asilvafx__php-sqlite-crud"),
    ("arrestdb", "alixaxel__ArrestDB"),
    ("yui0", "yui0__php-db-api"),
    ("simple_crud", "Falcowoski__API_REST_PHP_SQLITE"),
    ("nino", "NinoMiquelino__php-rest-api"),
    ("iasmin", "IasminRDS__api-produtos-php"),
    ("rricajos", "rricajos__php-task-manager"),
    ("bilet", "dogancanyildiz__bilet-satin-alma"),
]


def main() -> None:
    results = {
        "_method": {
            "definition": "Physical lines inside PHP blocks with a non-whitespace, non-comment, non-HTML token; PHP open/close tags excluded.",
            "lexer": f"Pygments {pygments.__version__} PhpLexer(startinline=False)",
            "candidate_code_executed": False,
        }
    }
    for scope_name, repo in ITEMS:
        root = CANDIDATES / repo
        rows = []
        for rel, path in sorted(iter_scope(root, SCOPES[scope_name])):
            count, _ = code_lines(path)
            rows.append({"file": rel, "loc": count})
        results[scope_name] = {
            "repo": repo,
            "root": str(root),
            "files": rows,
            "total": sum(row["loc"] for row in rows),
        }
        print(f"{scope_name:24} {results[scope_name]['total']:5d} LOC / {len(rows):2d} files")
    (ROOT / "loc-results.json").write_text(
        json.dumps(results, indent=2, ensure_ascii=False) + "\n", encoding="utf-8"
    )


if __name__ == "__main__":
    main()
