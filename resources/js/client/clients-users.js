import '../shared/admin-table-responsive.js';
import {
    fireSwal,
    cf4Confirm,
    cf4Toast,
    cf4Error,
    cf4Warning,
    escapeHtml,
} from './swal.js';
import { initCartInteractions } from './cart-actions.js';
import { addToCart as addToCartShared } from './cart-shared.js';
import {
    buildCf4CheckoutSuccessText,
    getCf4PaymentMethodShortLabel,
} from './checkout-copy.js';
import './auth-welcome-toast.js';

// ============================================================
// GLOBAL UTILITIES
// ============================================================

/** Returns the CSRF token from the meta tag or a hidden form input. */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

function isClientStockShortMessage(msg) {
    return msg === 'Producto agotado' || msg === 'Stock insuficiente';
}

function getCheckoutPaymentMethodFallback() {
    var selected = document.querySelector('input[name="checkout_payment_method"]:checked');
    return selected ? selected.value : 'cash';
}

// ============================================================
// CART COUNTER (navbar)
// ============================================================

function updateCartCount(count) {
    const cartCountEl = document.getElementById('cart-count');
    const cartLinkEl  = document.getElementById('cart-link');

    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'flex' : 'none';
    }
    if (cartLinkEl) {
        cartLinkEl.setAttribute('data-cart-count', count);
    }
}

// Invoice badge polling lives in clients-invoice-heartbeat.js (loaded from clients-header.js on idle).

// ============================================================
// ADD TO CART
// ============================================================

function addToCart(productId, quantity, triggerBtn) {
    quantity = quantity || 1;

    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        },
        body: JSON.stringify({ product_id: productId, quantity: quantity })
    })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.cart_count);
                fireSwal({
                    icon: 'success',
                    title: '¡Agregado!',
                    text: data.message || 'Producto agregado al carrito',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                var msg = data.message || 'No se pudo agregar el producto al carrito';
                var stockShort = isClientStockShortMessage(msg);
                fireSwal({
                    icon: stockShort ? 'warning' : 'error',
                    title: stockShort ? msg : 'Error',
                    text: stockShort ? '' : msg
                });
            }
        })
        .catch(function (err) {
            console.error('Error adding to cart:', err);
            fireSwal({ icon: 'error', title: 'Error', text: 'Ocurrió un error al agregar el producto al carrito' });
        });
}

// ============================================================
// MODAL HELPERS
// ============================================================

function openModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    var modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// ============================================================
// TOGGLE PASSWORD VISIBILITY
// ============================================================

/** Toggles a password field between text and password types (by IDs). */
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

/** Toggles a password field using a button element reference. */
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

// ============================================================
// FIELD MESSAGE UTILITIES (register & recovery forms)
// ============================================================

/** Shows a validation message below an input field. */
function showMsg(msgId, type, text) {
    var el = document.getElementById(msgId);
    if (!el) return;
    el.className = 'field-msg ' + type;
    el.innerHTML = (type === 'error')
        ? '<i class="fas fa-exclamation-circle"></i><span>' + text + '</span>'
        : '<i class="fas fa-check-circle"></i><span>' + text + '</span>';
}

/** Clears a field-level message. */
function clearMsg(msgId) {
    var el = document.getElementById(msgId);
    if (el) { el.className = 'field-msg'; el.innerHTML = ''; }
}

/** Adds or removes input-error / input-ok CSS class from an input. */
function setInputState(input, state) {
    if (!input) return;
    input.classList.remove('input-error', 'input-ok');
    if (state) input.classList.add(state);
}

// ============================================================
// PROFILE PAGE
// ============================================================

var profileOriginalValues = {};
var profileEditableFields = ['name', 'first_surname', 'second_surname', 'gmail'];

/** Stores current field values to allow cancellation of edits. */
function profileSaveOriginals() {
    profileEditableFields.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) profileOriginalValues[id] = el.value;
    });
}

/** Enables profile fields for editing and changes the button to save mode. */
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

/** Cancels editing and restores original field values. */
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

