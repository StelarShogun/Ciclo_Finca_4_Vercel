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

// ----------------------------------------------------------------
// TOGGLE PASSWORD VISIBILITY
// ----------------------------------------------------------------

// Toggles a password field using a button element reference.
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
// PROFILE PAGE
// ----------------------------------------------------------------

var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

// Stores current field values to allow cancellation of edits.
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

// Enables profile fields for editing and changes the button to save mode.
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

// Cancels editing and restores original field values.
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

// Shows confirmation dialog before submitting the profile form.
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

// Sends profile data via AJAX and updates UI on success.
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
            if (res.success) {
                profileEditableFields.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) { profileOriginalValues[id] = el.value; el.setAttribute('readonly', true); }
                });

                var name  = (document.getElementById('name')          || {}).value || '';
                var fs    = (document.getElementById('first_surname')  || {}).value || '';
                var ss    = (document.getElementById('second_surname') || {}).value || '';
                var gmail = (document.getElementById('gmail')          || {}).value || '';

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

// Evaluates password strength and updates the visual meter.
function updateStrength(val) {
    var wrapper = document.getElementById('passStrength');
    var fill    = document.getElementById('strengthFill');
    var label   = document.getElementById('strengthLabel');
    if (!wrapper) return;
    if (!val) { wrapper.classList.add('hidden'); return; }
    wrapper.classList.remove('hidden');

    var score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { w: '25%',  c: '#d32f2f', t: 'Débil'  },
        { w: '50%',  c: '#f57c00', t: 'Regular' },
        { w: '75%',  c: '#fbc02d', t: 'Buena'   },
        { w: '100%', c: '#235347', t: 'Fuerte'  }
    ];
    var lvl = levels[Math.max(score - 1, 0)];
    if (fill)  { fill.style.width = lvl.w; fill.style.background = lvl.c; }
    if (label) { label.textContent = lvl.t; label.style.color = lvl.c; }
}

// Shows the password change form (hides the Google-only CTA).
function showPasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.remove('hidden');
    if (cta)  cta.classList.add('hidden');
}

function hidePasswordForm() {
    var form = document.getElementById('formPassword');
    var cta  = document.getElementById('googlePassCta');
    if (form) form.classList.add('hidden');
    if (cta)  cta.classList.remove('hidden');
}

// Displays a dismissible alert on the profile page.
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

function closeProfileAlert() {
    var alertEl = document.getElementById('profileAlert');
    if (alertEl) alertEl.classList.add('hidden');
}

// Sends password change request; handles Google-only account transition.
function sendPassword(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled  = true;
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
                    var currentGroup       = document.createElement('div');
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

    // Store original values and wire password strength meter.
    profileSaveOriginals();

    var passInputProfile = document.getElementById('new_password');
    if (passInputProfile) {
        passInputProfile.addEventListener('input', function () { updateStrength(this.value); });
    }

    var flash = window.__profileFlash || {};
    if (flash.profile_updated)  showProfileAlert('Cambios guardados correctamente.', 'success');
    if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
    if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');

    // Password change form: confirm and handle Google-only accounts.
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
                text: confirmMsg, icon: 'warning',
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

}); // end DOMContentLoaded

// ----------------------------------------------------------------
// GLOBAL EXPORTS (called from onclick attributes in Blade templates)
// ----------------------------------------------------------------
window.togglePassword    = togglePassword;
window.enableEdit        = enableEdit;
window.cancelEdit        = cancelEdit;
window.submitProfile     = submitProfile;
window.showPasswordForm  = showPasswordForm;
window.hidePasswordForm  = hidePasswordForm;
window.showProfileAlert  = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;