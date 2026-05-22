/** Lazy SweetAlert2 — avoid loading on pages that never open a dialog. */

export async function getSwal() {
  const { default: Swal } = await import('sweetalert2')
  return Swal
}

export async function fireSwal(options) {
  const Swal = await getSwal()
  return Swal.fire(options)
}