/** Shows confirmation dialog before submitting the profile form. */
async function submitProfile() {
    var form = document.getElementById('formPerfil');
    if (!form) return;

    const result = await cf4Confirm({
        title: '¿Guardar cambios?',
        text: 'Se actualizarán tus datos personales.',
        icon: 'question',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    sendProfile(form);
}

/** Sends profile data via AJAX and updates UI on success. */
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

                var name  = (document.getElementById('name')           || {}).value || '';
                var fs    = (document.getElementById('first_surname')   || {}).value || '';
                var ss    = (document.getElementById('second_surname')  || {}).value || '';
                var gmail = (document.getElementById('gmail')           || {}).value || '';

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

/** Evaluates password strength and updates the visual meter. */
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

/** Shows the password change form (hides the Google-only CTA). */
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

/** Displays a dismissible alert on the profile page. */
function showProfileAlert(msg, tipo) {
    if (tipo === 'danger') {
        void cf4Error(msg, 'No se pudo completar');
    } else {
        void cf4Toast({
            icon: 'success',
            title: 'Listo',
            text: msg,
            timer: 3000,
        });
    }

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

/** Sends password change request; handles Google-only account transition. */
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

// ============================================================
// REGISTER FORM (register.blade.php)
// ============================================================

(function initRegistro() {
    var formRegistro = document.getElementById('formRegistroCliente');
    if (!formRegistro) return;

    var invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;

    // Live validation for name and surname fields (letters only, min length).
    [
        { id: 'name',           msgId: 'msg-name',           label: 'El nombre',          required: true  },
        { id: 'first_surname',  msgId: 'msg-first-surname',  label: 'El apellido',         required: true  },
        { id: 'second_surname', msgId: 'msg-second-surname', label: 'El segundo apellido', required: false },
    ].forEach(function (field) {
        var input = document.getElementById(field.id);
        if (!input) return;

        input.addEventListener('input', function () {
            if (invalidChars.test(this.value)) {
                this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
                showMsg(field.msgId, 'error', 'Solo se permiten letras y espacios, sin números ni símbolos.');
                setInputState(this, 'input-error');
                return;
            }
            var val = this.value.trim();
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
    var gmailInput = document.getElementById('gmail');
    if (gmailInput) {
        gmailInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
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

    // Password length + confirmation match.
    function checkPassMatch() {
        var passwordEl = document.getElementById('password');
        var pcInput    = document.getElementById('password_confirmation');
        if (!passwordEl || !pcInput) return;
        var p  = passwordEl.value;
        var pc = pcInput.value;
        if (pc.length === 0) { clearMsg('msg-pass-confirm'); setInputState(pcInput, null); return; }
        if (p !== pc) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcInput, 'input-error');
        } else {
            showMsg('msg-pass-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(pcInput, 'input-ok');
        }
    }

    var passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-pass'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkPassMatch();
        });
    }

    var passConfirmInput = document.getElementById('password_confirmation');
    if (passConfirmInput) passConfirmInput.addEventListener('input', checkPassMatch);

    // Final validation before submit; shows loading state.
    formRegistro.addEventListener('submit', function (e) {
        var valid = true;

        var nameEl = document.getElementById('name');
        if (nameEl && nameEl.value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(nameEl, 'input-error');
            valid = false;
        }
        var fsEl = document.getElementById('first_surname');
        if (fsEl && fsEl.value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(fsEl, 'input-error');
            valid = false;
        }

        var gv = gmailInput ? gmailInput.value.trim().toLowerCase() : '';
        if (gv === '') {
            showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
            setInputState(gmailInput, 'input-error');
            valid = false;
        } else if (!gv.endsWith('@gmail.com')) {
            showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(gmailInput, 'input-error');
            valid = false;
        }

        var pv   = passwordInput ? passwordInput.value : '';
        var pcEl = document.getElementById('password_confirmation');
        var pcv  = pcEl ? pcEl.value : '';

        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(passwordInput, 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(passwordInput, 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(pcEl, 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(pcEl, 'input-error');
            valid = false;
        }

        var termsEl = document.getElementById('accept_terms');
        if (termsEl && !termsEl.checked) {
            showMsg('msg-accept-terms', 'error', 'Debes aceptar los Términos y condiciones y la Política de privacidad.');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        var btnTexto    = document.getElementById('btnRegistrarTexto');
        var btnCargando = document.getElementById('btnRegistrarCargando');
        var btn         = document.getElementById('btnRegistrar');
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
        if (btn)         btn.disabled              = true;
    });
})();

// ============================================================
// VERIFICATION EMAIL PAGE
// ============================================================

(function initVerificacion() {
    var container = document.getElementById('otpInputs');
    var hidden    = document.getElementById('verification_code');
    var form      = document.getElementById('formVerificar');
    if (!container || !hidden || !form) return;

    var boxes = Array.prototype.slice.call(container.querySelectorAll('.otp-box'));
    if (!boxes.length) return;

    var errEl  = document.getElementById('code-error');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var submitting = false;

    function syncHidden() {
        var value = '';
        for (var i = 0; i < boxes.length; i++) value += boxes[i].value;
        hidden.value = value;
        return value;
    }

    function clearError() {
        if (errEl) errEl.style.display = 'none';
        container.classList.remove('is-error');
    }

    function showError() {
        if (errEl) errEl.style.display = 'block';
        container.classList.remove('is-error');
        void container.offsetWidth; // reflow so the shake restarts
        container.classList.add('is-error');
    }

    // Pop animation when a digit lands in a box.
    function markFilled(box) {
        if (box.value) {
            box.classList.remove('is-filled');
            void box.offsetWidth;
            box.classList.add('is-filled');
        } else {
            box.classList.remove('is-filled');
        }
    }

    function focusBox(index) {
        if (index >= 0 && index < boxes.length) {
            boxes[index].focus();
            boxes[index].select();
        }
    }

    function firstEmptyIndex() {
        for (var i = 0; i < boxes.length; i++) {
            if (!boxes[i].value) return i;
        }
        return -1;
    }

    function allFilled() {
        return firstEmptyIndex() === -1;
    }

    // Spread a (pasted / autofilled) string across the boxes from startIndex.
    function distribute(str, startIndex) {
        var digits = (str || '').replace(/\D/g, '').split('');
        var i = startIndex;
        for (var d = 0; d < digits.length && i < boxes.length; d++, i++) {
            boxes[i].value = digits[d];
            markFilled(boxes[i]);
        }
        syncHidden();
        focusBox(Math.min(i, boxes.length - 1));
        maybeAutoSubmit();
    }

    // Mirror the reel's "auto-verify once entered" behaviour.
    function maybeAutoSubmit() {
        if (!submitting && allFilled()) {
            setTimeout(function () {
                if (submitting) return;
                if (typeof form.requestSubmit === 'function') form.requestSubmit();
                else form.submit();
            }, reduce ? 0 : 180);
        }
    }

    boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            clearError();
            var v = box.value.replace(/\D/g, '');

            // Paste / autofill landed in a single box: spread it out.
            if (v.length > 1) {
                box.value = '';
                distribute(v, index);
                return;
            }

            box.value = v;
            markFilled(box);
            syncHidden();
            if (v) focusBox(index + 1);
            maybeAutoSubmit();
        });

        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                e.preventDefault();
                clearError();
                if (box.value) {
                    box.value = '';
                    markFilled(box);
                } else if (boxes[index - 1]) {
                    boxes[index - 1].value = '';
                    markFilled(boxes[index - 1]);
                    focusBox(index - 1);
                }
                syncHidden();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                focusBox(index - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                focusBox(index + 1);
            }
        });

        box.addEventListener('focus', function () { box.select(); });

        box.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            distribute(text, index);
        });
    });

    // How long the "verifying" wave stays on screen before the real navigation.
    var VERIFY_HOLD_MS = reduce ? 0 : 1200;

    form.addEventListener('submit', function (e) {
        // Once we are holding for the animation, allow the native submit through.
        if (submitting) return;

        var code = syncHidden();

        if (code.length !== 6) {
            e.preventDefault();
            showError();
            var empty = firstEmptyIndex();
            focusBox(empty === -1 ? 0 : empty);
            return;
        }

        // Hold the submission briefly so the wave + spinner are actually visible.
        e.preventDefault();
        submitting = true;
        clearError();
        container.classList.add('is-verifying');
        boxes.forEach(function (b) { b.readOnly = true; });

        var btnTexto    = document.getElementById('btnVerificarTexto');
        var btnCargando = document.getElementById('btnVerificarCargando');
        var btnIcon     = document.getElementById('btnVerificarIcon');
        var btn         = document.getElementById('btnVerificar');
        if (btnIcon)     btnIcon.style.display     = 'none';
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline-flex';
        if (btn)         btn.disabled              = true;

        // form.submit() does not re-fire this listener, so no re-entry guard needed.
        setTimeout(function () { form.submit(); }, VERIFY_HOLD_MS);
    });

    focusBox(0);
})();

