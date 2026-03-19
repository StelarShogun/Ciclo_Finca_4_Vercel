@extends('clients.layouts.guest')

@section('title', 'Registrar Cliente')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:480px;">
        <h2 class="text-center mb-4">Crear Cuenta</h2>

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
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
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}"
                       required
                       placeholder="Ej: Juan"
                       autocomplete="given-name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Apellido --}}
            <div class="form-group mb-3">
                <label for="first_surname">Apellido <span class="text-danger">*</span></label>
                <input type="text" id="first_surname" name="first_surname"
                       class="form-control @error('first_surname') is-invalid @enderror"
                       value="{{ old('first_surname') }}"
                       required
                       placeholder="Ej: Pérez"
                       autocomplete="family-name">
                @error('first_surname')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Segundo Apellido --}}
            <div class="form-group mb-3">
                <label for="second_surname">Segundo Apellido</label>
                <input type="text" id="second_surname" name="second_surname"
                       class="form-control @error('second_surname') is-invalid @enderror"
                       value="{{ old('second_surname') }}"
                       placeholder="Ej: García (opcional)"
                       autocomplete="additional-name">
                @error('second_surname')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Gmail --}}
            <div class="form-group mb-3">
                <label for="gmail">Correo Gmail <span class="text-danger">*</span></label>
                <input type="email" id="gmail" name="gmail"
                       class="form-control @error('gmail') is-invalid @enderror"
                       value="{{ old('gmail') }}"
                       required
                       placeholder="ejemplo@gmail.com"
                       autocomplete="email">
                @error('gmail')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Contraseña --}}
            <div class="form-group mb-3">
                <label for="password">Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required
                           minlength="8"
                           placeholder="Mínimo 8 caracteres"
                           autocomplete="new-password"
                           style="padding-right:40px;">
                    <button type="button" onclick="togglePass('password','eye1')"
                            style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- Verificar Contraseña --}}
            <div class="form-group mb-4">
                <label for="password_confirmation">Verificar Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           required
                           minlength="8"
                           placeholder="Repite la contraseña"
                           autocomplete="new-password"
                           style="padding-right:40px;">
                    <button type="button" onclick="togglePass('password_confirmation','eye2')"
                            style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                <div id="pass-match-error" class="text-danger small mt-1" style="display:none;">
                    Las contraseñas no coinciden.
                </div>
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
    // Mostrar/ocultar contraseña
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

    // Patrón: sólo letras (incluyendo tildes y ñ) y espacios
    const soloLetras = /^[A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]+$/;

    // Validación en tiempo real para campos de nombre
    ['name', 'first_surname', 'second_surname'].forEach(function(id) {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('input', function() {
            // Eliminar cualquier carácter que no sea letra o espacio
            this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
        });
    });

    // Validar que las contraseñas coincidan al escribir
    document.getElementById('password_confirmation').addEventListener('input', checkPassMatch);
    document.getElementById('password').addEventListener('input', checkPassMatch);

    function checkPassMatch() {
        const p  = document.getElementById('password').value;
        const pc = document.getElementById('password_confirmation').value;
        const err = document.getElementById('pass-match-error');
        if (pc.length > 0 && p !== pc) {
            err.style.display = 'block';
        } else {
            err.style.display = 'none';
        }
    }

    // Validación antes de enviar
    document.getElementById('formRegistroCliente').addEventListener('submit', function(e) {
        let valid = true;

        // Validar campos de nombre (sólo letras)
        ['name', 'first_surname', 'second_surname'].forEach(function(id) {
            const input = document.getElementById(id);
            if (!input || input.value.trim() === '') return; // segundo apellido es opcional
            if (!soloLetras.test(input.value.trim())) {
                input.classList.add('is-invalid');
                valid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        // Validar nombre y apellido requeridos
        ['name', 'first_surname'].forEach(function(id) {
            const input = document.getElementById(id);
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                valid = false;
            }
        });

        // Validar gmail
        const gmailInput = document.getElementById('gmail');
        if (!gmailInput.value.trim().toLowerCase().endsWith('@gmail.com')) {
            gmailInput.classList.add('is-invalid');
            valid = false;
        } else {
            gmailInput.classList.remove('is-invalid');
        }

        // Validar contraseñas
        const p  = document.getElementById('password').value;
        const pc = document.getElementById('password_confirmation').value;
        if (p !== pc) {
            document.getElementById('pass-match-error').style.display = 'block';
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            return;
        }

        // Feedback de carga
        document.getElementById('btnRegistrarTexto').style.display = 'none';
        document.getElementById('btnRegistrarCargando').style.display = 'inline';
        document.getElementById('btnRegistrar').disabled = true;
    });
</script>
@endpush


