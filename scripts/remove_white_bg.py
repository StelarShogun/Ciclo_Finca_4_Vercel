#!/usr/bin/env python3
"""Quita fondo blanco conectado a los bordes; preserva blancos interiores (p. ej. nieve)."""
from __future__ import annotations

import sys
from collections import deque

import numpy as np
from PIL import Image


def remove_background(
    arr: np.ndarray,
    *,
    connect_lum_min: float = 200.0,
) -> np.ndarray:
    """RGB uint8 -> RGBA. Marca como fondo lo conectado a bordes por píxeles claros."""
    h, w = arr.shape[:2]
    lum = arr.astype(np.float32).mean(axis=2)
    visited = np.zeros((h, w), dtype=bool)
    q: deque[tuple[int, int]] = deque()

    for x in range(w):
        for y in (0, h - 1):
            if lum[y, x] >= connect_lum_min:
                q.append((x, y))
    for y in range(h):
        for x in (0, w - 1):
            if lum[y, x] >= connect_lum_min:
                q.append((x, y))

    while q:
        x, y = q.popleft()
        if visited[y, x]:
            continue
        if lum[y, x] < connect_lum_min:
            continue
        visited[y, x] = True
        if x > 0:
            q.append((x - 1, y))
        if x + 1 < w:
            q.append((x + 1, y))
        if y > 0:
            q.append((x, y - 1))
        if y + 1 < h:
            q.append((x, y + 1))

    out = np.zeros((h, w, 4), dtype=np.uint8)
    out[:, :, :3] = arr

    # Fondo conectado a bordes: blanco -> transparente; grises: alpha según mezcla con blanco
    bg = visited
    lum_i = np.clip(lum, 0.0, 255.0)
    alpha_bg = np.clip(255.0 - lum_i, 0.0, 255.0).astype(np.uint8)

    out[:, :, 3] = 255
    out[bg, 3] = alpha_bg[bg]
    # Despremultiplicar mezcla sobre blanco (logo monocromo negro)
    a = out[:, :, 3].astype(np.float32)
    mask = a > 0
    for c in range(3):
        ch = out[:, :, c].astype(np.float32)
        ch[mask] = np.clip(ch[mask] * 255.0 / a[mask], 0.0, 255.0)
        out[:, :, c] = ch.astype(np.uint8)

    out[out[:, :, 3] == 0, :3] = 0

    return out


def main() -> None:
    src = sys.argv[1] if len(sys.argv) > 1 else None
    dst = sys.argv[2] if len(sys.argv) > 2 else None
    if not src or not dst:
        print("Uso: remove_white_bg.py entrada.png salida.png", file=sys.stderr)
        sys.exit(1)

    im = Image.open(src).convert("RGB")
    arr = np.array(im)
    rgba = remove_background(arr)
    Image.fromarray(rgba, "RGBA").save(dst, compress_level=9, optimize=True)


if __name__ == "__main__":
    main()
