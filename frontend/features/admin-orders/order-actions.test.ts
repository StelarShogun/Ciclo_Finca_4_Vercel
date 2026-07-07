// Gate test: `node features/admin-orders/order-actions.test.ts`.
import assert from "node:assert/strict";

import { orderActionsForStatus } from "./order-actions.ts";

assert.deepEqual(orderActionsForStatus("pending"), ["view", "mark-ready", "cancel"]);
assert.deepEqual(orderActionsForStatus("ready_to_pickup"), ["view", "complete", "cancel"]);
assert.deepEqual(orderActionsForStatus("completed"), ["view", "invoice"]);
assert.deepEqual(orderActionsForStatus("cancelled"), ["view"]);

console.log("order-actions.test.ts OK");
