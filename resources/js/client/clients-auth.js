import { cf4Toast, cf4Confirm, cf4Error, escapeHtml } from './swal.js';

// ----------------------------------------------------------------
// SHARED UTILITIES
// ----------------------------------------------------------------

// Retrieves CSRF token from meta tag or hidden input.
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

// Displays a success or error message inside a designated element.
function showMsg(msgId, type, text) {
    const el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

// Clears any validation message from the element.
function clearMsg(msgId) {
    const el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

// Adds or removes error/success CSS classes on an input.
function setInputState(input, state) {
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ----------------------------------------------------------------
// TOGGLE PASSWORD VISIBILITY
// ----------------------------------------------------------------

// Toggles a password field between text and password types (by IDs).
function togglePass(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
    } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    }
}

// ----------------------------------------------------------------
// USER DROPDOWN MENU (header)
// ----------------------------------------------------------------

// Sets the user menu open/closed state and updates ARIA attributes.
function setUserMenuOpen(open) {
    var wrap    = document.getElementById('user-menu');
    var panel   = document.getElementById('user-dropdown');
    var trigger = document.getElementById('user-menu-trigger');
    if (!wrap) return;
    wrap.classList.toggle('open', open);
    if (panel)   panel.setAttribute('aria-hidden', String(!open));
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
}

function toggleUserDropdown() {
    var wrap   = document.getElementById('user-menu');
    var isOpen = wrap ? wrap.classList.contains('open') : false;
    setUserMenuOpen(!isOpen);
}

// ----------------------------------------------------------------
// PAGE REGISTER
// ----------------------------------------------------------------
(function initRegistro() {
    const formRegistro = document.getElementById('formRegistroCliente');
    if (!formRegistro) return;

    const invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    // Live validation for name and surname fields (letters only, min length).
    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',          required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',         required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido', required: false },
    ].forEach(function (field) {
        const input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function () {
            if (invalidChars.test(this.value)) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
                showMsg(field.msgId, 'error', 'Solo se permiten letras y espacios, sin números ni símbolos.');
                setInputState(this, 'input-error');
                return;
            }
            const val = this.value.trim();
            if (val === '' && field.required) {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            } else if (val !== '' && val.length < 2) {
                showMsg(field.msgId, 'error', field.label + ' debe tener al menos 2 caracteres.');
                setInputState(this, 'input-error');
            } else if (val !== '') {
                showMsg(field.msgId, 'success', 'Campo válido.');
                setInputState(this, 'input-ok');
            } else {
                clearMsg(field.msgId);
                setInputState(this, null);
            }
        });

        input.addEventListener('blur', function () {
            if (field.required && this.value.trim() === '') {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    });

    // Gmail validation: only @gmail.com addresses allowed.
    const gmailInput = document.getElementById('gmail');
    if (gmailInput) {
        gmailInput.addEventListener('input', function () {
            const val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-gmail'); setInputState(this, null); return; }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-gmail', 'success', 'Correo válido.');
                setInputState(this, 'input-ok');
            }
        });
        gmailInput.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    function checkPassMatch() {
        const p       = document.getElementById('password').value;
        const pc      = document.getElementById('password_confirmation').value;
        const pcInput = document.getElementById('password_confirmation');
        if (pc.length === 0) { clearMsg('msg-pass-confirm'); setInputState(pcInput, null); return; }
        if (p !== pc) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcInput, 'input-error');
        } else {
            showMsg('msg-pass-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(pcInput, 'input-ok');
        }
    }

    const passInput = document.getElementById('password');
    if (passInput) {
        passInput.addEventListener('input', function () {
            const v = this.value;
            if (v.length === 0)    { clearMsg('msg-pass'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkPassMatch();
        });
    }

    const passConfirmInput = document.getElementById('password_confirmation');
    if (passConfirmInput) {
        passConfirmInput.addEventListener('input', checkPassMatch);
    }

    // Final validation before submit; shows loading state.
    formRegistro.addEventListener('submit', function (e) {
        let valid = true;

        const nameVal = document.getElementById('name');
        if (nameVal && nameVal.value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(nameVal, 'input-error');
            valid = false;
        }

        const surnameVal = document.getElementById('first_surname');
        if (surnameVal && surnameVal.value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(surnameVal, 'input-error');
            valid = false;
        }

        const gv = document.getElementById('gmail');
        if (gv) {
            const gvVal = gv.value.trim().toLowerCase();
            if (gvVal === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(gv, 'input-error');
                valid = false;
            } else if (!gvVal.endsWith('@gmail.com')) {
                showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(gv, 'input-error');
                valid = false;
            }
        }

        const pv  = passInput ? passInput.value : '';
        const pcv = passConfirmInput ? passConfirmInput.value : '';

        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(passInput, 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(passInput, 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(passConfirmInput, 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(passConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        document.getElementById('btnRegistrarTexto').style.display   = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled              = true;
    });
})();

// ----------------------------------------------------------------
// PAGE VERIFICATION EMAIL
// ----------------------------------------------------------------
(function initVerificacion() {
    const codeInput     = document.getElementById('verification_code');
    const formVerificar = document.getElementById('formVerificar');
    if (!codeInput || !formVerificar) return;

    // Restrict input to 6 digits.
    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    formVerificar.addEventListener('submit', function (e) {
        const code = codeInput.value.trim();
        const err  = document.getElementById('code-error');

        if (code.length !== 6) {
            err.style.display           = 'block';
            codeInput.style.borderColor = '#e74c3c';
            e.preventDefault();
            return;
        }

        err.style.display           = 'none';
        codeInput.style.borderColor = '#dadce0';

        document.getElementById('btnVerificarTexto').style.display   = 'none';
        document.getElementById('btnVerificarCargando').style.display = 'inline';
        document.getElementById('btnVerificar').disabled              = true;
    });
})();

// ----------------------------------------------------------------
// PAGE RECOVERY PASSWORD
// ----------------------------------------------------------------
(function initRecovery() {
    const formRecovery = document.getElementById('formRecovery');
    if (!formRecovery) return;

    // Password toggle buttons.
    const togglePassBtn    = document.getElementById('toggle-recovery-password');
    const toggleConfirmBtn = document.getElementById('toggle-recovery-confirm');
    if (togglePassBtn) {
        togglePassBtn.addEventListener('click', function () {
            togglePass('recovery-password', 'eye-recovery-password');
        });
    }
    if (toggleConfirmBtn) {
        toggleConfirmBtn.addEventListener('click', function () {
            togglePass('recovery-password-confirm', 'eye-recovery-confirm');
        });
    }

    // Gmail validation for recovery email.
    const recEmailInput = document.getElementById('recovery-email');
    if (recEmailInput) {
        recEmailInput.addEventListener('input', function () {
            const val = this.value.trim().toLowerCase();
            if (val === '') {
                clearMsg('msg-recovery-email');
                setInputState(this, null);
                return;
            }
            if (!val.endsWith('@gmail.com')) {
                showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
                setInputState(this, 'input-error');
            } else {
                clearMsg('msg-recovery-email');
                setInputState(this, 'input-ok');
            }
        });
        recEmailInput.addEventListener('blur', function () {
            if (this.value.trim() === '') {
                showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    // Password length indicator.
    const recPassInput = document.getElementById('recovery-password');
    if (recPassInput) {
        recPassInput.addEventListener('input', function () {
            const v = this.value;
            if (v.length === 0) {
                clearMsg('msg-recovery-password');
                setInputState(this, null);
            } else if (v.length < 8) {
                showMsg('msg-recovery-password', 'error', `Mínimo 8 caracteres (${v.length}/8).`);
                setInputState(this, 'input-error');
            } else {
                showMsg('msg-recovery-password', 'success', 'Longitud correcta.');
                setInputState(this, 'input-ok');
            }
            checkRecoveryMatch();
        });
    }

    function checkRecoveryMatch() {
        const passEl    = document.getElementById('recovery-password');
        const confirmEl = document.getElementById('recovery-password-confirm');
        if (!passEl || !confirmEl) return;
        const p  = passEl.value;
        const pc = confirmEl.value;
        if (pc.length === 0) {
            clearMsg('msg-recovery-confirm');
            setInputState(confirmEl, null);
            return;
        }
        if (p !== pc) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(confirmEl, 'input-error');
        } else {
            showMsg('msg-recovery-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(confirmEl, 'input-ok');
        }
    }

    const recConfirmInput = document.getElementById('recovery-password-confirm');
    if (recConfirmInput) {
        recConfirmInput.addEventListener('input', checkRecoveryMatch);
    }

    // Final validation before submitting recovery form.
    formRecovery.addEventListener('submit', function (e) {
        let valid = true;

        const emailVal = recEmailInput ? recEmailInput.value.trim().toLowerCase() : '';
        if (!emailVal) {
            showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
            if (recEmailInput) setInputState(recEmailInput, 'input-error');
            valid = false;
        } else if (!emailVal.endsWith('@gmail.com')) {
            showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
            if (recEmailInput) setInputState(recEmailInput, 'input-error');
            valid = false;
        }

        const passVal = recPassInput ? recPassInput.value : '';
        if (passVal.length === 0) {
            showMsg('msg-recovery-password', 'error', 'La contraseña es obligatoria.');
            if (recPassInput) setInputState(recPassInput, 'input-error');
            valid = false;
        } else if (passVal.length < 8) {
            showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres.');
            if (recPassInput) setInputState(recPassInput, 'input-error');
            valid = false;
        }

        const confVal = recConfirmInput ? recConfirmInput.value : '';
        if (confVal.length === 0) {
            showMsg('msg-recovery-confirm', 'error', 'Debes confirmar la contraseña.');
            if (recConfirmInput) setInputState(recConfirmInput, 'input-error');
            valid = false;
        } else if (passVal !== confVal) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            if (recConfirmInput) setInputState(recConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            return;
        }

        const btn         = document.getElementById('btnRecovery');
        const btnTexto    = document.getElementById('btnRecoveryTexto');
        const btnCargando = document.getElementById('btnRecoveryCargando');
        if (btn)         btn.disabled = true;
        if (btnTexto)    btnTexto.style.display = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
    });
})();

// ----------------------------------------------------------------
// GENERAL INITIALIZATION (DOMContentLoaded)
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // User dropdown: clean event listeners and attach new one.
    var userMenuTrigger = document.getElementById('user-menu-trigger');
    if (userMenuTrigger) {
        userMenuTrigger.replaceWith(userMenuTrigger.cloneNode(true));
        userMenuTrigger = document.getElementById('user-menu-trigger');
        userMenuTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserDropdown();
        });
    }

    // Close user dropdown when clicking outside.
    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('user-menu');
        if (wrap && !wrap.contains(e.target)) setUserMenuOpen(false);
    });

    // Password toggle on login page.
    var togglePasswordBtn = document.getElementById('toggle-password');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            togglePass('login-password', 'eye-icon');
        });
    }

    // AJAX login: handle 419 (CSRF expiry) and redirect on success.
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            if (!csrfToken) { window.location.href = '/login?session_expired=1'; return; }

            var formData  = new FormData(this);
            var submitBtn = document.getElementById('login-submit-btn');

            if (submitBtn) {
                submitBtn.disabled = true;
                var icon       = submitBtn.querySelector('i');
                var normalSpan = submitBtn.querySelector('span');
                if (icon)       icon.className         = 'fas fa-spinner fa-spin';
                if (normalSpan) normalSpan.textContent = 'Iniciando sesión...';
            }

            fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    if (response.status === 419) {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('csrf');
                    }
                    return response.json().catch(function () {
                        window.location.href = '/login?session_expired=1';
                        return Promise.reject('parse');
                    });
                })
                .then(function (data) {
                    if (data.success) {
                        void cf4Toast({
                            icon: 'success',
                            title: '¡Bienvenido!',
                            text: data.message || 'Inicio de sesión exitoso',
                            timer: 1500,
                        }).then(function () { window.location.href = data.redirect || '/'; });
                    } else {
                        cf4Confirm({
                            icon: 'error',
                            title: 'Error',
                            html: escapeHtml(data.message || 'Error al iniciar sesión') +
                                '<hr style="margin:12px 0">' +
                                '<p style="font-size:0.9rem;margin:0">¿Tienes una cuenta registrada? ¿O deseas registrarte?</p>',
                            confirmButtonText: 'Ir a Registro',
                            cancelButtonText: 'Cancelar',
                        }).then(function (result) {
                            if (result.isConfirmed) window.location.href = '/register';
                        });
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            var icon       = submitBtn.querySelector('i');
                            var normalSpan = submitBtn.querySelector('span');
                            if (icon)       icon.className         = 'fas fa-sign-in-alt';
                            if (normalSpan) normalSpan.textContent = 'Iniciar Sesión';
                        }
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    void cf4Error('Ocurrió un error al iniciar sesión', 'Error');
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

}); // end DOMContentLoaded

// ----------------------------------------------------------------
// GLOBAL EXPORTS (called from onclick attributes in Blade templates)
// ----------------------------------------------------------------
window.togglePass = togglePass;