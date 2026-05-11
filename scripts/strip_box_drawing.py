"""
One-off cleanup: strip Unicode box-drawing decoration from source comments.

- Collapses multi-line Blade banners {{-- ===\n TITLE \n=== --}} to {{-- TITLE --}}
- Strips runs of [─━═] from any other line
- Drops lines that became empty comment shells after stripping
"""

from __future__ import annotations

import argparse
import pathlib
import re
import sys

BOX_RE = re.compile(r"[─━═]+")
BLADE_BANNER_RE = re.compile(
    r"\{\{--[ \t]*[─━═]+[ \t]*\n"
    r"(?P<body>(?:(?!\{\{--|--\}\}).)*?)"
    r"\n[ \t]*[─━═]+[ \t]*--\}\}",
    re.DOTALL,
)

EMPTY_COMMENT_SHELLS = {
    "//",
    "/*",
    "*/",
    "/* */",
    "#",
    "*",
    "{{--",
    "--}}",
    "{{-- --}}",
    "{{----}}",
    "<!--",
    "-->",
    "<!-- -->",
}


def clean_text(text: str) -> str:
    text = BLADE_BANNER_RE.sub(
        lambda m: "{{-- " + " ".join(m.group("body").split()) + " --}}",
        text,
    )

    out_lines: list[str] = []
    for line in text.splitlines():
        if not BOX_RE.search(line):
            out_lines.append(line)
            continue

        leading = len(line) - len(line.lstrip(" \t"))
        prefix = line[:leading]
        body = BOX_RE.sub("", line[leading:])
        body = re.sub(r" {2,}", " ", body).rstrip()

        if body.strip() in EMPTY_COMMENT_SHELLS or body.strip() == "":
            continue

        out_lines.append(prefix + body)

    result = "\n".join(out_lines)
    if text.endswith("\n") and not result.endswith("\n"):
        result += "\n"
    return result


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("paths", nargs="+")
    ap.add_argument("--write", action="store_true", help="apply changes in place")
    args = ap.parse_args()

    changed = 0
    for raw in args.paths:
        p = pathlib.Path(raw)
        if not p.is_file():
            continue
        original = p.read_text(encoding="utf-8")
        cleaned = clean_text(original)
        if cleaned == original:
            continue
        changed += 1
        if args.write:
            p.write_text(cleaned, encoding="utf-8")
            print(f"cleaned: {p}")
        else:
            print(f"would clean: {p}")

    if not args.write:
        print(f"\n{changed} file(s) would be modified. Re-run with --write to apply.")
    else:
        print(f"\n{changed} file(s) modified.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
