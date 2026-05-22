/** Active-invoice + unseen-historial badge polling — only on pages that ship heartbeat meta. */

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
}

export function startInvoiceHeartbeat() {
  const metaUrl = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]')
  const metaCount = document.querySelector('meta[name="cf4-invoice-initial-count"]')
  const metaHistory = document.querySelector('meta[name="cf4-unseen-history-initial-count"]')
  if (!metaUrl) return

  let lastCount = parseInt(metaCount ? metaCount.getAttribute('content') : '0', 10)
  let lastUnseenHistory = parseInt(metaHistory ? metaHistory.getAttribute('content') : '0', 10)

  updateInvoiceCount(lastCount)
  updateUnseenHistoryBadge(lastUnseenHistory)

  const url = metaUrl.getAttribute('content')
  const intervalMs = 60000

  setInterval(async function () {
    try {
      const res = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      if (!res.ok) return
      const data = await res.json()
      const unseen = parseInt(data.unseen_history, 10) || 0
      const countChanged = data.count !== lastCount
      const historyChanged = unseen !== lastUnseenHistory

      if (countChanged) {
        lastCount = data.count
        updateInvoiceCount(lastCount)
      }
      if (historyChanged) {
        lastUnseenHistory = unseen
        updateUnseenHistoryBadge(lastUnseenHistory)
      }
      if ((countChanged || historyChanged) && window.location.pathname.startsWith('/invoices')) {
        location.reload()
      }
    } catch (_) {
      /* ignore network errors */
    }
  }, intervalMs)
}
