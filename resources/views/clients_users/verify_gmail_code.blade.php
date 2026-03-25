<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificar Correo - Ciclo Finca 4</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/clients-users.css'])
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6fb;font-family:'Poppins',sans-serif;">

@php
    $isRecovery  = session()->has('pending_recovery_id');
    $formAction  = $isRecovery ? route('clients.recovery.verify') : route('clients.verify');
    $displayEmail = session('pending_recovery_gmail') ?? session('pending_gmail');
    $backRoute   = $isRecovery ? route('clients.recovery.form') : route('clients.register.form');
    $backLabel   = $isRecovery ? 'Volver a recuperación' : 'Volver al registro';
    $pageTitle   = $isRecovery ? 'Verificar identidad' : 'Verifica que eres tú';
    $pageSubtitle = $isRecovery
        ? 'Ingresa el código para confirmar el cambio de contraseña'
        : 'Código de verificación ha sido enviado a tu correo';
@endphp

<div class="login-form-box" style="max-width:420px;width:100%;">

    {{-- Ícono --}}
    <div class="text-center mb-3">
        <i class="fas {{ $isRecovery ? 'fa-shield-alt' : 'fa-envelope-open-text' }}" style="font-size:3rem;color:#2d7a2d;"></i>
    </div>

    {{-- Títulos --}}
    <h2 class="text-center mb-2" style="font-size:1.5rem;font-weight:700;">{{ $pageTitle }}</h2>
    <p class="text-center text-muted mb-4" style="font-size:0.95rem;">
        {{ $pageSubtitle }}
        @if($displayEmail)
            <br><strong>{{ $displayEmail }}</strong>
        @endif
    </p>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-3" style="background:#fdf0ef;border:1px solid #f5c6cb;color:#c0392b;padding:10px 14px;border-radius:8px;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Alerta si el correo falló --}}
    @if (session('mail_warning'))
        <div class="alert alert-warning mb-3" style="background:#fff8e1;border:1px solid #ffe082;color:#856404;padding:10px 14px;border-radius:8px;">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('mail_warning') }}
        </div>
    @endif

    <form id="formVerificar" method="POST" action="{{ $formAction }}" novalidate>
        @csrf

        <div class="form-group mb-4">
            <label for="verification_code" style="font-weight:600;">Código de verificación</label>
            <input type="text"
                   id="verification_code"
                   name="verification_code"
                   maxlength="6"
                   placeholder="_ _ _ _ _ _"
                   autocomplete="one-time-code"
                   inputmode="numeric"
                   style="width:100%;padding:12px;font-size:1.8rem;letter-spacing:0.5rem;font-weight:700;text-align:center;border:1px solid #dadce0;border-radius:10px;outline:none;">
            <div id="code-error" style="display:none;color:#c0392b;text-align:center;font-size:0.82rem;margin-top:6px;">
                El código debe tener exactamente 6 dígitos.
            </div>
        </div>

        <button type="submit" id="btnVerificar"
            style="width:100%;padding:12px;background:#2d7a2d;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-check-circle"></i>
            <span id="btnVerificarTexto">{{ $isRecovery ? 'Verificar y Actualizar Contraseña' : 'Verificar Código' }}</span>
            <span id="btnVerificarCargando" style="display:none;">Verificando...</span>
        </button>
    </form>

    {{-- Reenviar código: solo para el flujo de registro --}}
    @unless($isRecovery)
    <div style="text-align:center;margin-top:1.5rem;">
        <p style="color:#5f6368;font-size:0.875rem;margin-bottom:6px;">¿No recibiste el código?</p>
        <form method="POST" action="{{ route('clients.verify.resend') }}">
            @csrf
            <button type="submit" style="background:none;border:none;color:#2d7a2d;font-weight:600;cursor:pointer;font-size:0.875rem;">
                Reenviar código
            </button>
        </form>
    </div>
    @endunless

    <div style="text-align:center;margin-top:1rem;">
        <a href="{{ $backRoute }}" style="font-size:0.875rem;color:#5f6368;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> {{ $backLabel }}
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var codeInput = document.getElementById('verification_code');
    var isRecovery = {{ $isRecovery ? 'true' : 'false' }};

    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    document.getElementById('formVerificar').addEventListener('submit', function (e) {
        var code = codeInput.value.trim();
        var err  = document.getElementById('code-error');

        if (code.length !== 6) {
            err.style.display = 'block';
            codeInput.style.borderColor = '#e74c3c';
            e.preventDefault();
            return;
        }

        err.style.display = 'none';
        codeInput.style.borderColor = '#dadce0';

        if (!isRecovery) {
            // Registration flow: standard form submit
            document.getElementById('btnVerificarTexto').style.display = 'none';
            document.getElementById('btnVerificarCargando').style.display = 'inline';
            document.getElementById('btnVerificar').disabled = true;
            return;
        }

        // Recovery flow: AJAX submit
        e.preventDefault();
        var btn        = document.getElementById('btnVerificar');
        var btnTexto   = document.getElementById('btnVerificarTexto');
        var btnCargando = document.getElementById('btnVerificarCargando');
        if (btn) btn.disabled = true;
        if (btnTexto) btnTexto.style.display = 'none';
        if (btnCargando) btnCargando.style.display = 'inline';

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.content : '';

        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(this)
        })
            .then(function (r) {
                if (r.status === 422) {
                    return r.json().then(function (data) {
                        var errors = data.errors || {};
                        var firstMsg = Object.values(errors)[0];
                        var msg = Array.isArray(firstMsg) ? firstMsg[0] : (firstMsg || 'Error de validación.');
                        err.textContent = msg;
                        err.style.display = 'block';
                        codeInput.style.borderColor = '#e74c3c';
                        return Promise.reject('validation');
                    });
                }
                return r.json();
            })
            .then(function (res) {
                if (!res.success) {
                    err.textContent = res.message || 'Código incorrecto.';
                    err.style.display = 'block';
                    codeInput.style.borderColor = '#e74c3c';
                    return;
                }
                Swal.fire({
                    icon: 'success',
                    title: '¡Contraseña actualizada!',
                    text: res.message || 'Ya puedes iniciar sesión con tu nueva contraseña.',
                    confirmButtonText: 'Ir al inicio de sesión'
                }).then(function () {
                    window.location.href = '/login';
                });
            })
            .catch(function (err2) {
                if (err2 === 'validation') return;
                Swal.fire('Error', 'Ocurrió un error. Intenta de nuevo.', 'error');
            })
            .finally(function () {
                if (btn) btn.disabled = false;
                if (btnTexto) btnTexto.style.display = '';
                if (btnCargando) btnCargando.style.display = 'none';
            });
    });
</script>

</body>
</html>
