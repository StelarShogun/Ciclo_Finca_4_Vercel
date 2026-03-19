@extends('clients.layouts.guest')

@section('title', 'Registrar Cliente')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:480px;">
        <h2 class="text-center mb-4">Crear Cuenta</h2>

        {{-- Errores de validación --}}
        @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el registro',
                    html: '<ul style="text-align:left;margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1a73e8'
                });
            });
        </script>
        @endif

        {{-- Mensaje de éxito --}}
        @if (session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif

        <form id="formRegistroCliente" method="POST" action="{{ route('clients.register') }}" novalidate>
            @csrf

            {{-- Nombre --}}
            <div class="form-group mb-3">
                <label for="name">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name"
                       class="form-control @error('name') input-error @enderror"
                       value="{{ old('name') }}"
                       placeholder="Ej: Juan"
                       autocomplete="given-name">
                <div id="msg-name" class="field-msg @error('name') error @enderror">
                    @error('name')<i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- Apellido + Segundo Apellido en fila --}}
            <div style="display:flex; gap:16px; margin-bottom:1rem;">
                <div class="form-group" style="flex:1; min-width:0; margin-bottom:0;">
                    <label for="first_surname">Apellido <span class="text-danger">*</span></label>
                    <input type="text" id="first_surname" name="first_surname"
                           class="form-control @error('first_surname') input-error @enderror"
                           value="{{ old('first_surname') }}"
                           placeholder="Ej: Pérez"
                           autocomplete="family-name">
                    <div id="msg-first-surname" class="field-msg @error('first_surname') error @enderror">
                        @error('first_surname')<i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-group" style="flex:1; min-width:0; margin-bottom:0;">
                    <label for="second_surname">Segundo Apellido</label>
                    <input type="text" id="second_surname" name="second_surname"
                           class="form-control @error('second_surname') input-error @enderror"
                           value="{{ old('second_surname') }}"
                           placeholder="Ej: García (opcional)"
                           autocomplete="additional-name">
                    <div id="msg-second-surname" class="field-msg @error('second_surname') error @enderror">
                        @error('second_surname')<i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            {{-- Gmail --}}
            <div class="form-group mb-3">
                <label for="gmail">Correo Electrónico <span class="text-danger">*</span></label>
                <input type="email" id="gmail" name="gmail"
                       class="form-control @error('gmail') input-error @enderror"
                       value="{{ old('gmail') }}"
                       placeholder="ejemplo@gmail.com"
                       autocomplete="email">
                <div id="msg-gmail" class="field-msg @error('gmail') error @enderror">
                    @error('gmail')<i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- Contraseña --}}
            <div class="form-group mb-3">
                <label for="password">Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') input-error @enderror"
                           minlength="8"
                           placeholder="Mínimo 8 caracteres"
                           autocomplete="new-password"
                           style="padding-right:40px;">
                    <button type="button" onclick="togglePass('password','eye1')"
                            style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                <div id="msg-pass" class="field-msg @error('password') error @enderror">
                    @error('password')<i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- Verificar Contraseña --}}
            <div class="form-group mb-4">
                <label for="password_confirmation">Verificar Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           minlength="8"
                           placeholder="Repite la contraseña"
                           autocomplete="new-password"
                           style="padding-right:40px;">
                    <button type="button" onclick="togglePass('password_confirmation','eye2')"
                            style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                <div id="msg-pass-confirm" class="field-msg"></div>
            </div>

            <button type="submit" id="btnRegistrar" class="btn btn-primary btn-block btn-lg mt-2">
                <i class="fas fa-user-plus"></i>
                <span id="btnRegistrarTexto">Crear Cuenta</span>
                <span id="btnRegistrarCargando" style="display:none;">Registrando...</span>
            </button>
        </form>

        <div class="login-footer text-center mt-3">
            <p>¿Ya tienes cuenta? <a href="{{ route('login.show') }}">Iniciar sesión</a></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ---------- utilidades ----------
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

    // ---------- patrón solo letras ----------
    const invalidChars = /[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/;
    const soloLetras   = /^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/;

    // ---------- validación en tiempo real: nombre y apellidos ----------
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

    // ---------- gmail ----------
    const gmailInput = document.getElementById('gmail');
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

    // ---------- contraseñas ----------
    function checkPassMatch() {
        const p  = document.getElementById('password').value;
        const pc = document.getElementById('password_confirmation').value;
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

    document.getElementById('password').addEventListener('input', function() {
        const v = this.value;
        if (v.length === 0)      { clearMsg('msg-pass'); setInputState(this, null); }
        else if (v.length < 8)   { showMsg('msg-pass', 'error', 'Mínimo 8 caracteres (' + v.length + '/8).'); setInputState(this, 'input-error'); }
        else                     { showMsg('msg-pass', 'success', 'Longitud correcta.'); setInputState(this, 'input-ok'); }
        checkPassMatch();
    });
    document.getElementById('password_confirmation').addEventListener('input', checkPassMatch);

    // ---------- envío ----------
    document.getElementById('formRegistroCliente').addEventListener('submit', function(e) {
        let valid = true;

        if (document.getElementById('name').value.trim() === '') {
            showMsg('msg-name', 'error', 'El nombre es obligatorio.');
            setInputState(document.getElementById('name'), 'input-error');
            valid = false;
        }
        if (document.getElementById('first_surname').value.trim() === '') {
            showMsg('msg-first-surname', 'error', 'El apellido es obligatorio.');
            setInputState(document.getElementById('first_surname'), 'input-error');
            valid = false;
        }

        const gv = document.getElementById('gmail').value.trim().toLowerCase();
        if (gv === '') {
            showMsg('msg-gmail', 'error', 'El correo Gmail es obligatorio.');
            setInputState(document.getElementById('gmail'), 'input-error');
            valid = false;
        } else if (!gv.endsWith('@gmail.com')) {
            showMsg('msg-gmail', 'error', 'Solo se aceptan correos @gmail.com.');
            setInputState(document.getElementById('gmail'), 'input-error');
            valid = false;
        }

        const pv  = document.getElementById('password').value;
        const pcv = document.getElementById('password_confirmation').value;
        if (pv.length === 0) {
            showMsg('msg-pass', 'error', 'La contraseña es obligatoria.');
            setInputState(document.getElementById('password'), 'input-error');
            valid = false;
        } else if (pv.length < 8) {
            showMsg('msg-pass', 'error', 'Mínimo 8 caracteres.');
            setInputState(document.getElementById('password'), 'input-error');
            valid = false;
        }
        if (pcv.length === 0) {
            showMsg('msg-pass-confirm', 'error', 'Debes confirmar la contraseña.');
            setInputState(document.getElementById('password_confirmation'), 'input-error');
            valid = false;
        } else if (pv !== pcv) {
            showMsg('msg-pass-confirm', 'error', 'Las contraseñas no coinciden.');
            setInputState(document.getElementById('password_confirmation'), 'input-error');
            valid = false;
        }

        if (!valid) { e.preventDefault(); return; }

        document.getElementById('btnRegistrarTexto').style.display = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled = true;
    });
</script>
@endpush


