#!/usr/bin/env python3
"""Cross-check Vite inputs (vite.config.js) vs @vite() in Blade views."""

from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def main() -> int:
    vite_config = (ROOT / "vite.config.js").read_text(encoding="utf-8")
    vite_inputs = set(re.findall(r'"(resources/[^"]+)"', vite_config))

    print(f"Vite inputs: {len(vite_inputs)}\n")

    blade_js: dict[str, list[str]] = {}
    for blade in sorted((ROOT / "resources/views").rglob("*.blade.php")):
        text = blade.read_text(encoding="utf-8", errors="ignore")
        paths: list[str] = []
        for match in re.finditer(r"@vite\(\[(.*?)\]\)", text, re.S):
            inner = match.group(1)
            for path in re.findall(r"'(resources/[^']+)'|\"(resources/[^\"]+)\"", inner):
                paths.append(path[0] or path[1])
        js_paths = [p for p in paths if p.endswith((".js", ".tsx"))]
        if js_paths:
            blade_js[str(blade.relative_to(ROOT))] = js_paths

    print("Blade views with JS/TS @vite references:\n")
    missing_from_vite: set[str] = set()
    for view, paths in blade_js.items():
        print(view)
        for path in paths:
            marker = "OK" if path in vite_inputs else "MISSING in vite.config"
            if path not in vite_inputs:
                missing_from_vite.add(path)
            print(f"  - {path} [{marker}]")
        print()

    if missing_from_vite:
        print("Add these to vite.config.js input or fix Blade paths:")
        for path in sorted(missing_from_vite):
            print(f"  - {path}")
        return 1

    print("All Blade JS paths are registered in vite.config.js.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
