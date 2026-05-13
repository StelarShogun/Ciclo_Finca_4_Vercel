@extends('client.layouts.app')

@section('title', 'Mi Perfil')

@push('styles')
    @vite(['resources/css/client/clients-users.css'])
@endpush

@section('content')

<div class="profile-shell">
{{-- Hero alineado al look del catálogo (CF4) --}}
<div class="profile-header">
    <div class="container">
        <h1 class="profile-header-title">Mi Perfil</h1>
        <p class="profile-header-subtitle">Gestioná tu información personal y seguridad</p>
    </div>
</div>

<div class="container">
    <div class="profile-wrapper">

        {{-- Hero: avatar, datos y accesos (favoritos / notificaciones) en la misma tarjeta --}}
        <div class="profile-hero">
            <div class="profile-avatar">
                <span id="avatarInitials">
                    {{ strtoupper(substr($client->name, 0, 1)) }}{{ strtoupper(substr($client->first_surname, 0, 1)) }}
                </span>
            </div>
            <div class="profile-hero-info">
                <h1 id="heroName">
                    {{ $client->name }} {{ $client->first_surname }} {{ $client->second_surname }}
                </h1>
                <p class="profile-email">{{ $client->gmail }}</p>
                <div class="profile-hero-meta">
                    @if ($isGoogleOnly)
                        <span class="profile-badge profile-badge--google">
                            <i class="fab fa-google"></i> Cuenta de Google
                        </span>
                    @else
                        <span class="profile-badge profile-badge--local">
                            <i class="fas fa-envelope"></i> Cuenta local
                        </span>
                    @endif
                    <nav class="profile-quick-actions" aria-label="Accesos rápidos">
                        <button type="button"
                                class="profile-quick-action profile-quick-action--favorites cf4-favorites-open-trigger">
                            <i class="fas fa-heart" aria-hidden="true"></i>
                            <span>Mis favoritos</span>
                        </button>
                        <a href="{{ route('clients.notifications') }}" class="profile-quick-action profile-quick-action--notifications">
                            <i class="fas fa-bell" aria-hidden="true"></i>
                            <span>Notificaciones</span>
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        {{-- Alerta de feedback, poblada por JS tras envíos --}}
        <div id="profileAlert" class="alert alert-success hidden" role="alert">
            <i id="profileAlertIcon" class="fas fa-check-circle"></i>
            <span id="profileAlertText"></span>
            <button type="button" class="profile-alert-close" onclick="closeProfileAlert()" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="profile-grid">

            {{-- CARD 1 · Datos personales --}}
            <div class="profile-card">
                <div class="profile-card-header">
                    <h2>
                        <i class="fas fa-user-circle" style="color:var(--color-primary)"></i>
                        Datos Personales
                    </h2>
                    <button type="button" id="btnEditarPerfil" class="btn btn-sm btn-outline-primary"
                        onclick="enableEdit()">
                        <i class="fas fa-pencil-alt"></i>
                        <span>Editar</span>
                    </button>
                </div>

                <form id="formPerfil" action="{{ route('clients.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="profile-fields">

                        <div class="form-group">
                            <label for="name">Nombre *</label>
                            <input type="text" id="name" name="name" class="form-control"
                                value="{{ old('name', $client->name) }}" readonly required
                                minlength="2" maxlength="60" placeholder="Tu nombre">
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
                                value="{{ old('second_surname', $client->second_surname) }}" readonly
                                maxlength="60" placeholder="Opcional">
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

            {{-- CARD 2 · Contraseña --}}
            <div class="profile-card" id="card-password">
                <div class="profile-card-header">
                    <h2>
                        <i class="fas fa-lock" style="color:var(--color-primary)"></i>
                        <span id="passwordCardTitle">
                            @if ($client->provider === 'google')
                                Definir Contraseña
                            @else
                                Cambiar Contraseña
                            @endif
                        </span>
                    </h2>
                </div>

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

                <form id="formPassword" action="{{ route('clients.profile.password') }}" method="POST"
                    class="{{ $client->provider === 'google' ? 'hidden' : '' }}">
                    @csrf
                    @method('PUT')

                    <div class="profile-fields">

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

</div>{{-- /profile-shell --}}

@endsection

@push('scripts')
    @vite(['resources/js/client/clients-users.js'])
@endpush