/**
 * Client auth welcome / logout toasts.
 *
 * Loaded conditionally by the layout (only when a `client_success_modal`
 * session flash is present). SweetAlert2 is imported dynamically so it does
 * not enter the layout's initial bundle for every visitor.
 */

function cf4AuthIconHtml(authIcon) {
    if (authIcon === 'google') {
        return '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--google" aria-hidden="true"><i class="fab fa-google"></i></span>';
    }
    if (authIcon === 'signout') {
        return '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--signout" aria-hidden="true"><i class="fas fa-right-from-bracket"></i></span>';
    }
    return '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--user" aria-hidden="true"><i class="fas fa-user-circle"></i></span>';
}

async function cf4AuthWelcomeToast(opts) {
    opts = opts || {};

    let Swal;
    try {
        const mod = await import('sweetalert2');
        Swal = mod.default || mod;
    } catch (err) {
        if (opts.thenUrl) {
            window.location.href = opts.thenUrl;
        }
        return;
    }

    const kind = opts.kind || 'welcome';
    const authIcon = opts.authIcon || (kind === 'logout' ? 'signout' : 'user');
    const displayName = (opts.displayName || '').trim();
    const timer = typeof opts.timer === 'number' ? opts.timer : 4000;

    let title;
    let text = opts.text || '';

    if (kind === 'logout') {
        title = opts.title || '¡Sesión cerrada!';
        if (!text) {
            text = 'Has cerrado sesión correctamente.';
        }
    } else {
        title = displayName
            ? '¡Bienvenido, ' + displayName + '!'
            : '¡Bienvenido!';
        if (!text) {
            text = authIcon === 'google' ? 'Inicio de sesión con Google' : 'Inicio de sesión exitoso';
        }
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        didOpen() {
            const popup = Swal.getPopup();
            if (popup) {
                popup.addEventListener('mouseenter', Swal.stopTimer);
                popup.addEventListener('mouseleave', Swal.resumeTimer);
            }
        },
        customClass: {
            popup: 'swal2-client-auth-toast-popup',
            title: 'swal2-client-auth-toast-title',
            htmlContainer: 'swal2-client-auth-toast-html',
            timerProgressBar: 'swal2-client-auth-toast-progress',
            icon: 'swal2-client-auth-toast-icon-wrap',
        },
    });

    await Toast.fire({
        icon: false,
        iconHtml: cf4AuthIconHtml(authIcon),
        title,
        text,
    });

    if (opts.thenUrl) {
        window.location.href = opts.thenUrl;
    }
}

if (typeof window !== 'undefined') {
    window.cf4AuthWelcomeToast = cf4AuthWelcomeToast;
}
