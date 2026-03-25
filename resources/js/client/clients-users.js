// ============================================================
// auth.js — Scripts consolidados para registro, login y verificación
// ============================================================

// ----------------------------------------------------------------
// UTILIDADES COMPARTIDAS
// ----------------------------------------------------------------
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function showMsg(msgId, type, text) {
    const el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

function clearMsg(msgId) {
    const el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

function setInputState(input, state) {
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ----------------------------------------------------------------
// PÁGINA: REGISTRO (create.blade.php)
// ----------------------------------------------------------------
(function initRegistro() {
    const formRegistro = document.getElementById('formRegistroCliente');
    if (!formRegistro) return;

    const invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',          required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',         required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido', required: false },
    ].forEach(function(field) {
        const input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function() {
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

        input.addEventListener('blur', function() {
            if (field.required && this.value.trim() === '') {
                showMsg(field.msgId, 'error', field.label + ' es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    });

    // Gmail
    const gmailInput = document.getElementById('gmail');
    if (gmailInput) {
        gmailInput.addEventListener('input', function() {
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
        gmailInput.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
                setInputState(this, 'input-error');
            }
        });
    }

    // Contraseñas
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
        passInput.addEventListener('input', function() {
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

    // Envío del formulario
    formRegistro.addEventListener('submit', function(e) {
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

        document.getElementById('btnRegistrarTexto').style.display = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled = true;
    });
})();

// ----------------------------------------------------------------
// PÁGINA: LOGIN (login.blade.php)
// ----------------------------------------------------------------
(function initLogin() {
    // Toggle ojo contraseña
    const toggleBtn = document.getElementById('toggle-password');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const input = document.getElementById('login-password');
            const icon  = document.getElementById('eye-icon');
            if (!input || !icon) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Submit: muestra estado de carga y deshabilita el botón
    const form        = document.getElementById('public-login-form');
    const submitBtn   = document.getElementById('login-submit-btn');

    if (form && submitBtn) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            const icon       = submitBtn.querySelector('i');
            const normalSpan = submitBtn.querySelector('span:not([id])');
            if (icon)       icon.className = 'fas fa-spinner fa-spin';
            if (normalSpan) normalSpan.textContent = 'Iniciando sesión...';
        });
    }
})();

// ----------------------------------------------------------------
// PÁGINA: VERIFICACIÓN DE CÓDIGO (verify_gmail_code.blade.php)
// ----------------------------------------------------------------
(function initVerificacion() {
    const codeInput   = document.getElementById('verification_code');
    const formVerificar = document.getElementById('formVerificar');
    if (!codeInput || !formVerificar) return;

    codeInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    formVerificar.addEventListener('submit', function(e) {
        const code = codeInput.value.trim();
        const err  = document.getElementById('code-error');

        if (code.length !== 6) {
            err.style.display = 'block';
            codeInput.style.borderColor = '#e74c3c';
            e.preventDefault();
            return;
        }

        err.style.display = 'none';
        codeInput.style.borderColor = '#dadce0';

        document.getElementById('btnVerificarTexto').style.display = 'none';
        document.getElementById('btnVerificarCargando').style.display = 'inline';
        document.getElementById('btnVerificar').disabled = true;
    });
})();

// ----------------------------------------------------------------
// PÁGINA: MI PERFIL (profile.blade.php)
// ----------------------------------------------------------------

var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

/** Guarda los valores actuales para que cancelEdit pueda restaurarlos. */
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

/** Habilita los campos del formulario y cambia el botón al modo guardar. */
function enableEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.removeAttribute('readonly');
    });

    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        btn.className = 'btn btn-sm btn-primary';
        btn.setAttribute('onclick', 'submitProfile()');
    }

    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.remove('hidden');

    var nameField = document.getElementById('name');
    if (nameField) nameField.focus();
}

/** Restaura los valores originales y vuelve al modo solo lectura. */
function cancelEdit() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.setAttribute('readonly', true);
            el.value = profileOriginalValues[id];
        }
    });

    var btn = document.getElementById('btnEditarPerfil');
    if (btn) {
        btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
        btn.className = 'btn btn-sm btn-outline-primary';
        btn.setAttribute('onclick', 'enableEdit()');
    }

    var actions = document.getElementById('accionesEdicion');
    if (actions) actions.classList.add('hidden');
}

/** Muestra confirmación y luego envía el formulario de perfil vía AJAX. */
function submitProfile() {
    var form = document.getElementById('formPerfil');
    if (!form) return;

    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán tus datos personales.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> Sí, guardar',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;
        sendProfile(form);
    });
}

