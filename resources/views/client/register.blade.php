@extends('client.layouts.app')

@section('hideNav')
@endsection

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('title', 'Registrar Cliente')

@section('content')
<div class="login-page-center">
    <div class="login-form-box" style="max-width:480px;">
        <h2 class="text-center mb-4">Crear Cuenta</h2>

        {{-- Display validation errors via SweetAlert on DOM ready --}}
        @if ($errors->any())
            <script>
                window.__cf4RegisterErrors = @json($errors->all());
            </script>
            @vite(['resources/js/client/register-validation-errors.js'])
        @endif

        @if (session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif

        {{-- novalidate defers all validation to JS --}}
        <form id="formRegistroCliente" method="POST" action="{{ route('clients.register') }}" novalidate>
            @csrf

            <div class="form-group mb-3">
                <label for="name">Nombre <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name"
                    class="form-control @error('name') input-error @enderror"
                    value="{{ old('name') }}" placeholder="Ej: Juan" autocomplete="given-name">
                <div id="msg-name" class="field-msg @error('name') error @enderror">
                    @error('name')
                        <i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- First and second surname rendered side by side (stacks on mobile via .surnames-row) --}}
            <div class="surnames-row" style="display:flex; gap:16px; margin-bottom:1rem;">
                <div class="form-group" style="flex:1; min-width:0; margin-bottom:0;">
                    <label for="first_surname">Apellido <span class="text-danger">*</span></label>
                    <input type="text" id="first_surname" name="first_surname"
                        class="form-control @error('first_surname') input-error @enderror"
                        value="{{ old('first_surname') }}" placeholder="Ej: Pérez" autocomplete="family-name">
                    <div id="msg-first-surname" class="field-msg @error('first_surname') error @enderror">
                        @error('first_surname')
                            <i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group" style="flex:1; min-width:0; margin-bottom:0;">
                    <label for="second_surname">Segundo Apellido</label>
                    <input type="text" id="second_surname" name="second_surname"
                        class="form-control @error('second_surname') input-error @enderror"
                        value="{{ old('second_surname') }}" placeholder="Ej: García (opcional)"
                        autocomplete="additional-name">
                    <div id="msg-second-surname" class="field-msg @error('second_surname') error @enderror">
                        @error('second_surname')
                            <i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="gmail">Correo Electrónico <span class="text-danger">*</span></label>
                <input type="email" id="gmail" name="gmail"
                    class="form-control @error('gmail') input-error @enderror"
                    value="{{ old('gmail') }}" placeholder="ejemplo@gmail.com" autocomplete="email">
                <div id="msg-gmail" class="field-msg @error('gmail') error @enderror">
                    @error('gmail')
                        <i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="password">Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password"
                        class="form-control @error('password') input-error @enderror"
                        minlength="8" placeholder="Mínimo 8 caracteres"
                        autocomplete="new-password" style="padding-right:40px;">
                    {{-- Toggle password visibility --}}
                    <button type="button" onclick="togglePass('password','eye1')"
                        style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                <div id="msg-pass" class="field-msg @error('password') error @enderror">
                    @error('password')
                        <i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group mb-4">
                <label for="password_confirmation">Verificar Contraseña <span class="text-danger">*</span></label>
                <div style="position:relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="form-control" minlength="8" placeholder="Repite la contraseña"
                        autocomplete="new-password" style="padding-right:40px;">
                    {{-- Toggle confirm password visibility --}}
                    <button type="button" onclick="togglePass('password_confirmation','eye2')"
                        style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                {{-- Populated dynamically by JS on mismatch --}}
                <div id="msg-pass-confirm" class="field-msg"></div>
            </div>

            {{-- Loading state swaps button text during form submission --}}
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
    @vite(['resources/js/client/clients-users.js'])
@endpush