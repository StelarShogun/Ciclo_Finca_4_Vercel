// Gate test: `node features/cart/cart-calculations.test.ts`.
import assert from "node:assert/strict";

import { paginatedCartItems, totalCartQuantity } from "./cart-calculations.ts";

assert.equal(totalCartQuantity([{ quantity: 2 }, { quantity: 3 }]), 5);

const page = paginatedCartItems([1, 2, 3, 4, 5], 2, 2);
assert.deepEqual(page.items, [3, 4]);
assert.equal(page.pagination.currentPage, 2);
assert.equal(paginatedCartItems([1], 99, 10).pagination.currentPage, 1);

console.log("cart-calculations.test.ts OK");
