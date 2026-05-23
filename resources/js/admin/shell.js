/**
 * Admin shell — shared admin UI (tables, list pagination, SweetAlert2 prefetch).
 */
import '../shared/admin-table-responsive.js';
import '../shared/ajax-pagination.js';
import { getSwal } from './shared/swal.js';

const prefetchSwal = () => {
    void getSwal();
};

if (typeof requestIdleCallback === 'function') {
    requestIdleCallback(prefetchSwal, { timeout: 2500 });
} else {
    setTimeout(prefetchSwal, 200);
}
