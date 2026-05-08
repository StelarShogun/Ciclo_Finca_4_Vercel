/**
 * Shared checkout success / confirmation copy (cart SweetAlert).
 * Used by clients-page.js and clients-users.js entrypoints.
 */

/** Horas máximas para retiro tras marcar listo (`meta[name="cf4-ready-to-pickup-expiration-hours"]`). */
export function getCf4ReadyToPickupExpirationHours() {
    const meta = document.querySelector('meta[name="cf4-ready-to-pickup-expiration-hours"]');
    const n = meta ? parseInt(meta.getAttribute('content'), 10) : NaN;
    if (!Number.isFinite(n) || n < 1) {
        return 72;
    }

    return n;
}

/** Frase legible para el aviso post-checkout (p. ej. "72 horas", "3 días"). */
export function formatCf4PickupWindowPhrase(hours) {
    let h = Math.floor(Number(hours));
    if (!Number.isFinite(h) || h < 1) {
        h = 72;
    }
    if (h >= 24 && h % 24 === 0) {
        const d = h / 24;

        return d === 1 ? '1 día' : `${d} días`;
    }

    return h === 1 ? '1 hora' : `${h} horas`;
}

/** Etiqueta corta del método de pago para el Swal de confirmación previa. */
export function getCf4PaymentMethodShortLabel(method) {
    switch (String(method || '').toLowerCase()) {
        case 'cash':
            return 'efectivo';
        case 'sinpe':
            return 'SINPE móvil';
        case 'transfer':
            return 'transferencia bancaria';
        default:
            return 'el método seleccionado';
    }
}

/**
 * Texto del Swal de éxito post-checkout. Cambia según las horas configuradas
 * (meta cf4-ready-to-pickup-expiration-hours) y el método de pago elegido.
 */
export function buildCf4CheckoutSuccessText(paymentMethod) {
    const phrase = formatCf4PickupWindowPhrase(getCf4ReadyToPickupExpirationHours());
    const base = 'Su pedido fue enviado con éxito. Cuando esté listo para recoger, '
        + `recibirá un aviso y tendrá hasta ${phrase} para retirarlo en tienda.`;

    let paymentLine;
    switch (String(paymentMethod || '').toLowerCase()) {
        case 'sinpe':
            paymentLine = ' El pago se realizará al momento del retiro mediante SINPE móvil; '
                + 'recuerde llevar el comprobante.';
            break;
        case 'transfer':
            paymentLine = ' El pago se realizará al momento del retiro mediante transferencia bancaria; '
                + 'recuerde llevar el comprobante.';
            break;
        case 'cash':
        default:
            paymentLine = ' El pago se realizará al momento del retiro en efectivo.';
            break;
    }

    return base + paymentLine;
}
