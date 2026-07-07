// Gate test: `node lib/frontend-helpers.test.ts` (Node >= 22, type stripping).
import assert from "node:assert/strict";

import { queryKeys } from "./query-keys.ts";
import { formatCRC } from "./money.ts";
import { apiErrorMessage } from "./errors.ts";

assert.equal(formatCRC(1234), "₡1 234");
assert.deepEqual(queryKeys.cart, ["cart"]);
assert.deepEqual(queryKeys.adminProducts(2, { search: "bike" }), ["admin-products", 2, { search: "bike" }]);
assert.equal(apiErrorMessage(new Error("plain"), "Fallback"), "Fallback");

console.log("frontend-helpers.test.ts OK");
