const FIT_MIN_PX = 13;
const FIT_MAX_PX = 32;

function fitWidthFor(element) {
    const content = element.closest('.kpi-content') || element.closest('.kpi-card') || element.parentElement;

    return content?.clientWidth || element.clientWidth;
}

/**
 * Shrinks `.kpi-value` until the full amount fits on one line inside the card.
 */
export function syncKpiValueScale(element) {
    if (!element) {
        return;
    }

    element.style.fontSize = '';
    element.style.textOverflow = '';

    const maxWidth = fitWidthFor(element);
    if (maxWidth <= 0) {
        return;
    }

    const cssMax = parseFloat(getComputedStyle(element).fontSize) || FIT_MAX_PX;
    let low = FIT_MIN_PX;
    let high = Math.min(cssMax, FIT_MAX_PX);
    let best = FIT_MIN_PX;

    while (low <= high) {
        const mid = Math.floor((low + high) / 2);
        element.style.fontSize = `${mid}px`;

        if (element.scrollWidth <= maxWidth) {
            best = mid;
            low = mid + 1;
        } else {
            high = mid - 1;
        }
    }

    element.style.fontSize = `${best}px`;

    const label = element.textContent.trim();
    if (label) {
        element.title = label;
    }
}

export function syncAllKpiValueScales(root = document) {
    requestAnimationFrame(() => {
        root.querySelectorAll('.kpi-value').forEach(syncKpiValueScale);
    });
}

let observerBound = false;

export function initKpiValueScaleObserver(root = document) {
    if (observerBound) {
        return;
    }

    const cards = root.querySelectorAll('.kpi-card');
    if (!cards.length) {
        return;
    }

    observerBound = true;

    const resize = () => syncAllKpiValueScales(root);
    const observer = new ResizeObserver(resize);
    cards.forEach((card) => observer.observe(card));

    if (document.fonts?.ready) {
        document.fonts.ready.then(resize).catch(() => {});
    }

    window.addEventListener('load', resize, { once: true });
}
