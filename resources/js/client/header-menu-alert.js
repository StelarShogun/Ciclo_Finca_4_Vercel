/** Yellow alert dot on the mobile hamburger when cart / invoices / notifications need attention. */

function elementSignalsAlert(id, { numericMin = 1 } = {}) {
  const el = document.getElementById(id)
  if (!el) return false

  if (numericMin !== null) {
    const value = parseInt(el.textContent, 10) || 0
    if (value < numericMin) return false
  }

  if (el.hidden) return false
  if (el.style.display === 'none') return false

  return window.getComputedStyle(el).display !== 'none'
}

export function headerMenuHasPendingAlerts() {
  return (
    elementSignalsAlert('cart-count', { numericMin: 1 })
    || elementSignalsAlert('invoice-count', { numericMin: 1 })
    || elementSignalsAlert('nav-notification-badge', { numericMin: 1 })
    || elementSignalsAlert('history-badge', { numericMin: null })
  )
}

export function updateHeaderMenuToggleBadge() {
  const toggle = document.getElementById('header-menu-toggle')
  if (!toggle) return

  const hasAlert = headerMenuHasPendingAlerts()
  let badge = toggle.querySelector('.header-menu-toggle-badge')

  if (hasAlert) {
    if (!badge) {
      badge = document.createElement('span')
      badge.className = 'header-menu-toggle-badge'
      badge.setAttribute('aria-hidden', 'true')
      toggle.appendChild(badge)
    }
    badge.hidden = false
    toggle.classList.add('has-alert')
    toggle.setAttribute('aria-label', 'Abrir menú de navegación (tienes novedades)')
  } else {
    if (badge) badge.hidden = true
    toggle.classList.remove('has-alert')
    toggle.setAttribute('aria-label', 'Abrir menú de navegación')
  }
}
