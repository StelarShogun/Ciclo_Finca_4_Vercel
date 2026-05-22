/**
 * Admin shell — prefetch SweetAlert2 on idle (replaces blocking CDN script).
 */
import '../shared/admin-table-responsive.js';
import { getSwal } from './shared/swal.js';

const prefetchSwal = () => {
    void getSwal();
};

if (typeof requestIdleCallback === 'function') {
    requestIdleCallback(prefetchSwal, { timeout: 2500 });
} else {
    setTimeout(prefetchSwal, 200);
}
