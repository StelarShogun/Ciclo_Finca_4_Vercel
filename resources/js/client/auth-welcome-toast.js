/**
 * Client auth welcome / logout toasts.
 *
 * Loaded conditionally by the layout (only when a `client_success_modal`
 * session flash is present). SweetAlert2 is imported dynamically so it does
 * not enter the layout's initial bundle for every visitor.
 */

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function cf4AuthToastFirstName(displayName) {
    const trimmed = (displayName || '').trim();
    if (!trimmed) {
        return '';
    }

    return trimmed.split(/\s+/)[0] || '';
}

function cf4AuthToastIconMarkup(authIcon) {
    if (authIcon === 'google') {
        return (
            '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--google" aria-hidden="true">' +
            '<span class="google-g-icon">G</span>' +
            '</span>'
        );
    }
    if (authIcon === 'signout') {
        return (
            '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--signout" aria-hidden="true">' +
            '<i class="fas fa-right-from-bracket"></i>' +
            '</span>'
        );
    }

    return (
        '<span class="cf4-auth-toast-icon cf4-auth-toast-icon--user" aria-hidden="true">' +
        '<i class="fas fa-user-circle"></i>' +
        '</span>'
    );
}

function cf4AuthToastHtml(opts) {
    const authIcon = opts.authIcon || 'user';
    const firstName = cf4AuthToastFirstName(opts.displayName);
    const isLogout = opts.kind === 'logout';

    let title;
    let subtitle;

    if (isLogout) {
        title = opts.title || '¡Sesión cerrada!';
        subtitle = opts.text || 'Has cerrado sesión correctamente.';
    } else if (firstName) {
        title = '¡Hola, ' + firstName + '!';
        if (authIcon === 'google') {
            subtitle = opts.text || 'Conectado con Google';
        } else {
            subtitle = opts.text || 'Inicio de sesión exitoso';
        }
    } else {
        title = '¡Bienvenido!';
        subtitle =
            opts.text ||
            (authIcon === 'google' ? 'Conectado con Google' : 'Inicio de sesión exitoso');
    }

    return (
        '<div class="cf4-auth-toast-card" role="status">' +
        cf4AuthToastIconMarkup(authIcon) +
        '<div class="cf4-auth-toast-copy">' +
        '<p class="cf4-auth-toast-title">' +
        escapeHtml(title) +
        '</p>' +
        '<p class="cf4-auth-toast-subtitle">' +
        escapeHtml(subtitle) +
        '</p>' +
        '</div>' +
        '</div>'
    );
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
    const timer = typeof opts.timer === 'number' ? opts.timer : 4000;

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
            htmlContainer: 'swal2-client-auth-toast-html',
            timerProgressBar: 'swal2-client-auth-toast-progress',
        },
    });

    await Toast.fire({
        icon: false,
        html: cf4AuthToastHtml({
            kind,
            authIcon,
            displayName: opts.displayName,
            title: opts.title,
            text: opts.text,
        }),
    });

    if (opts.thenUrl) {
        window.location.href = opts.thenUrl;
    }
}

if (typeof window !== 'undefined') {
    window.cf4AuthWelcomeToast = cf4AuthWelcomeToast;
}
