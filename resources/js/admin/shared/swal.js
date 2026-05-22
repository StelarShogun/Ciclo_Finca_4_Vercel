/** Lazy SweetAlert2 for admin bundles. */

let swalModulePromise = null;

export async function getSwal() {
    if (typeof window !== 'undefined' && window.Swal) {
        return window.Swal;
    }
    if (!swalModulePromise) {
        swalModulePromise = import('sweetalert2').then((mod) => {
            const Swal = mod.default ?? mod;
            window.Swal = Swal;
            return Swal;
        });
    }
    return swalModulePromise;
}

export async function fireSwal(options) {
    const Swal = await getSwal();
    return Swal.fire(options);
}
