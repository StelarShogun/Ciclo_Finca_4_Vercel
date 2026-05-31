@extends('client.layouts.app')

@section('hideNav')
@endsection

@section('title', 'Verificar Correo - Ciclo Finca 4')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')
@php
    $isRecoveryFlow = session()->has('pending_recovery_id');
@endphp
<div class="login-page-center">
    <div class="login-form-box" style="max-width:420px;width:100%;">

        <div class="text-center mb-3">
            <i class="fas fa-envelope-open-text" style="font-size:3rem;color:var(--color-success);"></i>
        </div>

        {{-- Display the destination email if stored in session --}}
        <h2 class="text-center mb-2" style="font-size:1.5rem;font-weight:700;">Verifica que eres tú</h2>
        <p class="text-center text-muted mb-4" style="font-size:0.95rem;">
            Código de verificación ha sido enviado a tu correo
            @if(session('pending_gmail'))
                <br><strong>{{ session('pending_gmail') }}</strong>
            @endif
        </p>

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Shown when the verification email could not be delivered --}}
        @if (session('mail_warning'))
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle"></i>
                {{ session('mail_warning') }}
            </div>
        @endif

        {{-- novalidate defers all validation to JS --}}
        <form id="formVerificar" method="POST" action="{{ $isRecoveryFlow ? route('clients.recovery.verify') : route('clients.verify') }}" novalidate>
            @csrf

            <div class="form-group mb-4">
                <label for="verification_code" class="login-field-label" style="font-weight:600;">Código de verificación</label>
                {{-- inputmode="numeric" opens the numeric keyboard on mobile --}}
                <input type="text"
                       id="verification_code"
                       name="verification_code"
                       class="form-control"
                       maxlength="6"
                       placeholder="_ _ _ _ _ _"
                       autocomplete="one-time-code"
                       inputmode="numeric"
                       style="font-size:clamp(1.3rem, 8vw, 1.8rem);letter-spacing:0.5rem;font-weight:700;text-align:center;">
                {{-- Shown by JS when the code length is not exactly 6 digits --}}
                <div id="code-error" style="display:none;color:var(--color-danger);text-align:center;font-size:0.82rem;margin-top:6px;">
                    El código debe tener exactamente 6 dígitos.
                </div>
            </div>

            {{-- Loading state swaps button text during submission --}}
            <button type="submit" id="btnVerificar" class="btn btn-login-submit"
                style="margin-top:0;">
                <i class="fas fa-check-circle"></i>
                <span id="btnVerificarTexto">Verificar Código</span>
                <span id="btnVerificarCargando" style="display:none;">Verificando...</span>
            </button>
        </form>

        {{-- Separate form to avoid interfering with the verification POST --}}
        @if (! $isRecoveryFlow)
            <div style="text-align:center;margin-top:1.5rem;">
                <p style="color:var(--text-secondary);font-size:0.875rem;margin-bottom:6px;">¿No recibiste el código?</p>
                <form method="POST" action="{{ route('clients.verify.resend') }}">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:var(--color-success);font-weight:600;cursor:pointer;font-size:0.875rem;">
                        Reenviar código
                    </button>
                </form>
            </div>
        @endif

        <div style="text-align:center;margin-top:1rem;">
            <a href="{{ $isRecoveryFlow ? route('clients.recovery.form') : route('clients.register.form') }}" style="font-size:0.875rem;color:var(--text-secondary);text-decoration:none;">
                <i class="fas fa-arrow-left"></i> {{ $isRecoveryFlow ? 'Volver a recuperación' : 'Volver al registro' }}
            </a>
        </div>

    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/client/clients-users.js'])
@endpush