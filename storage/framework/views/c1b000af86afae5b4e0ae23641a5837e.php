<?php $__env->startSection('title', 'Iniciar Sesión'); ?>

<?php $__env->startSection('content'); ?>
<div class="login-page-center">
    <div class="login-form-box">
        <h2 class="text-center mb-4">Iniciar Sesión</h2>
        <form id="public-login-form" method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group mb-3">
                <label for="login-email">Correo Electrónico</label>
                <input type="email" id="login-email" name="gmail" class="form-control" required placeholder="ejemplo@correo.com">
            </div>
            <div class="form-group mb-3 position-relative">
                <label for="login-password">Contraseña</label>
                <div style="position:relative;">
                    <input type="password" id="login-password" name="password" class="form-control" required placeholder="Ingresa tu contraseña" style="padding-right:40px;">
                    <button type="button" id="toggle-password" style="position:absolute;top:50%;right:8px;transform:translateY(-50%);background:none;border:none;cursor:pointer;">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            <div class="form-group mb-3">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" id="remember">
                    <span>Recordarme</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg mt-2" id="login-submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Iniciar Sesión</span>
                <span class="btn-loading hidden" id="login-loading">Iniciando...</span>
            </button>
        </form>
        <div class="login-divider my-4 text-center">
            <span>o</span>
        </div>
        <div class="oauth-buttons text-center mb-3">
            <a href="<?php echo e(route('auth.google')); ?>" class="oauth-btn google-btn">
                <i class="fab fa-google"></i>
                <span>Continuar con Google</span>
            </a>
        </div>
        <div class="login-footer text-center">
            <p>¿No tienes una cuenta? <a href="#" id="show-register-form">Regístrate aquí</a></p>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('css/clients-users.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.getElementById('toggle-password').addEventListener('click', function() {
    const input = document.getElementById('login-password');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('clientes.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/clients_users/login_user.blade.php ENDPATH**/ ?>