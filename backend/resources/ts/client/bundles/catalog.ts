// @ts-nocheck
import { fireSwal } from '../swal';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from '../checkout-copy';
import {
    addToCart,
    getCsrfToken,
    isClientStockShortMessage,
    updateCartCount,
} from '../cart-shared';
import '../../shared/ajax-pagination';

window.__cf4ClientPageJsLoaded = true;

import { initProductSpotlightCarousels } from '../init-product-spotlight-carousel';
import { initCatalogFilterSelects } from '../catalog-filter-select';

export function initClientCatalogPage() {
  initCatalogFilterSelects();

    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                fireSwal({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            addToCart(addBtn.dataset.productId, 1, addBtn);
            return;
        }

        var guestBtn = e.target.closest('.guest-add-btn');
        if (guestBtn) {
            if (guestBtn.dataset.purchasable === '0' || parseInt(guestBtn.dataset.productStock, 10) < 1) {
                fireSwal({ icon: 'warning', title: 'Producto agotado', text: 'Este producto no tiene unidades disponibles.' });
                return;
            }
            window.location.href = '/login';
        }
    });

    // Catalog price-range filter validation.
    (function initCatalogPriceFilter() {
        var form      = document.getElementById('filter-form');
        if (!form) return;
        var minInput  = document.getElementById('min_price');
        var maxInput  = document.getElementById('max_price');
        var submitBtn = document.getElementById('filter-submit-btn');

        function checkPriceRange() {
            if (!minInput || !maxInput || !submitBtn) return;
            var min       = parseFloat(minInput.value);
            var max       = parseFloat(maxInput.value);
            var minFilled = minInput.value.trim() !== '';
            var maxFilled = maxInput.value.trim() !== '';
            var negMin    = minFilled && !isNaN(min) && min < 0;
            var negMax    = maxFilled && !isNaN(max) && max < 0;
            var invalid   = negMin || negMax || (minFilled && maxFilled && !isNaN(min) && !isNaN(max) && min > max);
            submitBtn.disabled = invalid;
            if (invalid) {
                submitBtn.setAttribute(
                    'title',
                    negMin || negMax
                        ? 'Los precios no pueden ser negativos.'
                        : 'El precio mínimo debe ser menor o igual al precio máximo.'
                );
            } else {
                submitBtn.removeAttribute('title');
            }
        }

        if (minInput) minInput.addEventListener('input',  checkPriceRange);
        if (minInput) minInput.addEventListener('change', checkPriceRange);
        if (maxInput) maxInput.addEventListener('input',  checkPriceRange);
        if (maxInput) maxInput.addEventListener('change', checkPriceRange);
        checkPriceRange();
    })();

    (function initCatalogFilterSearchSync() {
        var filterForm = document.getElementById('filter-form');
        var navSearch = document.getElementById('catalog-nav-search');
        var hiddenSearch = document.getElementById('catalog-filter-search-fallback');
        if (!filterForm) return;

        function syncHiddenFromNav() {
            if (!navSearch || !hiddenSearch) return;
            hiddenSearch.value = String(navSearch.value || '');
        }

        if (navSearch && hiddenSearch) {
            navSearch.addEventListener('input', syncHiddenFromNav);
            navSearch.addEventListener('change', syncHiddenFromNav);
            syncHiddenFromNav();
        }

        filterForm.addEventListener('formdata', function (e) {
            var q = navSearch
                ? String(navSearch.value || '').trim()
                : (hiddenSearch ? String(hiddenSearch.value || '').trim() : '');
            e.formData.set('search', q);
        });
    })();

    // Home: carrusel de categorías (padres + chips de subcategorías).
    (function initCategoriesCarousel() {
        var wrap  = document.querySelector('[data-categories-carousel]');
        if (!wrap) return;
        var track = wrap.querySelector('[data-carousel-track]');
        var prev  = wrap.querySelector('[data-carousel-prev]');
        var next  = wrap.querySelector('[data-carousel-next]');
        if (!track || !prev || !next) return;

        function getStep() {
            var first = track.querySelector('.category-slide');
            if (!first) return Math.max(120, track.clientWidth * 0.85);
            var gap = parseInt(getComputedStyle(track).gap, 10);
            if (isNaN(gap)) gap = 18;
            return first.getBoundingClientRect().width + gap;
        }

        function updateButtons() {
            var maxScroll = track.scrollWidth - track.clientWidth - 2;
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll;
        }

        prev.addEventListener('click', function () { track.scrollBy({ left: -getStep(), behavior: 'smooth' }); });
        next.addEventListener('click', function () { track.scrollBy({ left:  getStep(), behavior: 'smooth' }); });
        track.addEventListener('scroll',  function () { window.requestAnimationFrame(updateButtons); });
        window.addEventListener('resize', function () { updateButtons(); });
        updateButtons();
    })();

    // ----------------------------------------------------------------
    // FIX: Catalog filter toggle — usa clase .open en vez de style.display
    // ----------------------------------------------------------------
    (function initCatalogFilterToggle() {
        var btn     = document.getElementById('catalog-filter-toggle');
        var sidebar = document.getElementById('catalog-sidebar');
        if (!btn || !sidebar) return;

        function checkMobile() {
            if (window.innerWidth <= 1024) {
                btn.style.display = 'flex';
                // Si no está expandido, asegurar que .open no esté
                if (btn.getAttribute('aria-expanded') !== 'true') {
                    sidebar.classList.remove('open');
                }
            } else {
                btn.style.display = 'none';
                // En desktop siempre visible: agregar .open para que el CSS lo muestre
                sidebar.classList.add('open');
            }
        }

        btn.addEventListener('click', function () {
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!open));
            // Toggle la clase .open que el CSS usa para max-height/opacity
            sidebar.classList.toggle('open', !open);
            var caret = btn.querySelector('.fa-chevron-down');
            if (caret) caret.style.transform = open ? '' : 'rotate(180deg)';
            var label = btn.querySelector('span');
            if (label) label.textContent = open ? 'Filtrar productos' : 'Ocultar filtros';
        });

        checkMobile();
        window.addEventListener('resize', checkMobile);
    })();

    // Catálogo: panel + sidebar categorías (hover desktop, tap móvil).
    (function initCatalogCategoryUi() {
        var panel = document.getElementById('catalog-category-panel');
        var sidebar = document.getElementById('catalog-category-sidebar');
        var dataEl = document.getElementById('catalog-category-tree-data');
        var tree = [];
        try {
            tree = dataEl && dataEl.textContent ? JSON.parse(dataEl.textContent) : [];
        } catch (e) {
            tree = [];
        }
        if (!panel && !sidebar) return;

        function esc(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function hrefAttr(url) {
            return String(url).replace(/"/g, '%22');
        }

        function isDesktop() {
            return window.matchMedia && window.matchMedia('(min-width: 1024px)').matches;
        }

        function parseDelayMs(el, fallback) {
            if (!el) return fallback;
            var raw = el.getAttribute('data-close-delay-ms');
            var n = parseInt(raw, 10);
            return !isNaN(n) && n >= 0 ? n : fallback;
        }

        function resolveParentForActive(activeId) {
            var id = parseInt(activeId, 10);
            if (!id) return null;
            for (var i = 0; i < tree.length; i++) {
                var p = tree[i];
                if (p.id === id) return p;
                var ch = p.children || [];
                for (var j = 0; j < ch.length; j++) {
                    if (ch[j].id === id) return p;
                }
            }
            return null;
        }

        function activeChildIdIfAny(activeId, parentNode) {
            var id = parseInt(activeId, 10);
            if (!id || !parentNode) return null;
            var ch = parentNode.children || [];
            for (var j = 0; j < ch.length; j++) {
                if (ch[j].id === id) return id;
            }
            return null;
        }

        function parentNodeById(pid) {
            for (var i = 0; i < tree.length; i++) {
                if (tree[i].id === pid) return tree[i];
            }
            return null;
        }

        /* ---------- Panel superior ---------- */
        if (panel) {
            var backdrop = document.getElementById('catalog-category-backdrop');
            var trigger = document.getElementById('catalog-category-trigger');
            var closeBtn = document.getElementById('catalog-category-close');
            var subCol = document.getElementById('catalog-category-subcolumn');
            var hoverRoot = panel.querySelector('[data-catalog-panel-hover-root]');
            var panelDelay = parseDelayMs(panel, 150);
            var panelLeaveTimer = null;

            function clearPanelLeaveTimer() {
                if (panelLeaveTimer) {
                    clearTimeout(panelLeaveTimer);
                    panelLeaveTimer = null;
                }
            }

            function setParentRowsHovered(parentId) {
                panel.querySelectorAll('.catalog-category-parent-row').forEach(function (row) {
                    var pid = parseInt(row.getAttribute('data-parent-id'), 10);
                    row.classList.toggle('is-hovered', !!parentId && pid === parentId);
                });
            }

            function renderSubcolumn(parentNode, highlightChildId) {
                if (!subCol) return;
                if (!parentNode) {
                    subCol.innerHTML = '<p class="catalog-category-placeholder">Pasá el cursor sobre una categoría para ver subcategorías.</p>';
                    return;
                }
                var html = '';
                var ch = parentNode.children || [];
                ch.forEach(function (c) {
                    var cls = highlightChildId && c.id === highlightChildId
                        ? 'catalog-category-sub-link is-active'
                        : 'catalog-category-sub-link';
                    html += '<a class="' + cls + '" href="' + hrefAttr(c.url) + '">' + esc(c.name) + '</a>';
                });
                subCol.innerHTML = html;
            }

            function selectParent(parentNode, highlightChildId) {
                setParentRowsHovered(parentNode ? parentNode.id : null);
                renderSubcolumn(parentNode, highlightChildId || null);
            }

            function syncFromUrl() {
                if (!isDesktop()) return;
                var activeRaw = panel.getAttribute('data-active-category-id');
                var p = resolveParentForActive(activeRaw);
                var childHi = activeChildIdIfAny(activeRaw, p);
                if (p) {
                    selectParent(p, childHi);
                } else {
                    setParentRowsHovered(null);
                    renderSubcolumn(null, null);
                }
            }

            function scheduleClearPanelHover() {
                clearPanelLeaveTimer();
                panelLeaveTimer = setTimeout(function () {
                    panelLeaveTimer = null;
                    setParentRowsHovered(null);
                    renderSubcolumn(null, null);
                }, panelDelay);
            }

            function openPanel() {
                panel.classList.add('is-open');
                if (backdrop) backdrop.classList.add('is-open');
                panel.setAttribute('aria-hidden', 'false');
                if (backdrop) backdrop.setAttribute('aria-hidden', 'false');
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
                document.body.classList.add('catalog-category-panel-open');
                if (isDesktop()) syncFromUrl();
                else if (subCol) {
                    subCol.innerHTML = '<p class="catalog-category-placeholder">Expandí una categoría para ver subcategorías.</p>';
                }
            }

            function closePanel() {
                clearPanelLeaveTimer();
                panel.classList.remove('is-open');
                if (backdrop) backdrop.classList.remove('is-open');
                panel.setAttribute('aria-hidden', 'true');
                if (backdrop) backdrop.setAttribute('aria-hidden', 'true');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('catalog-category-panel-open');
            }

            function togglePanel() {
                if (panel.classList.contains('is-open')) closePanel();
                else openPanel();
            }

            if (hoverRoot && subCol) {
                hoverRoot.addEventListener('mouseenter', function () {
                    clearPanelLeaveTimer();
                });
                hoverRoot.addEventListener('mouseleave', function () {
                    if (!isDesktop()) return;
                    scheduleClearPanelHover();
                });

                panel.querySelectorAll('.catalog-category-parent-row').forEach(function (row) {
                    row.addEventListener('mouseenter', function () {
                        if (!isDesktop()) return;
                        clearPanelLeaveTimer();
                        var pid = parseInt(row.getAttribute('data-parent-id'), 10);
                        var pnode = parentNodeById(pid);
                        if (!pnode) return;
                        var activeRaw = panel.getAttribute('data-active-category-id');
                        var hi = activeChildIdIfAny(activeRaw, pnode);
                        selectParent(pnode, hi);
                    });
                });
            }

            panel.querySelectorAll('.catalog-category-panel-mobile-expand').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isDesktop()) return;
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    var id = btn.getAttribute('aria-controls');
                    var block = id ? document.getElementById(id) : null;
                    btn.setAttribute('aria-expanded', String(!open));
                    if (block) block.hidden = open;
                });
            });

            if (trigger) {
                trigger.addEventListener('click', function (e) {
                    if (isDesktop()) return;
                    e.stopPropagation();
                    togglePanel();
                });
            }
            if (closeBtn) closeBtn.addEventListener('click', function () { closePanel(); });
            if (backdrop) backdrop.addEventListener('click', function () { closePanel(); });

            document.addEventListener('click', function (ev) {
                if (!panel.classList.contains('is-open')) return;
                var t = ev.target;
                if (panel.contains(t)) return;
                if (trigger && trigger.contains(t)) return;
                closePanel();
            });

            document.addEventListener('keydown', function (ev) {
                if (!panel.classList.contains('is-open')) return;
                if (ev.key === 'Escape') {
                    ev.preventDefault();
                    closePanel();
                }
            });

            if (isDesktop()) syncFromUrl();

            window.addEventListener('resize', function () {
                if (isDesktop() && panel.classList.contains('is-open')) closePanel();
            });
        }

        /* ---------- Sidebar categorías ---------- */
        if (sidebar) {
            var sbDelay = parseDelayMs(sidebar, 150);
            var sbLeaveTimer = null;
            var flyoutPortalEl = null;
            var activeSidebarItem = null;
            var portalGlobalBound = false;
            var portalRepositionRaf = null;

            function clampNumber(value, min, max) {
                return Math.max(min, Math.min(max, value));
            }

            function ensureSidebarFlyoutPortal() {
                if (!flyoutPortalEl) {
                    flyoutPortalEl = document.getElementById('catalog-category-flyout-portal');
                    if (!flyoutPortalEl) {
                        flyoutPortalEl = document.createElement('div');
                        flyoutPortalEl.id = 'catalog-category-flyout-portal';
                        flyoutPortalEl.className = 'catalog-category-flyout-portal';
                        flyoutPortalEl.setAttribute('aria-hidden', 'true');
                        document.body.appendChild(flyoutPortalEl);
                    }

                    flyoutPortalEl.addEventListener('mouseenter', function () {
                        clearSbTimer();
                    });
                    flyoutPortalEl.addEventListener('mouseleave', function () {
                        scheduleSidebarFlyoutClose();
                    });
                }

                if (!portalGlobalBound) {
                    portalGlobalBound = true;

                    function schedulePortalReposition() {
                        if (!activeSidebarItem || !flyoutPortalEl || !flyoutPortalEl.classList.contains('is-open')) return;
                        if (portalRepositionRaf) return;
                        portalRepositionRaf = window.requestAnimationFrame(function () {
                            portalRepositionRaf = null;
                            if (activeSidebarItem && flyoutPortalEl && flyoutPortalEl.classList.contains('is-open')) {
                                positionSidebarFlyoutPortal(activeSidebarItem);
                            }
                        });
                    }

                    window.addEventListener('scroll', schedulePortalReposition, true);
                    var railScroll = sidebar.querySelector('.category-rail-scroll');
                    if (railScroll) {
                        railScroll.addEventListener('scroll', schedulePortalReposition, true);
                    }
                    var sidebarStack = sidebar.closest('.catalog-sidebar-stack');
                    if (sidebarStack) {
                        sidebarStack.addEventListener('scroll', schedulePortalReposition, true);
                    }

                    window.addEventListener('resize', function () {
                        if (!isDesktop()) {
                            closeSidebarFlyoutPortal();
                            return;
                        }
                        if (activeSidebarItem && flyoutPortalEl && flyoutPortalEl.classList.contains('is-open')) {
                            positionSidebarFlyoutPortal(activeSidebarItem);
                        }
                    });

                    document.addEventListener('keydown', function (ev) {
                        if (ev.key !== 'Escape') return;
                        var p = document.getElementById('catalog-category-flyout-portal');
                        if (p && p.classList.contains('is-open')) {
                            ev.preventDefault();
                            closeSidebarFlyoutPortal();
                        }
                    });
                }

                return flyoutPortalEl;
            }

            function clearSbTimer() {
                if (sbLeaveTimer) {
                    clearTimeout(sbLeaveTimer);
                    sbLeaveTimer = null;
                }
            }

            function closeSidebarFlyoutPortal() {
                clearSbTimer();
                if (activeSidebarItem) {
                    activeSidebarItem.classList.remove('is-flyout-open');
                    var fo = activeSidebarItem.querySelector('.catalog-category-flyout');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                }
                activeSidebarItem = null;
                var portal = document.getElementById('catalog-category-flyout-portal');
                if (portal) {
                    portal.classList.remove('is-open');
                    portal.setAttribute('aria-hidden', 'true');
                    portal.innerHTML = '';
                    portal.style.left = '';
                    portal.style.top = '';
                    portal.style.visibility = '';
                }
            }

            function closeAllSidebarFlyouts() {
                closeSidebarFlyoutPortal();
                sidebar.querySelectorAll('.catalog-category-sidebar-item.is-flyout-open').forEach(function (el) {
                    el.classList.remove('is-flyout-open');
                    var fo = el.querySelector('.catalog-category-flyout');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                });
            }

            function positionSidebarFlyoutPortal(item) {
                var portal = ensureSidebarFlyoutPortal();
                var row = item.querySelector('.catalog-category-sidebar-item-row') || item;
                var rect = row.getBoundingClientRect();
                var gap = 12;
                var viewportPadding = 12;

                portal.style.visibility = 'hidden';
                portal.classList.add('is-open');

                var portalRect = portal.getBoundingClientRect();
                var left = rect.right + gap;
                var top = rect.top;

                if (left + portalRect.width > window.innerWidth - viewportPadding) {
                    left = rect.left - portalRect.width - gap;
                }

                left = clampNumber(left, viewportPadding, window.innerWidth - portalRect.width - viewportPadding);
                top = clampNumber(
                    top,
                    viewportPadding,
                    window.innerHeight - portalRect.height - viewportPadding
                );

                portal.style.left = left + 'px';
                portal.style.top = top + 'px';
                portal.style.visibility = 'visible';
            }

            function openSidebarFlyoutPortal(item) {
                if (!isDesktop()) return;

                var sourceFlyout = item.querySelector('.catalog-category-flyout');
                if (!sourceFlyout) return;

                clearSbTimer();
                closeAllSidebarFlyouts();

                activeSidebarItem = item;
                item.classList.add('is-flyout-open');
                sourceFlyout.setAttribute('aria-hidden', 'false');

                var portal = ensureSidebarFlyoutPortal();
                portal.innerHTML = sourceFlyout.innerHTML;
                portal.setAttribute('aria-hidden', 'false');

                positionSidebarFlyoutPortal(item);
            }

            function scheduleSidebarFlyoutClose() {
                clearSbTimer();
                sbLeaveTimer = setTimeout(function () {
                    sbLeaveTimer = null;
                    closeSidebarFlyoutPortal();
                }, sbDelay);
            }

            sidebar.querySelectorAll('.catalog-category-sidebar-item[data-has-children="1"]').forEach(function (item) {
                item.addEventListener('mouseenter', function () {
                    if (!isDesktop()) return;
                    openSidebarFlyoutPortal(item);
                });

                item.addEventListener('mouseleave', function () {
                    if (!isDesktop()) return;
                    scheduleSidebarFlyoutClose();
                });

                item.addEventListener('focusin', function () {
                    if (!isDesktop()) return;
                    openSidebarFlyoutPortal(item);
                });

                item.addEventListener('focusout', function (ev) {
                    if (!isDesktop()) return;
                    var rt = ev.relatedTarget;
                    var portal = document.getElementById('catalog-category-flyout-portal');
                    if (portal && rt && portal.contains(rt)) return;
                    scheduleSidebarFlyoutClose();
                });
            });

            sidebar.querySelectorAll('.catalog-category-mobile-expand').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isDesktop()) return;
                    var item = btn.closest('.catalog-category-sidebar-item');
                    if (!item) return;
                    var open = btn.getAttribute('aria-expanded') === 'true';
                    var id = btn.getAttribute('aria-controls');
                    var block = id ? document.getElementById(id) : null;
                    var nextOpen = !open;
                    btn.setAttribute('aria-expanded', String(nextOpen));
                    item.classList.toggle('is-mobile-open', nextOpen);
                    if (block) block.setAttribute('aria-hidden', String(!nextOpen));
                });
            });

            sidebar.addEventListener('keydown', function (ev) {
                if (ev.key !== 'Escape') return;
                closeAllSidebarFlyouts();
                sidebar.querySelectorAll('.catalog-category-sidebar-item.is-mobile-open').forEach(function (item) {
                    item.classList.remove('is-mobile-open');
                    var fo = item.querySelector('.catalog-category-flyout');
                    var btn = item.querySelector('.catalog-category-mobile-expand');
                    if (fo) fo.setAttribute('aria-hidden', 'true');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                });
            });

            /* Toggle persistente del rail: en desktop el rail expandido tapa filtros (CSS capa); inert evita foco/clic en filtros. */
            var railToggle = document.getElementById('catalog-category-sidebar-toggle');
            var filtersAside = document.getElementById('catalog-sidebar');

            function syncRailExpandedForViewport() {
                if (isDesktop()) return;
                if (sidebar.classList.contains('is-expanded')) {
                    sidebar.classList.remove('is-expanded');
                    var cc = sidebar.closest('.catalog-container');
                    if (cc) cc.classList.remove('rail-expanded');
                    if (railToggle) {
                        railToggle.setAttribute('aria-expanded', 'false');
                        railToggle.setAttribute('aria-label', 'Expandir menú de categorías');
                    }
                }
                if (filtersAside) filtersAside.removeAttribute('inert');
            }

            window.addEventListener('resize', syncRailExpandedForViewport);

            if (railToggle) {
                railToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var expanded = sidebar.classList.toggle('is-expanded');
                    var catalogContainer = sidebar.closest('.catalog-container');
                    if (catalogContainer) {
                        catalogContainer.classList.toggle('rail-expanded', expanded);
                    }
                    if (filtersAside) {
                        if (expanded) filtersAside.setAttribute('inert', '');
                        else filtersAside.removeAttribute('inert');
                    }
                    railToggle.setAttribute('aria-expanded', String(expanded));
                    railToggle.setAttribute(
                        'aria-label',
                        expanded ? 'Contraer menú de categorías' : 'Expandir menú de categorías'
                    );
                    var portalNode = document.getElementById('catalog-category-flyout-portal');
                    if (activeSidebarItem && portalNode && portalNode.classList.contains('is-open')) {
                        positionSidebarFlyoutPortal(activeSidebarItem);
                    }
                });
            }
        }
    })();

    void initProductSpotlightCarousels();
}
