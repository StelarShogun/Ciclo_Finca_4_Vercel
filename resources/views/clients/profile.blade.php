@extends('clients.layouts.app')

@section('title', 'Mi Perfil')

@section('content')

    <div class="container">
        <div class="profile-wrapper">

            {{-- ── Hero ── --}}
            <div class="profile-hero">
                <div class="profile-avatar">
                    {{-- Initials from first name and first surname --}}
                    <span id="avatarInitials">
                        {{ strtoupper(substr($client->name, 0, 1)) }}{{ strtoupper(substr($client->first_surname, 0, 1)) }}
                    </span>
                </div>
                <div class="profile-hero-info">
                    <h1 id="heroName">
                        {{ $client->name }} {{ $client->first_surname }} {{ $client->second_surname }}
                    </h1>
                    <p class="profile-email">{{ $client->gmail }}</p>
                    {{-- Badge based on account type --}}
                    @if ($isGoogleOnly)
                        <span class="profile-badge profile-badge--google">
                            <i class="fab fa-google"></i> Cuenta de Google
                        </span>
                    @else
                        <span class="profile-badge profile-badge--local">
                            <i class="fas fa-envelope"></i> Cuenta local
                        </span>
                    @endif
                </div>
            </div>

            {{-- Feedback alert (success / error) --}}
            <div id="profileAlert" class="alert alert-success hidden" role="alert">
                <i id="profileAlertIcon" class="fas fa-check-circle"></i>
                <span id="profileAlertText"></span>
                <button type="button" class="profile-alert-close" onclick="closeProfileAlert()" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="profile-grid">

                {{-- ══════════════════════════════════════
                 CARD 1 · Personal iformation
            ══════════════════════════════════════ --}}
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h2>
                            <i class="fas fa-user-circle" style="color:var(--color-primary)"></i>
                            Datos Personales
                        </h2>
                        {{-- Button alternates between Edit and Save modes via JS --}}
                        <button type="button" id="btnEditarPerfil" class="btn btn-sm btn-outline-primary"
                            onclick="enableEdit()">
                            <i class="fas fa-pencil-alt"></i> Editar Perfil
                        </button>
                    </div>

                    <form id="formPerfil" action="{{ route('clients.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="profile-fields">

                            <div class="form-group">
                                <label for="name">Nombre *</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    value="{{ old('name', $client->name) }}" readonly required minlength="2" maxlength="60"
                                    placeholder="Tu nombre">
                                @error('name')
                                    <span class="profile-field-error">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="first_surname">Primer Apellido *</label>
                                <input type="text" id="first_surname" name="first_surname" class="form-control"
                                    value="{{ old('first_surname', $client->first_surname) }}" readonly required
                                    minlength="2" maxlength="60" placeholder="Tu primer apellido">
                                @error('first_surname')
                                    <span class="profile-field-error">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="second_surname">Segundo Apellido</label>
                                <input type="text" id="second_surname" name="second_surname" class="form-control"
                                    value="{{ old('second_surname', $client->second_surname) }}" readonly maxlength="60"
                                    placeholder="Opcional">
                                @error('second_surname')
                                    <span class="profile-field-error">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group profile-field-full">
                                <label for="gmail">Correo Electrónico *</label>
                                <input type="email" id="gmail" name="gmail" class="form-control"
                                    value="{{ old('gmail', $client->gmail) }}" readonly required
                                    placeholder="tu@correo.com">
                                @error('gmail')
                                    <span class="profile-field-error">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                        </div>{{-- /profile-fields --}}

                        {{-- Edit actions: hidden until edit mode is activated --}}
                        <div id="accionesEdicion" class="profile-form-actions hidden">
                            <button type="button" class="btn btn-primary" onclick="submitProfile()">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ══════════════════════════════════════
                CARD 2 · Password
                ══════════════════════════════════════ --}}
                <div class="profile-card" id="card-password">
                    <div class="profile-card-header">
                        <h2>
                            <i class="fas fa-lock" style="color:var(--color-primary)"></i>
                            {{-- Title switches based on provider; JS updates it if provider changes --}}
                            <span id="passwordCardTitle">
                                @if ($client->provider === 'google')
                                    Definir Contraseña
                                @else
                                    Cambiar Contraseña
                                @endif
                            </span>
                        </h2>
                    </div>

                    {{-- CTA: only shown when provider === 'google' --}}
                    @if ($client->provider === 'google')
                        <div id="googlePassCta" class="profile-google-cta">
                            <div class="profile-google-icon">
                                <i class="fab fa-google"></i>
                            </div>
                            <p>
                                Actualmente inicias sesión con Google.<br>
                                Puedes agregar una contraseña para usar también correo y contraseña.
                            </p>
                            <button type="button" class="btn btn-primary btn-block" onclick="showPasswordForm()">
                                <i class="fas fa-key"></i> Definir contraseña
                            </button>
                        </div>
                    @endif

                    {{-- FORM: hidden initially only for Google accounts without a password --}}
                    <form id="formPassword" action="{{ route('clients.profile.password') }}" method="POST"
                        class="{{ $client->provider === 'google' ? 'hidden' : '' }}">
                        @csrf
                        @method('PUT')

                        <div class="profile-fields">

                            {{-- Current password field: only for local accounts --}}
                            @if ($client->provider !== 'google')
                                <div class="form-group profile-field-full" id="currentPassGroup">
                                    <label for="current_password">Contraseña Actual</label>
                                    <div class="profile-input-pass">
                                        <input type="password" id="current_password" name="current_password"
                                            class="form-control" placeholder="Tu contraseña actual"
                                            autocomplete="current-password">
                                        <button type="button" class="profile-toggle-pass"
                                            onclick="togglePassword('current_password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <span class="profile-field-error">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>
                            @endif

                            {{-- New password --}}
                            <div class="form-group">
                                <label for="new_password">Nueva Contraseña</label>
                                <div class="profile-input-pass">
                                    <input type="password" id="new_password" name="new_password" class="form-control"
                                        placeholder="Mínimo 8 caracteres" minlength="8" autocomplete="new-password">
                                    <button type="button" class="profile-toggle-pass"
                                        onclick="togglePassword('new_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <div id="passStrength" class="profile-strength hidden">
                                    <div class="profile-strength-bar">
                                        <div class="profile-strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span id="strengthLabel"></span>
                                </div>

                                @error('new_password')
                                    <span class="profile-field-error">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            {{-- Confirm password --}}
                            <div class="form-group">
                                <label for="new_password_confirmation">Confirmar Contraseña</label>
                                <div class="profile-input-pass">
                                    <input type="password" id="new_password_confirmation"
                                        name="new_password_confirmation" class="form-control"
                                        placeholder="Repite la contraseña" minlength="8" autocomplete="new-password">
                                    <button type="button" class="profile-toggle-pass"
                                        onclick="togglePassword('new_password_confirmation', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                        </div>{{-- /profile-fields --}}

                        <div class="profile-form-actions">
                            <button type="submit" class="btn btn-primary" id="btnSavePassword">
                                <i class="fas fa-save"></i>
                                {{-- Button text is updated via JS if the provider changes --}}
                                @if ($client->provider === 'google')
                                    Guardar Contraseña
                                @else
                                    Actualizar Contraseña
                                @endif
                            </button>

                            @if ($client->provider === 'google')
                                <button type="button" class="btn btn-secondary" onclick="hidePasswordForm()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            @endif
                        </div>
                    </form>
                </div>

            </div>{{-- /profile-grid --}}
        </div>{{-- /profile-wrapper --}}
    </div>{{-- /container --}}
@endsection
