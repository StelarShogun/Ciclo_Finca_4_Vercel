<aside class="admin-sidebar expanded">
    <div class="sidebar-header">
        <img src="<?php echo e(asset('assets/images/logo.png')); ?>" alt="Ciclo Finca 4 Logo" class="logo">
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="<?php echo e(url('/dashboard')); ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-label">Dashboard</span>
                </a>
            </li>
            <li class="<?php echo e(request()->routeIs('inventory') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('inventory')); ?>">
                    <i class="fas fa-box"></i>
                    <span class="sidebar-label">Inventario</span>
                </a>
            </li>
            <li class="<?php echo e(request()->routeIs('suppliers.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('suppliers.index')); ?>">
                    <i class="fas fa-truck"></i>
                    <span class="sidebar-label">Proveedores</span>
                </a>
            </li>
            <li class="<?php echo e(request()->routeIs('sales.*') ? 'active' : ''); ?>">
                <a href="<?php echo e(route('sales.index')); ?>">
                    <i class="fas fa-cash-register"></i>
                    <span class="sidebar-label">Ventas</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(url('/usuarios')); ?>">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-label">Usuarios</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(url('/orders')); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="sidebar-label">Pedidos</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(url('/moderation')); ?>">
                    <i class="fas fa-comments"></i>
                    <span class="sidebar-label">Moderación</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(url('/reports')); ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="sidebar-label">Reportes</span>
                </a>
            </li>
        </ul>
    </nav>
    <!-- Footer fijo en la parte inferior -->
    <div class="sidebar-footer">
        <?php if(auth()->guard()->check()): ?>
            <!-- Botón de Cerrar Sesión -->
            <form action="<?php echo e(route('logout')); ?>" method="POST" style="margin: 0;">
                <?php echo csrf_field(); ?>
                <button type="submit" class="logout-btn" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sidebar-label">Cerrar Sesión</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</aside>
<?php /**PATH /var/www/html/resources/views/partes/aside.blade.php ENDPATH**/ ?>