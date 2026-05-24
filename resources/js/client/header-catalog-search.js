/**
 * Navbar catalog search: trending + predictive suggestions (CF4-106/107).
 * Safe to call from clients-users.js (all pages) and clients-page.js (catalog);
 * only binds once per [data-catalog-suggestions] root.
 */
export function initHeaderCatalogSearch() {
    var root = document.querySelector('[data-catalog-suggestions]');
    var input = document.getElementById('catalog-nav-search') || document.getElementById('search');
    if (!root || !input) return;
    if (root.getAttribute('data-cf4-search-init') === '1') return;
    root.setAttribute('data-cf4-search-init', '1');

    var url = root.getAttribute('data-suggestions-url') || '';
    var trendingUrl = root.getAttribute('data-trending-url') || '';

    var list = document.getElementById('catalog-search-suggestions');
    if (!list) return;

    var state = {
        open: false,
        loading: false,
        error: false,
        items: [],
        activeIndex: -1,
        lastQuery: '',
        mode: 'idle',
        trendingCache: null,
        trendingAborter: null,
        aborter: null,
        debounceId: null,
    };

    list.setAttribute('aria-label', 'Tendencias y sugerencias de búsqueda');

    var listPortalHome = { parent: null, next: null };

    function clearListPortalStyles() {
        list.style.removeProperty('position');
        list.style.removeProperty('top');
        list.style.removeProperty('left');
        list.style.removeProperty('right');
        list.style.removeProperty('width');
        list.style.removeProperty('max-height');
        list.style.removeProperty('z-index');
    }

    function restoreListToDom() {
        if (!listPortalHome.parent) {
            return;
        }
        if (listPortalHome.next && listPortalHome.next.parentNode === listPortalHome.parent) {
            listPortalHome.parent.insertBefore(list, listPortalHome.next);
        } else {
            listPortalHome.parent.appendChild(list);
        }
        listPortalHome.parent = null;
        listPortalHome.next = null;
        clearListPortalStyles();
    }

    function syncMobileSuggestionsPosition() {
        if (window.innerWidth > 1024) {
            restoreListToDom();
            return;
        }

        if (!state.open) {
            restoreListToDom();
            return;
        }

        if (list.parentElement !== document.body) {
            listPortalHome.parent = list.parentElement;
            listPortalHome.next = list.nextSibling;
            document.body.appendChild(list);
        }

        var anchor = root.querySelector('.header-catalog-search-track') || root;
        var rect = anchor.getBoundingClientRect();
        var gap = 6;
        var side = Math.max(12, Math.round(rect.left));
        var width = Math.min(Math.round(rect.width), window.innerWidth - side - 12);

        list.style.position = 'fixed';
        list.style.top = Math.round(rect.bottom + gap) + 'px';
        list.style.left = side + 'px';
        list.style.right = 'auto';
        list.style.width = Math.max(width, 200) + 'px';
        list.style.maxHeight = Math.min(280, Math.round(window.innerHeight - rect.bottom - gap - 16)) + 'px';
        list.style.zIndex = '1205';
    }

    function setOpen(open) {
        state.open = !!open;
        list.classList.toggle('is-open', state.open);
        list.setAttribute('aria-hidden', state.open ? 'false' : 'true');
        input.setAttribute('aria-expanded', state.open ? 'true' : 'false');
        if (!state.open) {
            state.activeIndex = -1;
            restoreListToDom();
        } else {
            window.requestAnimationFrame(function () {
                syncMobileSuggestionsPosition();
                window.requestAnimationFrame(syncMobileSuggestionsPosition);
            });
        }
    }

    function abortTrendingFetch() {
        if (state.trendingAborter) {
            try { state.trendingAborter.abort(); } catch (err) { /* ignore */ }
        }
        state.trendingAborter = null;
    }

    function abortSuggestionsFetch() {
        if (state.aborter) {
            try { state.aborter.abort(); } catch (err) { /* ignore */ }
        }
        state.aborter = null;
    }

    function trimmedQueryLength() {
        return String(input.value || '').trim().length;
    }

    function esc(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function badgeFor(item) {
        if (!item) return '';
        if (item.type === 'category') return 'Categoría';
        if (item.match_type === 'sku') return 'SKU';
        if (item.match_type === 'category') return 'En categoría';
        if (item.match_type === 'trending') return 'Tendencia';
        if (item.match_type === 'featured') return 'Sugerido';
        return '';
    }

    function suggestionRowHtml(i, isActive, it) {
        var active = isActive ? ' is-active' : '';
        var title = esc(it.name || '');
        var metaParts = [];
        if (it.sku) metaParts.push(esc(it.sku));
        if (it.category) metaParts.push(esc(it.category));
        var shouldShowMeta = it.match_type !== 'trending_term';
        var meta = (shouldShowMeta && metaParts.length)
            ? '<div class="catalog-search-suggestion-meta">' + metaParts.join(' · ') + '</div>'
            : '';
        var badge = badgeFor(it);
        var badgeHtml = badge ? '<div class="catalog-search-suggestion-badge">' + esc(badge) + '</div>' : '';
        var termThumb =
            (it.match_type === 'trending_term' || (!it.image_url && (it.type === 'term' || it.match_type === 'trending_term')))
                ? ' catalog-search-suggestion-thumb--term'
                : '';

        var thumb = '';
        if (it.image_url) {
            thumb = '<div class="catalog-search-suggestion-thumb"><img src="' + esc(it.image_url) + '" alt="" loading="lazy"></div>';
        } else {
            thumb = '<div class="catalog-search-suggestion-thumb' + termThumb + '" aria-hidden="true"></div>';
        }

        return ''
            + '<div class="catalog-search-suggestion' + active + '"'
            + ' role="option"'
            + ' data-suggestion-index="' + i + '"'
            + ' aria-selected="' + (isActive ? 'true' : 'false') + '">'
            + thumb
            + '<div class="catalog-search-suggestion-body">'
            + '<div class="catalog-search-suggestion-title">' + title + '</div>'
            + meta
            + '</div>'
            + badgeHtml
            + '</div>';
    }

    function renderTrending(payload) {
        state.mode = 'trending';

        var flat = [];
        var html = '';
        var idx = 0;

        var products = (payload && Array.isArray(payload.products)) ? payload.products : [];
        var terms = (payload && !payload.is_fallback && Array.isArray(payload.terms)) ? payload.terms : [];
        var shouldShowTerms = terms.length > 0;

        if (shouldShowTerms) {
            var h1 = 'Tendencias de búsqueda';
            html += '<div class="catalog-search-suggestions-section" role="presentation">' + esc(h1) + '</div>';
            for (var ti = 0; ti < terms.length; ti++) {
                html += suggestionRowHtml(idx, idx === state.activeIndex, terms[ti] || {});
                flat.push(terms[ti]);
                idx += 1;
            }
        } else if (products.length) {
            var h2 = payload.is_fallback ? 'Productos sugeridos' : 'Productos en tendencia';
            html += '<div class="catalog-search-suggestions-section" role="presentation">' + esc(h2) + '</div>';
            for (var pi = 0; pi < products.length; pi++) {
                html += suggestionRowHtml(idx, idx === state.activeIndex, products[pi] || {});
                flat.push(products[pi]);
                idx += 1;
            }
        }

        state.items = flat;

        if (!html) {
            list.innerHTML = '<div class="catalog-search-suggestions-state">' + esc(
                'Aún no registramos suficientes búsquedas para mostrar tendencias.'
            ) + '</div>';
            return;
        }

        list.innerHTML = html;
    }

    function render() {
        if (!state.open) {
            list.innerHTML = '';
            return;
        }

        if (state.loading) {
            list.innerHTML = '<div class="catalog-search-suggestions-state">' + esc(
                state.mode === 'trending' ? 'Cargando tendencias...' : 'Cargando sugerencias...'
            ) + '</div>';
            return;
        }

        if (state.error) {
            list.innerHTML = '<div class="catalog-search-suggestions-state">' + esc(
                'No pudimos cargar resultados en este momento.'
            ) + '</div>';
            return;
        }

        if (state.mode === 'trending') {
            if (state.trendingCache) {
                renderTrending(state.trendingCache);
                return;
            }
            renderTrending({});
            return;
        }

        if (!state.items || state.items.length === 0) {
            list.innerHTML = '<div class="catalog-search-suggestions-state">' + esc(
                'No se encontraron productos relacionados'
            ) + '</div>';
            return;
        }

        var html = '';
        for (var i = 0; i < state.items.length; i++) {
            html += suggestionRowHtml(i, i === state.activeIndex, state.items[i] || {});
        }
        list.innerHTML = html;
    }

    function setActiveIndex(next) {
        var n = state.items ? state.items.length : 0;
        if (n <= 0) {
            state.activeIndex = -1;
            render();
            return;
        }
        if (next < -1) next = -1;
        if (next >= n) next = n - 1;
        state.activeIndex = next;
        render();
    }

    function close() {
        setOpen(false);
    }

    var toggleBtn = root.querySelector('.header-catalog-search-toggle');

    function setExpanded(exp, opts) {
        opts = opts || {};
        if (!toggleBtn) return;
        var on = !!exp;
        if (opts.instant) {
            root.classList.add('header-catalog-search--no-motion');
        }
        root.classList.toggle('is-expanded', on);
        toggleBtn.setAttribute('aria-expanded', on ? 'true' : 'false');
        if (on) {
            window.setTimeout(function () {
                try { input.focus(); } catch (err) { /* ignore */ }
                syncMobileSuggestionsPosition();
            }, 180);
        } else {
            close();
        }
        if (opts.instant) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    root.classList.remove('header-catalog-search--no-motion');
                });
            });
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!root.classList.contains('is-expanded')) {
                setExpanded(true);
                return;
            }
            try { input.focus(); } catch (err) { /* ignore */ }
        });

        document.addEventListener('mousedown', function (e) {
            if (!root.classList.contains('is-expanded')) return;
            if (root.contains(e.target)) return;
            setExpanded(false);
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) return;
            if (!root.classList.contains('is-expanded')) return;
            setExpanded(false, { instant: true });
        });

        window.addEventListener('pageshow', function (ev) {
            if (!ev.persisted) return;
            if (!root.classList.contains('is-expanded')) return;
            setExpanded(false, { instant: true });
        });
    }

    function openIfNeeded() {
        if (!state.open) setOpen(true);
    }

    function selectIndex(idx) {
        var it = state.items && idx >= 0 ? state.items[idx] : null;
        if (!it || !it.url) return;
        window.location.href = it.url;
    }

    function fetchTrending() {
        if (!trendingUrl) return;

        if (state.trendingCache) {
            state.mode = 'trending';
            state.loading = false;
            state.error = false;
            state.activeIndex = -1;
            openIfNeeded();
            render();
            return;
        }

        abortTrendingFetch();
        state.trendingAborter = new AbortController();
        state.mode = 'trending';
        state.loading = true;
        state.error = false;
        state.items = [];
        state.activeIndex = -1;
        openIfNeeded();
        render();

        fetch(trendingUrl, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            signal: state.trendingAborter.signal,
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Trending unavailable');
                return res.json();
            })
            .then(function (data) {
                if (trimmedQueryLength() >= 2 || state.mode !== 'trending') {
                    state.loading = false;
                    state.trendingAborter = null;
                    return;
                }

                state.loading = false;
                state.error = false;
                state.trendingCache = data && typeof data === 'object' ? data : { products: [], terms: [] };
                openIfNeeded();
                render();

                state.trendingAborter = null;
            })
            .catch(function (err) {
                state.trendingAborter = null;
                if (err && err.name === 'AbortError') return;
                if (trimmedQueryLength() >= 2 || state.mode !== 'trending') {
                    state.loading = false;
                    return;
                }
                state.loading = false;
                state.error = true;
                state.items = [];
                openIfNeeded();
                render();
            });
    }

    function tryOpenTrendingOnShortQuery() {
        if (trimmedQueryLength() >= 2) return;
        if (!trendingUrl) {
            close();
            return;
        }

        abortSuggestionsFetch();

        fetchTrending();
    }

    function fetchSuggestions(query) {
        abortSuggestionsFetch();

        state.aborter = new AbortController();
        state.loading = true;
        state.error = false;
        state.items = [];
        state.activeIndex = -1;
        state.mode = 'predictive';

        abortTrendingFetch();

        openIfNeeded();
        render();

        var reqUrl = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'search=' + encodeURIComponent(query);

        fetch(reqUrl, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            signal: state.aborter.signal,
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (trimmedQueryLength() < 2 || state.mode !== 'predictive') {
                    state.loading = false;
                    return;
                }
                state.loading = false;
                state.error = false;
                state.items = (data && Array.isArray(data.suggestions)) ? data.suggestions : [];
                openIfNeeded();
                render();
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                state.loading = false;
                state.error = true;
                state.items = [];
                openIfNeeded();
                render();
            });
    }

    function schedule(query) {
        if (!url) return;
        if (state.debounceId) clearTimeout(state.debounceId);
        var delay = query.length >= 4 ? 0 : 160;
        state.debounceId = setTimeout(function () {
            state.debounceId = null;
            fetchSuggestions(query);
        }, delay);
    }

    if (!url && !trendingUrl) return;

    input.addEventListener('input', function () {
        var q = String(input.value || '').trim();
        state.lastQuery = q;

        if (q.length < 2) {
            abortSuggestionsFetch();
            if (state.debounceId) {
                clearTimeout(state.debounceId);
                state.debounceId = null;
            }

            tryOpenTrendingOnShortQuery();

            return;
        }

        if (!url) {
            abortTrendingFetch();
            close();
            return;
        }

        state.mode = 'predictive';

        abortTrendingFetch();
        schedule(q);
    });

    ['focus', 'pointerdown'].forEach(function (ev) {
        input.addEventListener(ev, function () {
            if (trimmedQueryLength() < 2) {
                tryOpenTrendingOnShortQuery();
            }
        });
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            if (!state.open) setOpen(true);
            if (state.open) {
                e.preventDefault();
                setActiveIndex(state.activeIndex + 1);
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            if (state.open) {
                e.preventDefault();
                setActiveIndex(state.activeIndex - 1);
            }
            return;
        }

        if (e.key === 'Escape') {
            if (state.open) {
                e.preventDefault();
                close();
                return;
            }
            if (root.classList.contains('is-expanded')) {
                e.preventDefault();
                input.value = '';
                setExpanded(false);
                if (toggleBtn) {
                    try { toggleBtn.focus(); } catch (err) { /* ignore */ }
                }
            }
            return;
        }

        if (e.key === 'Enter') {
            if (state.open && state.activeIndex >= 0) {
                e.preventDefault();
                selectIndex(state.activeIndex);
            }
            return;
        }
    });

    list.addEventListener('mousemove', function (e) {
        var row = e.target && e.target.closest ? e.target.closest('[data-suggestion-index]') : null;
        if (!row) return;
        var idx = parseInt(row.getAttribute('data-suggestion-index'), 10);
        if (!isNaN(idx) && idx !== state.activeIndex) {
            state.activeIndex = idx;
            render();
        }
    });

    list.addEventListener('mousedown', function (e) {
        var row = e.target && e.target.closest ? e.target.closest('[data-suggestion-index]') : null;
        if (!row) return;
        e.preventDefault();
        var idx = parseInt(row.getAttribute('data-suggestion-index'), 10);
        if (!isNaN(idx)) selectIndex(idx);
    });

    document.addEventListener('mousedown', function (e) {
        if (!state.open) return;
        if (root.contains(e.target) || list.contains(e.target) || input.contains(e.target)) return;
        close();
    });

    window.addEventListener('resize', syncMobileSuggestionsPosition);
    window.addEventListener('scroll', syncMobileSuggestionsPosition, true);

    window.cf4SyncHeaderSearchSuggestionsPosition = syncMobileSuggestionsPosition;
}
