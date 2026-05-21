/** Pending-invoice badge polling — load only on pages that ship heartbeat meta. */

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

export function startInvoiceHeartbeat() {
  const metaUrl = document.querySelector('meta[name="cf4-invoice-heartbeat-url"]')
  const metaCount = document.querySelector('meta[name="cf4-invoice-initial-count"]')
  if (!metaUrl) return

  let lastCount = parseInt(metaCount ? metaCount.getAttribute('content') : '0', 10)
  updateInvoiceCount(lastCount)

  const url = metaUrl.getAttribute('content')
  const intervalMs = 60000

  setInterval(async function () {
    try {
      const res = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      if (!res.ok) return
      const data = await res.json()
      if (data.count !== lastCount) {
        lastCount = data.count
        updateInvoiceCount(lastCount)
        if (window.location.pathname.startsWith('/invoices')) {
          location.reload()
        }
      }
    } catch (_) {
      /* ignore network errors */
    }
  }, intervalMs)
}
