/**
 * Real-time notification toasts + badge sync for the client storefront.
 *
 * Polls /notifications/heartbeat (which now returns invoice counts too) and:
 *  - Shows floating toasts for new order-status notifications.
 *  - Updates the unread-notification badge (number in the profile circle).
 *  - Updates the invoice count + unseen-history badge.
 *  - Updates the hamburger alert dot.
 *  - Detects revision changes on invoice pages → reloads automatically.
 */

import { cf4OrderStatusToast } from './swal.js';
import { setHeaderAlertMeta, updateHeaderMenuToggleBadge } from './header-menu-alert.js';

// ------------------------------------------------------------------
// Session-storage deduplication (survives SPA-style navigation within tab)
// ------------------------------------------------------------------
const STORAGE_KEY = 'cf4-toasted-notification-ids';
const MAX_STORED_IDS = 50;

function readStoredIds() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch {
        return [];
    }
}

function storeId(id) {
    const normalized = String(id);
    const ids = readStoredIds();
    if (!ids.includes(normalized)) {
        ids.push(normalized);
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(ids.slice(-MAX_STORED_IDS)));
        } catch { /* quota */ }
    }
}

function wasShown(id) {
    return readStoredIds().includes(String(id));
}

// ------------------------------------------------------------------
// Badge helpers
// ------------------------------------------------------------------
function updateNotificationBadge(count) {
    const value = Math.max(0, Number(count) || 0);
    const label = value > 9 ? '9+' : String(value);

    // Main badge on the account trigger button
    let badge = document.getElementById('nav-notification-badge');
    if (!badge) {
        // Create it if not in DOM (can happen when count was 0 at page load
        // and server skipped rendering it)
        const avatarWrap = document.querySelector('.user-menu-trigger-avatar-wrap');
        if (avatarWrap) {
            badge = document.createElement('span');
            badge.id = 'nav-notification-badge';
            badge.className = 'cf4-invoice-count user-menu-trigger-notification-badge';
            badge.style.display = 'none';
            avatarWrap.appendChild(badge);
        }
    }
    if (badge) {
        badge.textContent = label;
        badge.style.display = value > 0 ? 'flex' : 'none';
    }

    // Inline badge inside the dropdown "Notificaciones" link
    document.querySelectorAll('.cf4-nav-badge--inline').forEach((el) => {
        el.textContent = label;
        el.style.display = value > 0 ? 'inline-flex' : 'none';
    });

    setHeaderAlertMeta('cf4-header-alert-notifications', value);
    updateHeaderMenuToggleBadge();
}

function updateInvoiceBadge(count) {
    const value = Math.max(0, Number(count) || 0);
    const invoiceLink = document.getElementById('invoices-link');
    if (!invoiceLink) return;

    let badge = document.getElementById('invoice-count');
    if (value > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'invoice-count';
            badge.className = 'cf4-invoice-count';
            invoiceLink.appendChild(badge);
        }
        badge.textContent = value;
        badge.style.display = 'flex';
    } else if (badge) {
        badge.style.display = 'none';
    }

    setHeaderAlertMeta('cf4-header-alert-invoices', value);
    updateHeaderMenuToggleBadge();
}

function updateUnseenHistoryBadge(count) {
    const value = Math.max(0, Number(count) || 0);
    const invoiceLink = document.getElementById('invoices-link');
    if (!invoiceLink) return;

    let badge = document.getElementById('history-badge');
    if (value > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'history-badge';
            badge.className = 'cf4-history-badge';
            badge.title = 'Compras nuevas en Historial';
            badge.setAttribute('aria-label', 'Historial con compras nuevas');
            invoiceLink.appendChild(badge);
        }
        badge.style.display = 'block';
    } else if (badge) {
        badge.style.display = 'none';
    }

    const tabBadge = document.getElementById('history-tab-badge');
    if (tabBadge) {
        tabBadge.style.display = value > 0 ? 'block' : 'none';
    }

    setHeaderAlertMeta('cf4-header-alert-history', value);
    updateHeaderMenuToggleBadge();
}

