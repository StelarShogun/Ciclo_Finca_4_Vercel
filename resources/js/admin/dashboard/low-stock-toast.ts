// @ts-nocheck
export function initLowStockToast() {
    const toast = document.getElementById('low-stock-toast');
    const closeBtn = document.getElementById('close-low-stock-toast');
    if (!toast || !closeBtn) {
        return;
    }

    const hideToast = () => {
        toast.classList.add('ls-toast--hiding');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    };

    const autoTimer = window.setTimeout(hideToast, 7000);

    closeBtn.addEventListener('click', () => {
        window.clearTimeout(autoTimer);
        hideToast();
    });
}
