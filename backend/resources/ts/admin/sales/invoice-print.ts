document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const button = target.closest<HTMLElement>('[data-confirm-print]');
    if (!button) {
        return;
    }

    event.preventDefault();
    window.print();
});

window.addEventListener('load', () => {
    const autoPrint = document.querySelector<HTMLMetaElement>('meta[name="auto-print"]')?.content === '1';
    if (autoPrint) {
        window.setTimeout(() => window.print(), 400);
    }
}, { once: true });
