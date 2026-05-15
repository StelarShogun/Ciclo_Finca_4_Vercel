/**
 * Client auth welcome / logout toasts (SweetAlert2).
 * Depends on SweetAlert2 (global Swal). Loaded from the client layout Vite bundle.
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

/**
 * @param {object} opts
 * @param {'welcome'|'logout'} [opts.kind]
 * @param {'google'|'user'|'signout'} [opts.authIcon]
 * @param {string} [opts.displayName]
 * @param {string} [opts.title]
 * @param {string} [opts.text]
 * @param {number} [opts.timer]
 * @param {string} [opts.thenUrl]  If set, navigate after the toast closes.
 */
function cf4AuthWelcomeToast(opts) {
    opts = opts || {};
    if (typeof Swal === 'undefined') {
        if (opts.thenUrl) {
            window.location.href = opts.thenUrl;
        }
        return Promise.resolve();
    }

    var kind = opts.kind || 'welcome';
    var authIcon = opts.authIcon || (kind === 'logout' ? 'signout' : 'user');
    var displayName = (opts.displayName || '').trim();
    var timer = typeof opts.timer === 'number' ? opts.timer : 4000;

    var title;
    var text = opts.text || '';

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

    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: function () {
            var popup = Swal.getPopup();
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

    return Toast.fire({
        icon: false,
        iconHtml: cf4AuthIconHtml(authIcon),
        title: title,
        text: text,
    }).then(function () {
        if (opts.thenUrl) {
            window.location.href = opts.thenUrl;
        }
    });
}

if (typeof window !== 'undefined') {
    window.cf4AuthWelcomeToast = cf4AuthWelcomeToast;
}
