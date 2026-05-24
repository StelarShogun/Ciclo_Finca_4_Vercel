/** Active-invoice + unseen-historial badge polling — only on pages that ship heartbeat meta. */

import { updateHeaderMenuToggleBadge } from './header-menu-alert.js'

function updateInvoiceCount(count) {
  const invoiceLink = document.getElementById('invoices-link')
  if (!invoiceLink) return

  let badge = document.getElementById('invoice-count')

  if (count > 0) {
    if (!badge) {
      badge = document.createElement('span')
      badge.id = 'invoice-count'
      badge.className = 'cf4-invoice-count'
      invoiceLink.appendChild(badge)
    }
    badge.textContent = count
    badge.style.display = 'flex'
  } else if (badge) {
    badge.style.display = 'none'
  }

  updateHeaderMenuToggleBadge()
}

/** Dot badge when the client has unseen completed orders in Historial. */
function updateUnseenHistoryBadge(count) {
  const invoiceLink = document.getElementById('invoices-link')
  if (!invoiceLink) return

  let badge = document.getElementById('history-badge')

  if (count > 0) {
    if (!badge) {
      badge = document.createElement('span')
      badge.id = 'history-badge'
      badge.className = 'cf4-history-badge'
      badge.title = 'Compras nuevas en Historial'
      badge.setAttribute('aria-label', 'Historial con compras nuevas')
      invoiceLink.appendChild(badge)
    }
    badge.style.display = 'block'
  } else if (badge) {
    badge.style.display = 'none'
  }

  const tabBadge = document.getElementById('history-tab-badge')
  if (tabBadge) {
    tabBadge.style.display = count > 0 ? 'block' : 'none'
  }

  updateHeaderMenuToggleBadge()
}

function isInvoicesSectionPath() {
  return window.location.pathname.startsWith('/invoices')
}

export function startInvoiceHeartbeat() {
  const metaUrl = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]')
  const metaCount = document.querySelector('meta[name="cf4-invoice-initial-count"]')
    || document.querySelector('meta[name="cf4-invoice-count"]')
  const metaHistory = document.querySelector('meta[name="cf4-unseen-history-initial-count"]')
    || document.querySelector('meta[name="cf4-unseen-history-count"]')
  const metaRevision = document.querySelector('meta[name="cf4-invoice-revision"]')
  if (!metaUrl) return

  let lastCount = parseInt(metaCount ? metaCount.getAttribute('content') : '0', 10)
  let lastUnseenHistory = parseInt(metaHistory ? metaHistory.getAttribute('content') : '0', 10)
  let lastRevision = metaRevision ? metaRevision.getAttribute('content') : null
  let hasSyncedRevision = lastRevision !== null && lastRevision !== ''

  updateInvoiceCount(lastCount)
  updateUnseenHistoryBadge(lastUnseenHistory)

  const url = metaUrl.getAttribute('content')
  const intervalMs = isInvoicesSectionPath() ? 15000 : 60000

  const poll = async function () {
    try {
      const res = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      })
      if (!res.ok) return
      const data = await res.json()
      const unseen = parseInt(data.unseen_history, 10) || 0
      const countChanged = data.count !== lastCount
      const historyChanged = unseen !== lastUnseenHistory
      const revision = typeof data.revision === 'string' ? data.revision : null
      const revisionChanged = revision !== null
        && hasSyncedRevision
        && revision !== lastRevision

      if (countChanged) {
        lastCount = data.count
        updateInvoiceCount(lastCount)
      }
      if (historyChanged) {
        lastUnseenHistory = unseen
        updateUnseenHistoryBadge(lastUnseenHistory)
      }
      if (revision !== null) {
        if (!hasSyncedRevision) {
          lastRevision = revision
          hasSyncedRevision = true
        } else if (revisionChanged) {
          lastRevision = revision
        }
      }

      if ((countChanged || historyChanged || revisionChanged) && isInvoicesSectionPath()) {
        location.reload()
      }
    } catch (_) {
      /* ignore network errors */
    }
  }

  setInterval(poll, intervalMs)
  if (isInvoicesSectionPath()) {
    setTimeout(poll, 3000)
  }
}
