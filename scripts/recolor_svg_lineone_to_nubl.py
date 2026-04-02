"""
One-off: map Lineone purple/indigo/magenta accents to NUBL gold scale in
public/images/**/*.svg (recursive).
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent / "public" / "images"

REPLACEMENTS = [
    ("#5F5AF6", "#f0aa1f"),
    ("#5f5af6", "#f0aa1f"),
    ("#4F46E5", "#f0aa1f"),
    ("#4f46e5", "#f0aa1f"),
    ("#4338CA", "#c9940b"),
    ("#4338ca", "#c9940b"),
    ("#3730A3", "#a37a08"),
    ("#3730a3", "#a37a08"),
    ("#6366F1", "#f5c02e"),
    ("#6366f1", "#f5c02e"),
    ("#818CF8", "#fde68a"),
    ("#818cf8", "#fde68a"),
    ("#A5B4FC", "#fcd34d"),
    ("#a5b4fc", "#fcd34d"),
    ("#C4B5FD", "#fef3c7"),
    ("#c4b5fd", "#fef3c7"),
    ("#DDD6FE", "#fef3c7"),
    ("#ddd6fe", "#fef3c7"),
    ("#EDE9FE", "#fffbeb"),
    ("#ede9fe", "#fffbeb"),
    ("#EEF2FF", "#fffbeb"),
    ("#eef2ff", "#fffbeb"),
    ("#9495DC", "#fcd34d"),
    ("#9495dc", "#fcd34d"),
    ("#8B5CF6", "#f0aa1f"),
    ("#8b5cf6", "#f0aa1f"),
    ("#7C3AED", "#f0aa1f"),
    ("#7c3aed", "#f0aa1f"),
    ("#6D28D9", "#c9940b"),
    ("#6d28d9", "#c9940b"),
    ("#667EEA", "#f0aa1f"),
    ("#667eea", "#f0aa1f"),
    ("#764BA2", "#a37a08"),
    ("#764ba2", "#a37a08"),
    ("#E1E1F7", "#fef3c7"),
    ("#e1e1f7", "#fef3c7"),
    ("#BD0090", "#c9940b"),
    ("#bd0090", "#c9940b"),
    ("#E000AB", "#a37a08"),
    ("#e000ab", "#a37a08"),
    ("#B8008C", "#c9940b"),
    ("#b8008c", "#c9940b"),
    ("#FF57D8", "#f5c02e"),
    ("#ff57d8", "#f5c02e"),
    ("#F000B9", "#f0aa1f"),
    ("#f000b9", "#f0aa1f"),
    ("#576E9E", "#c9940b"),
    ("#576e9e", "#c9940b"),
    ("#4C48C5", "#f0aa1f"),
    ("#4c48c5", "#f0aa1f"),
    ("#6F7F9F", "#c9940b"),
    ("#6f7f9f", "#c9940b"),
    ("#6C566C", "#a37a08"),
    ("#6c566c", "#a37a08"),
    ("#9B82F2", "#f0aa1f"),
    ("#9b82f2", "#f0aa1f"),
    # Tailwind violet / fuchsia / pink (often used in SVG exports)
    ("#9333EA", "#f0aa1f"),
    ("#9333ea", "#f0aa1f"),
    ("#A855F7", "#f0aa1f"),
    ("#a855f7", "#f0aa1f"),
    ("#D946EF", "#f0aa1f"),
    ("#d946ef", "#f0aa1f"),
    ("#C026D3", "#c9940b"),
    ("#c026d3", "#c9940b"),
    ("#EC4899", "#f0aa1f"),
    ("#ec4899", "#f0aa1f"),
    ("#F472B6", "#f5c02e"),
    ("#f472b6", "#f5c02e"),
    ("#DB2777", "#c9940b"),
    ("#db2777", "#c9940b"),
    ("#E11D48", "#a37a08"),
    ("#e11d48", "#a37a08"),
    # Near-white with purple tint (logo white variant; only file using it)
    ("#F6F6FE", "#FFFFFF"),
    ("#f6f6fe", "#FFFFFF"),
]


def main() -> None:
    changed: list[Path] = []
    for p in sorted(ROOT.rglob("*.svg")):
        text = p.read_text(encoding="utf-8")
        orig = text
        for old, new in REPLACEMENTS:
            text = text.replace(old, new)
        if text != orig:
            p.write_text(text, encoding="utf-8", newline="\n")
            changed.append(p)
    print(f"Updated {len(changed)} file(s) under {ROOT}")
    for path in changed:
        rel = path.relative_to(ROOT)
        print(f"  {rel.as_posix()}")


if __name__ == "__main__":
    main()
