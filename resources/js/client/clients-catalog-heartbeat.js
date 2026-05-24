/** Poll catalog version and reload when admin adds products or brands (CF4-166). */

export function startCatalogHeartbeat() {
    const metaUrl = document.querySelector('meta[name="cf4-catalog-heartbeat-url"]');
    if (!metaUrl) {
        return;
    }

    const metaVersion = document.querySelector('meta[name="cf4-catalog-initial-version"]');
    let lastVersion = metaVersion?.getAttribute('content') || '0';
    const url = metaUrl.getAttribute('content');
    const intervalMs = 60000;

    async function checkCatalogVersion() {
        if (document.visibilityState === 'hidden') {
            return;
        }

        try {
            const res = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                return;
            }

            const data = await res.json();
            const nextVersion = String(data.version ?? '');
            if (nextVersion === '' || nextVersion === lastVersion) {
                return;
            }

            lastVersion = nextVersion;
            window.location.reload();
        } catch {
            /* ignore network errors */
        }
    }

    void checkCatalogVersion();
    window.setInterval(checkCatalogVersion, intervalMs);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            void checkCatalogVersion();
        }
    });
}