/** Realiza la petición fetch del perfil (llamada tras la confirmación). */
function sendProfile(form) {
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (res.success) {
                profileEditableFields.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) {
                        profileOriginalValues[id] = el.value;
                        el.setAttribute('readonly', true);
                    }
                });

                var name   = (document.getElementById('name') || {}).value || '';
                var fs     = (document.getElementById('first_surname') || {}).value || '';
                var ss     = (document.getElementById('second_surname') || {}).value || '';
                var gmail  = (document.getElementById('gmail') || {}).value || '';

                var heroName  = document.getElementById('heroName');
                var initials  = document.getElementById('avatarInitials');
                var heroEmail = document.querySelector('.profile-email');
                if (heroName)  heroName.textContent  = [name, fs, ss].filter(Boolean).join(' ');
                if (initials)  initials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (heroEmail) heroEmail.textContent = gmail;

                var headerInitials  = document.querySelector('.user-avatar-bubble');
                var headerShortName = document.querySelector('.user-trigger-name');
                var headerFullName  = document.querySelector('.user-dropdown-fullname');
                var headerEmail     = document.querySelector('.user-dropdown-email');
                if (headerInitials)  headerInitials.textContent  = (name.charAt(0) + fs.charAt(0)).toUpperCase();
                if (headerShortName) headerShortName.textContent = name;
                if (headerFullName)  headerFullName.textContent  = [name, fs].filter(Boolean).join(' ');
                if (headerEmail)     headerEmail.textContent     = gmail;

                var btn = document.getElementById('btnEditarPerfil');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Editar Perfil';
                    btn.className = 'btn btn-sm btn-outline-primary';
                    btn.setAttribute('onclick', 'enableEdit()');
                }
                var actions = document.getElementById('accionesEdicion');
                if (actions) actions.classList.add('hidden');

                showProfileAlert(res.message || 'Cambios guardados correctamente', 'success');
            } else {
                showProfileAlert(res.message || 'Error al guardar los cambios.', 'danger');
            }
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        });
}

/** Alterna visibilidad de un campo contraseña (usado en el perfil). */
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn ? btn.querySelector('i') : null;
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        if (icon) icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/** Actualiza la barra de fortaleza de la contraseña. */
function updateStrength(val) {
    var wrapper = document.getElementById('passStrength');
    var fill    = document.getElementById('strengthFill');
    var label   = document.getElementById('strengthLabel');
    if (!wrapper) return;
    if (!val) { wrapper.classList.add('hidden'); return; }
    wrapper.classList.remove('hidden');

    var score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    var levels = [
        { w: '25%',  c: '#d32f2f', t: 'Débil'   },
        { w: '50%',  c: '#f57c00', t: 'Regular'  },
        { w: '75%',  c: '#fbc02d', t: 'Buena'    },
        { w: '100%', c: '#2e7d32', t: 'Fuerte'   }
    ];
    var lvl = levels[Math.max(score - 1, 0)];
    if (fill)  { fill.style.width = lvl.w; fill.style.background = lvl.c; }
    if (label) { label.textContent = lvl.t; label.style.color = lvl.c; }
}

/** Muestra el formulario de contraseña para cuentas Google. */
function showPasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.remove('hidden');
    if (cta)  cta.classList.add('hidden');
}

/** Oculta el formulario de contraseña y vuelve a mostrar el CTA. */
function hidePasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.add('hidden');
    if (cta)  cta.classList.remove('hidden');
}

/** Muestra una alerta en el perfil; se cierra sola a los 5 s. */
function showProfileAlert(msg, tipo) {
    var alertEl = document.getElementById('profileAlert');
    var textEl  = document.getElementById('profileAlertText');
    var iconEl  = document.getElementById('profileAlertIcon');
    if (!alertEl) return;
    textEl.textContent = msg;
    alertEl.className  = 'alert ' + (tipo === 'danger' ? 'alert-danger' : 'alert-success');
    iconEl.className   = tipo === 'danger' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
    alertEl.classList.remove('hidden');
    alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    clearTimeout(alertEl.profileTimeout);
    alertEl.profileTimeout = setTimeout(closeProfileAlert, 5000);
}

/** Cierra la alerta del perfil. */
function closeProfileAlert() {
    var alertEl = document.getElementById('profileAlert');
    if (alertEl) alertEl.classList.add('hidden');
}

