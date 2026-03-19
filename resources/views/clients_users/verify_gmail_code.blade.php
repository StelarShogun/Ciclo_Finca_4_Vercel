@extends('clients.layouts.guest')

@section('title', 'Verificar Correo')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:420px;">

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
            <div class="alert alert-danger mb-3">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Alerta si el correo falló --}}
        @if (session('mail_warning'))
            <div class="alert alert-warning mb-3">
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
                       class="form-control text-center @error('verification_code') is-invalid @enderror"
                       maxlength="6"
                       placeholder="_ _ _ _ _ _"
                       autocomplete="one-time-code"
                       inputmode="numeric"
                       style="font-size:1.8rem;letter-spacing:0.5rem;font-weight:700;">
                @error('verification_code')
                    <div class="invalid-feedback text-center">{{ $message }}</div>
                @enderror
                <div id="code-error" class="text-danger text-center small mt-1" style="display:none;">
                    El código debe tener exactamente 6 dígitos.
                </div>
            </div>

            <button type="submit" id="btnVerificar" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-check-circle"></i>
                <span id="btnVerificarTexto">Verificar Código</span>
                <span id="btnVerificarCargando" style="display:none;">Verificando...</span>
            </button>
        </form>

        {{-- Reenviar código --}}
        <div class="text-center mt-4">
            <p class="text-muted" style="font-size:0.875rem;">¿No recibiste el código?</p>
            <form method="POST" action="{{ route('clients.verify.resend') }}">
                @csrf
                <button type="submit" class="btn btn-link p-0" style="font-size:0.875rem;">
                    Reenviar código
                </button>
            </form>
        </div>

        <div class="login-footer text-center mt-3">
            <a href="{{ route('clients.register.form') }}" style="font-size:0.875rem;">
                <i class="fas fa-arrow-left"></i> Volver al registro
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Solo permitir dígitos en el campo de código
    const codeInput = document.getElementById('verification_code');

    codeInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    // Validar antes de enviar
    document.getElementById('formVerificar').addEventListener('submit', function (e) {
        const code = codeInput.value.trim();
        const err  = document.getElementById('code-error');

        if (code.length !== 6) {
            err.style.display = 'block';
            codeInput.classList.add('is-invalid');
            e.preventDefault();
            return;
        }

        err.style.display = 'none';
        codeInput.classList.remove('is-invalid');

        // Feedback carga
        document.getElementById('btnVerificarTexto').style.display = 'none';
        document.getElementById('btnVerificarCargando').style.display = 'inline';
        document.getElementById('btnVerificar').disabled = true;
    });
</script>
@endpush