// ------------------------------------------------------------------
// Toast queue — prevents overlapping toasts
// ------------------------------------------------------------------
let toastQueue = Promise.resolve();

function enqueueToast(item) {
    toastQueue = toastQueue
        .then(() => cf4OrderStatusToast({
            kind: item.kind || 'ready_to_pickup',
            title: item.title || '¡Notificación!',
            message: item.message || '',
            actionUrl: item.action_url || '',
            actionLabel: item.action_label || 'Ver facturas',
            timer: 5000,
        }))
        .catch(() => {});
}

// ------------------------------------------------------------------
// Main polling loop
// ------------------------------------------------------------------
export function startNotificationToasts() {
    const metaUrl = document.querySelector('meta[name="cf4-notifications-heartbeat-url"]');
    if (!metaUrl) return;
    const url = metaUrl.getAttribute('content');
    if (!url) return;

    const isInvoicePath = () => window.location.pathname.startsWith('/invoices');
    const isNotificationsPath = () => window.location.pathname.startsWith('/notifications');

    // Seed initial values from meta tags already present on the page.
    const metaCount = document.querySelector('meta[name="cf4-invoice-initial-count"]')
        || document.querySelector('meta[name="cf4-invoice-count"]');
    const metaHistory = document.querySelector('meta[name="cf4-unseen-history-initial-count"]')
        || document.querySelector('meta[name="cf4-unseen-history-count"]');
    const metaRevision = document.querySelector('meta[name="cf4-invoice-revision"]');

    let lastCount = parseInt(metaCount?.getAttribute('content') || '0', 10);
    let lastUnseen = parseInt(metaHistory?.getAttribute('content') || '0', 10);
    let lastRevision = metaRevision?.getAttribute('content') ?? null;
    let revisionSynced = lastRevision !== null && lastRevision !== '';

    // Apply initial badge values immediately (avoids flicker before first poll).
    updateInvoiceBadge(lastCount);
    updateUnseenHistoryBadge(lastUnseen);

    // Snapshot of notification IDs already present on page load — we won't toast those.
    let baselineIds = null;

    const poll = async () => {
        try {
            const res = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;

            const data = await res.json();

            // --- badge updates ---
            updateNotificationBadge(data.unread_count ?? 0);
            updateInvoiceBadge(data.invoice_count ?? lastCount);
            updateUnseenHistoryBadge(data.unseen_history ?? lastUnseen);

            const newCount = data.invoice_count ?? lastCount;
            const newUnseen = data.unseen_history ?? lastUnseen;
            const newRevision = typeof data.revision === 'string' ? data.revision : null;
            const countChanged = newCount !== lastCount;
            const unseenChanged = newUnseen !== lastUnseen;
            const revisionChanged = newRevision !== null && revisionSynced && newRevision !== lastRevision;

            lastCount = newCount;
            lastUnseen = newUnseen;
            if (newRevision !== null) {
                if (!revisionSynced) {
                    lastRevision = newRevision;
                    revisionSynced = true;
                } else {
                    lastRevision = newRevision;
                }
            }

            // --- reload data-driven pages when list changes ---
            if (countChanged || unseenChanged || revisionChanged) {
                if (isInvoicePath() || isNotificationsPath()) {
                    location.reload();
                    return;
                }
            }

            // --- toasts for new notifications ---
            const toasts = Array.isArray(data.toasts) ? data.toasts : [];
            const currentIds = toasts.map((t) => String(t.id));

            if (baselineIds === null) {
                // First successful poll: record existing IDs, don't toast them.
                baselineIds = new Set(currentIds);
                return;
            }

            for (const item of toasts) {
                const id = String(item.id);
                if (baselineIds.has(id) || wasShown(id)) continue;

                enqueueToast(item);
                storeId(id);
                baselineIds.add(id);
            }
        } catch {
            /* ignore network errors */
        }
    };

    // 15 s on invoice pages (where changes are most expected), 20 s everywhere else.
    const intervalMs = isInvoicePath() ? 15_000 : 20_000;
    setInterval(poll, intervalMs);

    // First poll: short delay so the page finishes rendering.
    setTimeout(poll, 2500);
}
