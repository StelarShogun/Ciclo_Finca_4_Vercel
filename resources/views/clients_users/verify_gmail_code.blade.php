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

<div class="login-form-box" style="max-width:420px;width:100%;">

    {{-- Ícono --}}
    <div class="text-center mb-3">
        <i class="fas fa-envelope-open-text" style="font-size:3rem;color:#2d7a2d;"></i>
    </div>

    {{-- Títulos --}}
    <h2 class="text-center mb-2" style="font-size:1.5rem;font-weight:700;">Verifica que eres tú</h2>
    <p class="text-center text-muted mb-4" style="font-size:0.95rem;">
        Código de verificación ha sido enviado a tu correo
        @if(session('pending_gmail'))
            <br><strong>{{ session('pending_gmail') }}</strong>
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

    <form id="formVerificar" method="POST" action="{{ route('clients.verify') }}" novalidate>
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
            <span id="btnVerificarTexto">Verificar Código</span>
            <span id="btnVerificarCargando" style="display:none;">Verificando...</span>
        </button>
    </form>

    {{-- Reenviar código --}}
    <div style="text-align:center;margin-top:1.5rem;">
        <p style="color:#5f6368;font-size:0.875rem;margin-bottom:6px;">¿No recibiste el código?</p>
        <form method="POST" action="{{ route('clients.verify.resend') }}">
            @csrf
            <button type="submit" style="background:none;border:none;color:#2d7a2d;font-weight:600;cursor:pointer;font-size:0.875rem;">
                Reenviar código
            </button>
        </form>
    </div>

    <div style="text-align:center;margin-top:1rem;">
        <a href="{{ route('clients.register.form') }}" style="font-size:0.875rem;color:#5f6368;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Volver al registro
        </a>
    </div>
</div>

<script>
    const codeInput = document.getElementById('verification_code');

    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    document.getElementById('formVerificar').addEventListener('submit', function (e) {
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
</script>

</body>
</html>