// ============================================================
// RECOVERY PASSWORD PAGE
// ============================================================

(function initRecovery() {
    var formRecovery = document.getElementById('formRecovery');
    if (!formRecovery) return;

    // Password toggle buttons.
    var togglePassBtn    = document.getElementById('toggle-recovery-password');
    var toggleConfirmBtn = document.getElementById('toggle-recovery-confirm');
    if (togglePassBtn)    togglePassBtn.addEventListener('click',    function () { togglePass('recovery-password',         'eye-recovery-password'); });
    if (toggleConfirmBtn) toggleConfirmBtn.addEventListener('click', function () { togglePass('recovery-password-confirm', 'eye-recovery-confirm'); });

    // Gmail validation for recovery email.
    var recEmailInput = document.getElementById('recovery-email');
    if (recEmailInput) {
        recEmailInput.addEventListener('input', function () {
            var val = this.value.trim().toLowerCase();
            if (val === '') { clearMsg('msg-recovery-email'); setInputState(this, null); return; }
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
    var recPassInput = document.getElementById('recovery-password');
    if (recPassInput) {
        recPassInput.addEventListener('input', function () {
            var v = this.value;
            if (v.length === 0)    { clearMsg('msg-recovery-password'); setInputState(this, null); }
            else if (v.length < 8) { showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
            else                   { showMsg('msg-recovery-password', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
            checkRecoveryMatch();
        });
    }

    // Password confirmation match.
    function checkRecoveryMatch() {
        var passEl    = document.getElementById('recovery-password');
        var confirmEl = document.getElementById('recovery-password-confirm');
        if (!passEl || !confirmEl) return;
        var p  = passEl.value;
        var pc = confirmEl.value;
        if (pc.length === 0) { clearMsg('msg-recovery-confirm'); setInputState(confirmEl, null); return; }
        if (p !== pc) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(confirmEl, 'input-error');
        } else {
            showMsg('msg-recovery-confirm', 'success', 'Las contraseñas coinciden.');
            setInputState(confirmEl, 'input-ok');
        }
    }

    var recConfirmInput = document.getElementById('recovery-password-confirm');
    if (recConfirmInput) recConfirmInput.addEventListener('input', checkRecoveryMatch);

    // Final validation before submitting recovery form.
    formRecovery.addEventListener('submit', function (e) {
        var valid = true;

        var emailVal = recEmailInput ? recEmailInput.value.trim().toLowerCase() : '';
        if (!emailVal) {
            showMsg('msg-recovery-email', 'error', 'El correo Gmail es obligatorio.');
            setInputState(recEmailInput, 'input-error');
            valid = false;
        } else if (!emailVal.endsWith('@gmail.com')) {
            showMsg('msg-recovery-email', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(recEmailInput, 'input-error');
            valid = false;
        }

        var passVal = recPassInput ? recPassInput.value : '';
        if (passVal.length === 0) {
            showMsg('msg-recovery-password', 'error', 'La contraseña es obligatoria.');
            setInputState(recPassInput, 'input-error');
            valid = false;
        } else if (passVal.length < 8) {
            showMsg('msg-recovery-password', 'error', 'Mínimo 8 caracteres.');
            setInputState(recPassInput, 'input-error');
            valid = false;
        }

        var confVal = recConfirmInput ? recConfirmInput.value : '';
        if (confVal.length === 0) {
            showMsg('msg-recovery-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        } else if (passVal !== confVal) {
            showMsg('msg-recovery-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(recConfirmInput, 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        var btn         = document.getElementById('btnRecovery');
        var btnTexto    = document.getElementById('btnRecoveryTexto');
        var btnCargando = document.getElementById('btnRecoveryCargando');
        if (btn)         btn.disabled              = true;
        if (btnTexto)    btnTexto.style.display    = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';
    });
})();

// ============================================================
// GENERAL INITIALIZATION (DOMContentLoaded)
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    // — Login modal —
    var loginModalTrigger = document.getElementById('login-modal-trigger');
    if (loginModalTrigger) {
        loginModalTrigger.addEventListener('click', function () {
            window.location.href = '/login';
        });
    }

    var closeLoginModalBtn = document.getElementById('close-login-modal');
    if (closeLoginModalBtn) closeLoginModalBtn.addEventListener('click', closeLoginModal);

    var loginModalOverlay = document.getElementById('login-modal-overlay');
    if (loginModalOverlay) loginModalOverlay.addEventListener('click', closeLoginModal);

    // — Toggle password (login page) —
    var togglePasswordBtn = document.getElementById('toggle-password');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            togglePass('login-password', 'eye-icon');
        });
    }

    // — Login form submission via AJAX —
    var publicLoginForm = document.getElementById('public-login-form');
    if (publicLoginForm) {
        publicLoginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var csrfToken = getCsrfToken();
            if (!csrfToken) {
                window.location.href = '/login?session_expired=1';
                return;
            }

            var formData    = new FormData(this);
            var submitBtn   = document.getElementById('login-submit-btn');
            var loadingSpan = document.getElementById('login-loading');
            var submitSpan  = submitBtn ? submitBtn.querySelector('span:not(.btn-loading)') : null;

            if (submitBtn)   submitBtn.disabled = true;
            if (submitSpan)  submitSpan.classList.add('hidden');
            if (loadingSpan) loadingSpan.classList.remove('hidden');

            fetch('/login', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    // 419 means expired session/CSRF token.
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
                        if (typeof window.cf4AuthWelcomeToast === 'function') {
                            window.cf4AuthWelcomeToast({
                                kind: 'welcome',
                                authIcon: 'user',
                                displayName: data.display_name || '',
                                thenUrl: data.redirect || '/',
                            });
                        } else {
                            fireSwal({
                                icon: 'success',
                                title: '¡Bienvenido!',
                                text: data.message || 'Inicio de sesión exitoso',
                                timer: 4000,
                                showConfirmButton: false,
                            }).then(function () {
                                window.location.href = data.redirect || '/';
                            });
                        }
                    } else if (data.redirect) {
                        // Correo no verificado: ofrecer ir a verificar.
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                        cf4Confirm({
                            icon: 'warning',
                            title: 'Correo no verificado',
                            text: data.message || 'Debes verificar tu correo antes de iniciar sesión.',
                            confirmButtonText: 'Verificar Correo',
                            cancelButtonText: 'Cancelar',
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            // El servidor ya envió el código al detectar el correo no verificado.
                            window.location.href = data.redirect;
                        });
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
                            if (result.isConfirmed) { window.location.href = '/register'; }
                        });
                        if (submitBtn)   submitBtn.disabled = false;
                        if (submitSpan)  submitSpan.classList.remove('hidden');
                        if (loadingSpan) loadingSpan.classList.add('hidden');
                    }
                })
                .catch(function (err) {
                    if (err === 'csrf' || err === 'parse') return;
                    console.error('Login error:', err);
                    fireSwal({ icon: 'error', title: 'Error', text: 'Ocurrió un error al iniciar sesión' });
                    if (submitBtn)   submitBtn.disabled = false;
                    if (submitSpan)  submitSpan.classList.remove('hidden');
                    if (loadingSpan) loadingSpan.classList.add('hidden');
                });
        });
    }

    // — Profile page: store originals and wire password strength meter —
    if (document.getElementById('formPerfil')) {
        profileSaveOriginals();

        var passInputProfile = document.getElementById('new_password');
        if (passInputProfile) {
            passInputProfile.addEventListener('input', function () { updateStrength(this.value); });
        }

        var flash = window.__profileFlash || {};
        if (flash.profile_updated)  showProfileAlert('Cambios guardados correctamente.', 'success');
        if (flash.password_updated) showProfileAlert('Contraseña actualizada correctamente.', 'success');
        if (flash.password_defined) showProfileAlert('Contraseña definida. Ahora puedes iniciar sesión con correo y contraseña.', 'success');
    }

    // — Password change form: confirm and handle Google-only accounts —
    var formPassword = document.getElementById('formPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', async function (e) {
            e.preventDefault();
            var isGoogleOnly = !!document.getElementById('googlePassCta');
            var confirmMsg   = isGoogleOnly
                ? 'Se definirá una contraseña para tu cuenta. Podrás iniciar sesión con correo y contraseña.'
                : 'Se actualizará la contraseña de tu cuenta.';

            const result = await cf4Confirm({
                title: '¿Confirmar cambio de contraseña?',
                text: confirmMsg,
                icon: 'warning',
                confirmButtonText: isGoogleOnly ? 'Sí, definir' : 'Sí, actualizar',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) return;

            sendPassword(formPassword);
        });
    }

    // — Favorites drawer remove button (delegated) —
    // El resto de manejadores de carrito se delega a clients-page.js cuando esa
    // entrada de Vite también se carga (catalog/cart/product/home). Si solo se
    // carga clients-users.js (login/register/recovery/profile) no hay UI de
    // carrito, por lo que omitimos los listeners para evitar doble ejecución.
    document.addEventListener('click', function (e) {
        var favoriteRemoveBtn = e.target.closest('[data-favorite-remove-btn]');
        if (favoriteRemoveBtn) {
            e.preventDefault();
            toggleFavoriteFromDrawer(favoriteRemoveBtn.getAttribute('data-product-id'));
        }
    });

    // — Cart UI listeners (only when clients-page.js is NOT loaded on this page) —
    // Pages that render cart UI (cart, catalog, product, home) load clients-page.js,
    // which already binds these handlers. Avoid double-binding here.
    if (!window.__cf4ClientPageJsLoaded) {
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.add-to-cart-btn');
        if (addBtn) {
            if (addBtn.dataset.purchasable === '0' || parseInt(addBtn.dataset.productStock, 10) < 1) {
                void cf4Warning('Este producto no tiene unidades disponibles.', 'Producto agotado');
                return;
            }
            addToCartShared(addBtn.dataset.productId, 1, addBtn);
            return;
        }

        var guestBtn = e.target.closest('.guest-add-btn');
        if (guestBtn) {
            if (guestBtn.dataset.purchasable === '0' || parseInt(guestBtn.dataset.productStock, 10) < 1) {
                void cf4Warning('Este producto no tiene unidades disponibles.', 'Producto agotado');
                return;
            }
            window.location.href = '/login';
            return;
        }

        if (e.target.classList.contains('modal') && e.target.classList.contains('active')) {
            e.target.classList.remove('active');
        }
    });

    initCartInteractions();
    } // end if (!window.__cf4ClientPageJsLoaded)

    // — ESC closes all modals and dropdowns —
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (typeof window.cf4SetHeaderMenuOpen === 'function') {
            window.cf4SetHeaderMenuOpen(false);
        }
        setFavoritesDrawerOpen(false);
        closeUserDropdown();
        closeLoginModal();
        document.querySelectorAll('.modal.active').forEach(function (modal) {
            modal.classList.remove('active');
        });
    });

    // — Catalog price-range filter validation —
    (function initCatalogPriceFilter() {
        var form      = document.getElementById('filter-form');
        if (!form) return;
        var minInput  = document.getElementById('min_price');
        var maxInput  = document.getElementById('max_price');
        var submitBtn = document.getElementById('filter-submit-btn');

        function checkPriceRange() {
            if (!minInput || !maxInput || !submitBtn) return;
            var min       = parseFloat(minInput.value);
            var max       = parseFloat(maxInput.value);
            var minFilled = minInput.value.trim() !== '';
            var maxFilled = maxInput.value.trim() !== '';
            var negMin    = minFilled && !isNaN(min) && min < 0;
            var negMax    = maxFilled && !isNaN(max) && max < 0;
            var invalid   = negMin || negMax || (minFilled && maxFilled && !isNaN(min) && !isNaN(max) && min > max);
            submitBtn.disabled = invalid;
            if (invalid) {
                submitBtn.setAttribute(
                    'title',
                    negMin || negMax
                        ? 'Los precios no pueden ser negativos.'
                        : 'El precio mínimo debe ser menor o igual al precio máximo.'
                );
            } else {
                submitBtn.removeAttribute('title');
            }
        }

        if (minInput) minInput.addEventListener('input',  checkPriceRange);
        if (minInput) minInput.addEventListener('change', checkPriceRange);
        if (maxInput) maxInput.addEventListener('input',  checkPriceRange);
        if (maxInput) maxInput.addEventListener('change', checkPriceRange);
        checkPriceRange();
    })();

    // Catálogo: paginación vía ajax-pagination.js (clients-page.js). Legacy #goToPageBtn solo si queda Blade de Dev.

}); // end DOMContentLoaded

// ============================================================
// GLOBAL EXPORTS (for use from inline scripts / Blade onclicks)
// ============================================================
window.addToCart        = addToCart;
window.updateCartCount  = updateCartCount;
window.togglePass       = togglePass;
window.togglePassword   = togglePassword;
window.showMsg          = showMsg;
window.clearMsg         = clearMsg;
window.setInputState    = setInputState;
window.enableEdit       = enableEdit;
window.cancelEdit       = cancelEdit;
window.submitProfile    = submitProfile;
window.showPasswordForm = showPasswordForm;
window.hidePasswordForm = hidePasswordForm;
window.showProfileAlert  = showProfileAlert;
window.closeProfileAlert = closeProfileAlert;