/** Realiza la petición fetch de cambio/definición de contraseña. */
function sendPassword(form) {
    var submitBtn       = form.querySelector('button[type="submit"]');
    var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: new FormData(form)
    })
        .then(function (r) {
            if (r.status === 422) {
                return r.json().then(function (data) {
                    var errors   = data.errors || {};
                    var firstMsg = Object.values(errors)[0];
                    showProfileAlert(
                        Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.'),
                        'danger'
                    );
                    return Promise.reject('validation');
                });
            }
            return r.json();
        })
        .then(function (res) {
            if (!res.success) {
                showProfileAlert(res.message || 'Error al actualizar la contraseña.', 'danger');
                return;
            }

            form.reset();
            updateStrength('');

            if (res.provider_changed) {
                var cta       = document.getElementById('googlePassCta');
                var cancelBtn = form.querySelector('.btn-secondary');
                if (cta)       cta.classList.add('hidden');
                if (cancelBtn) cancelBtn.remove();

                var heroBadge = document.querySelector('.profile-badge');
                if (heroBadge) {
                    heroBadge.className = 'profile-badge profile-badge--local';
                    heroBadge.innerHTML = '<i class="fas fa-envelope"></i> Cuenta local';
                }

                var fieldsDiv = form.querySelector('.profile-fields');
                if (fieldsDiv && !document.getElementById('currentPassGroup')) {
                    var currentGroup = document.createElement('div');
                    currentGroup.id        = 'currentPassGroup';
                    currentGroup.className = 'form-group profile-field-full';
                    currentGroup.innerHTML =
                        '<label for="current_password">Contraseña Actual</label>' +
                        '<div class="profile-input-pass">' +
                        '<input type="password" id="current_password" name="current_password"' +
                        ' class="form-control" placeholder="Tu contraseña actual"' +
                        ' autocomplete="current-password">' +
                        '<button type="button" class="profile-toggle-pass"' +
                        ' onclick="togglePassword(\'current_password\', this)">' +
                        '<i class="fas fa-eye"></i>' +
                        '</button>' +
                        '</div>';
                    fieldsDiv.insertBefore(currentGroup, fieldsDiv.firstChild);
                }

                var title   = document.getElementById('passwordCardTitle');
                var saveBtn = document.getElementById('btnSavePassword');
                if (title)   title.textContent = 'Cambiar Contraseña';
                if (saveBtn) saveBtn.innerHTML  = '<i class="fas fa-save"></i> Actualizar Contraseña';

                form.classList.remove('hidden');
            }

            showProfileAlert(res.message || 'Contraseña actualizada correctamente.', 'success');
        })
        .catch(function (err) {
            if (err === 'validation') return;
            showProfileAlert('Error de conexión. Intenta de nuevo.', 'danger');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled  = false;
                var isNowLocal      = !!document.getElementById('currentPassGroup');
                submitBtn.innerHTML = '<i class="fas fa-save"></i> ' +
                    (isNowLocal ? 'Actualizar Contraseña' : 'Guardar Contraseña');
            }
        });
}

// Inicialización del perfil al cargar el DOM
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('formPerfil')) {
        profileSaveOriginals();

        var passInput = document.getElementById('new_password');
        if (passInput) {
            passInput.addEventListener('input', function () {
                updateStrength(this.value);
            });
        }

        // Mensajes flash pasados desde Blade via window.__profileFlash
        var flash = window.__profileFlash || {};
        if (flash.profile_updated)  showProfileAlert('Cambios guardados correctamente.', 'success');
        if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
        if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');
    }

    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function (e) {
            e.preventDefault();

            var isGoogleOnly = !!document.getElementById('googlePassCta');
            var confirmMsg   = isGoogleOnly
                ? 'Se definirá una contraseña para tu cuenta. Podrás iniciar sesión con correo y contraseña.'
                : 'Se actualizará la contraseña de tu cuenta.';
            var confirmBtn   = isGoogleOnly
                ? '<i class="fas fa-key"></i> Sí, definir'
                : '<i class="fas fa-save"></i> Sí, actualizar';

            Swal.fire({
                title: '¿Confirmar cambio de contraseña?',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmBtn,
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) return;
                sendPassword(formPassword);
            });
        });
    }
});

// Exportaciones globales (usadas desde atributos onclick en el HTML)
window.enableEdit        = enableEdit;
window.cancelEdit        = cancelEdit;
window.submitProfile     = submitProfile;
window.togglePassword    = togglePassword;
window.showPasswordForm  = showPasswordForm;
window.hidePasswordForm  = hidePasswordForm;
window.showProfileAlert  = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;