// Gate test: `node lib/pagination.test.ts` (Node >= 22, type stripping).
import assert from "node:assert/strict";

import { ELLIPSIS, clampPage, pageWindow } from "./pagination.ts";

// Pocas páginas: todas, sin ellipsis.
assert.deepEqual(pageWindow(1, 1), [1]);
assert.deepEqual(pageWindow(3, 10), [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

// Cerca del inicio: bloque inicial + ellipsis + 2 últimas.
assert.deepEqual(pageWindow(1, 20), [1, 2, 3, 4, 5, 6, 7, 8, ELLIPSIS, 19, 20]);
assert.deepEqual(pageWindow(6, 20), [1, 2, 3, 4, 5, 6, 7, 8, ELLIPSIS, 19, 20]);

// Medio: 2 primeras + ellipsis + ventana + ellipsis + 2 últimas.
assert.deepEqual(pageWindow(10, 20), [1, 2, ELLIPSIS, 8, 9, 10, 11, 12, ELLIPSIS, 19, 20]);

// Cerca del final: 2 primeras + ellipsis + bloque final.
assert.deepEqual(pageWindow(15, 20), [1, 2, ELLIPSIS, 13, 14, 15, 16, 17, 18, 19, 20]);
assert.deepEqual(pageWindow(20, 20), [1, 2, ELLIPSIS, 13, 14, 15, 16, 17, 18, 19, 20]);

// La página actual siempre está en la ventana.
for (let last = 1; last <= 40; last++) {
  for (let current = 1; current <= last; current++) {
    const items = pageWindow(current, last);
    assert.ok(items.includes(current), `current ${current} missing for last ${last}`);
    const nums = items.filter((i): i is number => typeof i === "number");
    assert.deepEqual(nums, [...nums].sort((a, b) => a - b), "pages ordered");
    assert.equal(new Set(nums).size, nums.length, "no duplicate pages");
  }
}

// clampPage
assert.equal(clampPage("5", 1, 10), 5);
assert.equal(clampPage("0", 3, 10), 1);
assert.equal(clampPage("99", 3, 10), 10);
assert.equal(clampPage("abc", 3, 10), 3);
assert.equal(clampPage(" 7 ", 1, 10), 7);

console.log("pagination.test.ts OK");
