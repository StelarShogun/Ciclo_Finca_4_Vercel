import Swal from 'sweetalert2';

window.Swal = Swal;

function init() {
    if (!window.location.pathname.includes('/classifications/edit')) return;

    const form = document.getElementById('product-classifications-form');
    if (!form) return;

    let isDirty = false;

    document.querySelectorAll('select').forEach((el) => {
        el.addEventListener('change', () => {
            isDirty = true;
        });
    });

    const submitHandler = (e) => {
        e.preventDefault();

        if (!isDirty) {
            Swal.fire({
                icon: 'info',
                title: 'Sin cambios',
                text: 'No modificaste ningún valor. No hay nada que guardar.',
                confirmButtonText: 'Entendido',
            });
            return;
        }

        Swal.fire({
            title: '¿Guardar cambios?',
            text: 'Se actualizarán los valores de atributos de este producto.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;
            isDirty = false;
            form.removeEventListener('submit', submitHandler);
            form.submit();
        });
    };

    form.addEventListener('submit', submitHandler);

    document.addEventListener('click', async (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        if (!isDirty) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        e.preventDefault();
        e.stopPropagation();

        const result = await Swal.fire({
            title: '¿Salir sin guardar?',
            text: 'Tenés cambios sin guardar. ¿Querés salir de todas formas?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'No, quedarme',
        });

        if (result.isConfirmed) {
            isDirty = false;
            window.location.href = href;
        }
    });

    document.addEventListener(
        'submit',
        async (e) => {
            const submitted = e.target;
            if (!(submitted instanceof HTMLFormElement)) return;
            if (submitted.id === 'product-classifications-form') return;
            if (!isDirty) return;

            e.preventDefault();
            e.stopImmediatePropagation();

            const result = await Swal.fire({
                title: '¿Salir sin guardar?',
                text: 'Tenés cambios sin guardar. ¿Querés salir de todas formas?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'No, quedarme',
            });

            if (result.isConfirmed) {
                isDirty = false;
                submitted.submit();
            }
        },
        true,
    );

    window.addEventListener('beforeunload', (e) => {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = '';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
