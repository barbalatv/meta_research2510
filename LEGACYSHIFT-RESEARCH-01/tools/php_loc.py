#!/usr/bin/env python3
"""Static PHP production-LOC counter for LEGACYSHIFT-RESEARCH-01.

Definition: a production PHP LOC is a physical line inside a PHP block that
contains at least one lexical token other than whitespace, a comment, HTML
outside PHP, or a PHP open/close tag. No candidate code is executed. Pygments
is used only as a lexer.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Iterable

from pygments import lex
from pygments.lexers import PhpLexer
from pygments.token import Comment, Other, Text, Whitespace


def code_lines(path: Path) -> tuple[int, list[int]]:
    text = path.read_text(encoding="utf-8-sig", errors="replace")
    marked: set[int] = set()
    line = 1
    for token_type, value in lex(text, PhpLexer(startinline=False)):
        excluded = (
            token_type in Comment
            or token_type in Text
            or token_type in Other
            or token_type in Whitespace
        )
        parts = value.split("\n")
        for index, part in enumerate(parts):
            if not excluded and part.strip():
                marked.add(line)
            if index < len(parts) - 1:
                line += 1
    return len(marked), sorted(marked)


def iter_scope(root: Path, scope: dict) -> Iterable[tuple[str, Path]]:
    if "files" in scope:
        for rel in scope["files"]:
            yield rel, root / rel
    else:
        include = scope.get("include", ["**/*.php"])
        exclude = set(scope.get("exclude", []))
        seen: set[str] = set()
        for pattern in include:
            for path in root.glob(pattern):
                if not path.is_file():
                    continue
                rel = path.relative_to(root).as_posix()
                if rel in exclude or rel in seen:
                    continue
                if any(part in {".git", "vendor", "node_modules", "tests", "test"} for part in path.parts):
                    continue
                seen.add(rel)
                yield rel, path


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("root", type=Path)
    parser.add_argument("scope", type=Path, help="JSON scope file or JSON object string")
    parser.add_argument("--json", action="store_true")
    args = parser.parse_args()
    try:
        scope = json.loads(args.scope.read_text(encoding="utf-8"))
    except (OSError, UnicodeDecodeError):
        scope = json.loads(str(args.scope))

    rows = []
    for rel, path in sorted(iter_scope(args.root, scope)):
        if not path.exists():
            raise FileNotFoundError(path)
        count, _ = code_lines(path)
        rows.append({"file": rel, "loc": count})
    result = {"root": str(args.root), "files": rows, "total": sum(r["loc"] for r in rows)}
    if args.json:
        print(json.dumps(result, indent=2, ensure_ascii=False))
    else:
        for row in rows:
            print(f"{row['loc']:5d}  {row['file']}")
        print(f"{result['total']:5d}  TOTAL ({len(rows)} files)")


if __name__ == "__main__":
    main()
