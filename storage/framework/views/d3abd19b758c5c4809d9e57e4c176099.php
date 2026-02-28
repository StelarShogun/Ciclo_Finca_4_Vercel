

<?php $__env->startSection('Titulo pagina', 'Iniciar Sesión'); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="auth-container">
        <!-- FORMULARIO DE LOGIN -->
        <div id="loginForm" class="auth-form active">
            <div class="formulario-header">
                <h1>Iniciar Sesión - Administradores</h1>
                <p>Solo los administradores pueden acceder al sistema</p>
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i>
                    <strong>Acceso Restringido:</strong> Este sistema está disponible únicamente para usuarios con rol de administrador.
                </div>
            </div>

            <div class="formulario-card">
                <!-- Mensaje de feedback -->
                <div id="mensajeFeedbackLogin" class="feedback-message hidden">
                    <i class="fas fa-info-circle"></i>
                    <span id="mensajeLoginTexto"></span>
                </div>

                <form id="formLogin" class="formulario-body" action="<?php echo e(route('login')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="loginEmail">Correo Electrónico *</label>
                        <input type="email" id="loginEmail" name="email" required placeholder="ejemplo@correo.com">
                    </div>

                    <!-- Contraseña -->
                    <div class="form-group">
                        <label for="loginPassword">Contraseña *</label>
                        <input type="password" id="loginPassword" name="password" required
                            placeholder="Ingresa tu contraseña">
                    </div>

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <button type="button" id="btnLoginSubmit" class="btn btn-primary full-width"
                            onclick="loginUsuario(event)">
                            <i class="fas fa-sign-in-alt"></i>
                            <span id="btnLoginTexto">Iniciar Sesión</span>
                            <span id="btnLoginCargando" class="hidden">Iniciando...</span>
                        </button>
                    </div>
                </form>

                <!-- Enlace para registro -->
                <div class="auth-switch">
                    <p>¿No tienes una cuenta?</p>
                    <button type="button" onclick="mostrarRegistro()" class="auth-link">
                        Regístrate aquí
                    </button>
                </div>
            </div>
        </div>

        <!-- FORMULARIO DE REGISTRO -->
        <div id="registroForm" class="auth-form">
            <div class="formulario-header">
                <h1>Registrar Nuevo Usuario</h1>
                <p>Completa el formulario para crear una cuenta</p>
            </div>

            <div class="formulario-card">
                <!-- Mensaje de feedback -->
                <div id="mensajeFeedbackRegistro" class="feedback-message hidden">
                    <i class="fas fa-info-circle"></i>
                    <span id="mensajeRegistroTexto"></span>
                </div>

                <form id="formRegistro" class="formulario-body" action="<?php echo e(route('storeLogin')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <!-- Fila de nombre y apellido -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="registroNombre">Nombre *</label>
                            <input type="text" id="registroNombre" name="nombre" required placeholder="Ej: Juan">
                        </div>
                        <div class="form-group">
                            <label for="registroApellido">Apellido *</label>
                            <input type="text" id="registroApellido" name="apellido" required placeholder="Ej: Pérez">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="registroEmail">Correo Electrónico *</label>
                        <input type="email" id="registroEmail" name="email" required placeholder="ejemplo@correo.com">
                    </div>

                    <!-- Contraseñas -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="registroPassword">Contraseña *</label>
                            <input type="password" id="registroPassword" name="password" required minlength="6"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="form-group">
                            <label for="registroPasswordConfirmation">Confirmar Contraseña *</label>
                            <input type="password" id="registroPasswordConfirmation" name="password_confirmation" required
                                minlength="6" placeholder="Repite la contraseña">
                        </div>
                    </div>

                    <!-- Acciones del formulario -->
                    <div class="form-actions">
                        <button type="submit" id="btnRegistroSubmit" class="btn btn-primary"
                            onclick="registrarUsuario(event, 'login')">
                            <i class="fas fa-user-plus"></i>
                            <span id="btnRegistroTexto">Registrar Usuario</span>
                            <span id="btnRegistroCargando" class="hidden">Registrando...</span>
                        </button>
                        <button type="button" onclick="mostrarLogin()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Volver al Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="<?php echo e(asset('estilos.php')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/usuarios.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('usuarios', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/usuarios/login.blade.php ENDPATH**/ ?>