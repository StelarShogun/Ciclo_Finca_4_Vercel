// Gate test: `node lib/otp.test.ts`
import assert from "node:assert/strict";

import { applyOtpInput } from "./otp.ts";

const empty = ["", "", "", "", "", ""];

// Tecleo simple avanza a la siguiente caja.
assert.deepEqual(applyOtpInput(empty, 0, "4"), { digits: ["4", "", "", "", "", ""], focus: 1 });

// Pegar un código completo llena todo y enfoca la última.
assert.deepEqual(applyOtpInput(empty, 0, "123456"), { digits: ["1", "2", "3", "4", "5", "6"], focus: 5 });

// Pegar a mitad respeta el límite de 6 cajas.
assert.deepEqual(applyOtpInput(empty, 4, "9876"), { digits: ["", "", "", "", "9", "8"], focus: 5 });

// Entrada no numérica limpia la caja actual.
assert.deepEqual(applyOtpInput(["1", "2", "", "", "", ""], 1, "x"), { digits: ["1", "", "", "", "", ""], focus: null });

// Sobrescribir una caja llena reemplaza el dígito (select on focus + slice).
assert.deepEqual(applyOtpInput(["1", "2", "3", "4", "5", "6"], 2, "7"), { digits: ["1", "2", "7", "4", "5", "6"], focus: 3 });

console.log("otp.test.ts OK");
