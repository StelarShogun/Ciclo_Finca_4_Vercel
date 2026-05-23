<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Ciclo Finca 4 Admin</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Styles & Fonts --}}
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/components/page-header.css', 'resources/css/admin/dashboard/dashboard.css'])
</head>

<body class="admin-layout">

    {{-- Sidebar navigation --}}
    @include('admin.parts.aside')

    <main class="admin-main admin-main--content">
        <div class="admin-content-wrapper">
            <div class="dashboard-container">

            {{-- ==================== HEADER ==================== --}}
            @component('admin.partials.page-header', ['title' => 'Panel de control'])
                <p>Consulta los indicadores principales de ventas, inventario, proveedores y actividad reciente del sistema.
                </p>
                <p class="current-time" id="current-time"></p>

                @slot('actions')
                    <div class="header-actions">
                        <button class="btn btn-primary" id="refresh-dashboard">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>

                        <button class="btn btn-secondary" id="btn-open-weekly-report-modal">
                            <i class="fas fa-envelope"></i>
                            Reporte semanal
                        </button>
                    </div>
                @endslot
            @endcomponent
            {{-- Data load error notice --}}
            @if (isset($error))
                <div class="alert alert-warning alert-inline-error">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                </div>
            @endif

            {{-- ==================== KPI CARDS ==================== --}}
            <section class="kpis-section">

                {{-- Total products --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Total Productos</h3>
                        <div class="kpi-value" id="total-products">{{ $totalProducts ?? 0 }}</div>
                        <div class="kpi-change positive" id="products-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12%</span>
                        </div>
                    </div>
                </div>

                {{-- Today's sales --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Ventas Hoy</h3>
                        <div class="kpi-value" id="today-sales">₡{{ number_format($todaySales ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="kpi-change positive" id="sales-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8%</span>
                        </div>
                    </div>
                </div>

                {{-- Total suppliers --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Proveedores</h3>
                        <div class="kpi-value" id="total-suppliers">{{ $totalSuppliers ?? 0 }}</div>
                        <div class="kpi-change neutral" id="suppliers-change">
                            <i class="fas fa-minus"></i>
                            <span>0%</span>
                        </div>
                    </div>
                </div>

                {{-- Low stock alert --}}
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Stock Bajo</h3>
                        <div class="kpi-value" id="low-stock">{{ $lowStockProducts ?? 0 }}</div>
                        <div class="kpi-change negative" id="stock-change">
                            <i class="fas fa-arrow-down"></i>
                            <span>-3%</span>
                        </div>
                    </div>
                </div>

            </section>

            {{-- ==================== CHARTS ==================== --}}
            <section class="charts-section">

                {{-- Sales trend chart with period toggle --}}
                <div class="chart-container chart-container--sales">
                    <div class="chart-header">
                        <h3>Ventas de los Últimos 7 Días</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-period="7d">7 días</button>
                            <button class="chart-btn" data-period="30d">30 días</button>
                            <button class="chart-btn" data-period="90d">90 días</button>
                        </div>
                    </div>
                    <div class="chart-wrapper chart-wrapper--sales">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>

                {{-- Product distribution by category --}}
                <div class="chart-container chart-container--category">
                    <div class="chart-header">
                        <h3>Productos por Categoría</h3>
                    </div>
                    <div class="category-chart-body">
                        <div class="chart-wrapper chart-wrapper--category-donut">
                            <canvas id="category-chart"></canvas>
                        </div>
                        <div id="category-chart-legend" class="category-chart-legend" role="list"
                            aria-label="Leyenda de categorías"></div>
                    </div>
                </div>

            </section>

            {{-- ==================== DATA TABLES ==================== --}}
            <section class="tables-section">

                {{-- Low stock products table (top 10 strictly below stock_minimum) --}}
                <div class="table-container">
                    <div class="table-header">
                        <h3>
                            Top 10 Productos con Stock Bajo
                            @if (($lowStockProducts ?? 0) > 0)
                                <span class="badge-count">{{ $lowStockProducts }}</span>
                            @endif
                        </h3>
                        <a href="{{ route('inventory') }}" class="btn btn-sm btn-primary">
                            Ver Todos
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content table-content--scroll">
                        <div class="table-scroll-wrapper">
                            <table class="dashboard-table admin-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="low-stock-table" class="tbody-scroll">
                                    @forelse(($lowStockProductsList ?? collect())->take(10) as $product)
                                        <tr>
                                            <td>
                                                <div class="product-info">
                                                    <img src="{{ asset('assets/images/products/' . ($product->image ?? 'default-96.webp')) }}"
                                                        alt="{{ $product->name }}" class="product-thumb"
                                                        width="48" height="48" loading="lazy" decoding="async">
                                                    <span>{{ $product->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="stock-badge danger">{{ $product->stock_current }}</span>
                                            </td>
                                            <td>{{ $product->stock_minimum }}</td>
                                            <td>
                                                @php
                                                    $pct =
                                                        $product->stock_minimum > 0
                                                            ? round(
                                                                ($product->stock_current / $product->stock_minimum) *
                                                                    100,
                                                            )
                                                            : 0;
                                                @endphp
                                                <span class="status-badge {{ $pct <= 0 ? 'danger' : 'warning' }}"
                                                    title="{{ $pct }}% del mínimo requerido">
                                                    {{ $pct <= 0 ? 'Sin Stock' : 'Stock Bajo (' . $pct . '%)' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-check-circle"></i>
                                                    <p>No hay productos con stock bajo</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Recent sales table --}}
                <div class="table-container">
                    <div class="table-header">
                        <h3>Ventas Recientes</h3>
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-primary">
                            Ver Todas
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-content table-content--scroll">
                        <div class="table-scroll-wrapper">
                            <table class="dashboard-table admin-table">
                                <thead>
                                    <tr>
                                        <th>Factura</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Fecha de venta</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-sales-table" class="tbody-scroll">
                                    @forelse(($recentSales ?? collect())->take(10) as $sale)
                                        <tr>
                                            <td>{{ $sale->invoice_number ?? '#' . $sale->sale_id }}</td>
                                            <td>
                                                @if ($sale->client)
                                                    {{ trim($sale->client->name . ' ' . $sale->client->first_surname . ' ' . ($sale->client->second_surname ?? '')) }}
                                                @elseif($sale->buyer_name)
                                                    {{ $sale->buyer_name }}
                                                @else
                                                    Mostrador / sin datos
                                                @endif
                                            </td>
                                            <td>₡{{ number_format($sale->total, 0, ',', '.') }}</td>
                                            <td>{{ $sale->adminSaleDateLabel() }}</td>
                                            <td>
                                                @php
                                                    $statusLabels = [
                                                        'completed' => 'Completada',
                                                        'pending' => 'Pendiente',
                                                        'ready_to_pickup' => 'Por recoger',
                                                        'cancelled' => 'Cancelada',
                                                        'canceled' => 'Cancelada',
                                                        'refunded' => 'Reembolsada',
                                                        'returned' => 'Devuelta',
                                                    ];

                                                    $statusText =
                                                        $statusLabels[$sale->status] ?? ucfirst($sale->status);

                                                    $statusBadgeClass = match ($sale->status) {
                                                        'completed' => 'success',
                                                        'pending', 'ready_to_pickup' => 'warning',
                                                        default => 'danger',
                                                    };
                                                @endphp
                                                <span class="status-badge {{ $statusBadgeClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    <p>No hay ventas recientes</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>

            {{-- ==================== QUICK ACTIONS ==================== --}}
            <section class="quick-actions">
                <h3>Acciones Rápidas</h3>
                <div class="actions-grid">

                    <a href="{{ route('inventory') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-plus"></i></div>
                        <div class="action-content">
                            <h4>Gestionar Productos</h4>
                            <p>Agregar y administrar productos del inventario</p>
                        </div>
                    </a>

                    <a href="{{ route('sales.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-cash-register"></i></div>
                        <div class="action-content">
                            <h4>Gestionar Ventas</h4>
                            <p>Procesar y administrar ventas del sistema</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.index') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="action-content">
                            <h4>Ver Reportes</h4>
                            <p>Ingresar al módulo de reportes del sistema</p>
                        </div>
                    </a>

                    <a href="{{ route('suppliers.create') }}" class="action-card">
                        <div class="action-icon"><i class="fas fa-truck"></i></div>
                        <div class="action-content">
                            <h4>Nuevo proveedor</h4>
                            <p>Registrar un proveedor en el sistema</p>
                        </div>
                    </a>

                </div>
            </section>

            </div>
        </div>
    </main>

    {{-- Dashboard scripts --}}
    @vite(['resources/js/admin/shell.js', 'resources/js/admin/dashboard/dashboard.js'])


    {{-- ==================== LOW-STOCK TOAST ==================== --}}
    @if (($lowStockProducts ?? 0) > 0)
        <div id="low-stock-toast" class="ls-toast ls-toast--visible" role="alert" aria-live="assertive">
            <div class="ls-toast__icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="ls-toast__body">
                <strong class="ls-toast__title">Alerta de inventario</strong>
                <p class="ls-toast__msg">
                    {{ $lowStockProducts }} producto{{ $lowStockProducts > 1 ? 's' : '' }}
                    {{ $lowStockProducts > 1 ? 'están' : 'está' }} por debajo del stock mínimo configurado.
                </p>
                <a href="{{ route('inventory') }}" class="ls-toast__link">
                    Ver inventario <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <button class="ls-toast__close" id="close-low-stock-toast" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <script>
            (function() {
                var toast = document.getElementById('low-stock-toast');
                var closeBtn = document.getElementById('close-low-stock-toast');
                if (!toast) return;

                function hideToast() {
                    toast.classList.add('ls-toast--hiding');
                    toast.addEventListener('transitionend', function() {
                        toast.remove();
                    }, {
                        once: true
                    });
                }

                var autoTimer = setTimeout(hideToast, 7000);

                closeBtn.addEventListener('click', function() {
                    clearTimeout(autoTimer);
                    hideToast();
                });
            })();
        </script>
    @endif


    {{-- ==================== CONFIRM TOAST (success / error) ==================== --}}
    <div id="cf4-toast" class="cf4-toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="cf4-toast__icon-wrap">
            <i class="cf4-toast__icon fas"></i>
        </div>
        <div class="cf4-toast__body">
            <strong class="cf4-toast__title"></strong>
            <p class="cf4-toast__msg"></p>
        </div>
        <button class="cf4-toast__close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>


    {{-- ==================== WEEKLY REPORT MODAL ==================== --}}
    <div id="weekly-report-modal" class="wr-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true"
        aria-labelledby="wr-modal-title">
        <div class="wr-modal-panel">

            {{-- Header --}}
            <div class="wr-modal-header">
                <div class="wr-modal-header__icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <h3 class="wr-modal-header__title" id="wr-modal-title">Reporte semanal automático</h3>
                    <p class="wr-modal-header__sub">Configure el envío periódico de KPIs del dashboard</p>
                </div>
                <button type="button" class="wr-modal-close" id="btn-close-weekly-report-modal"
                    aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="wr-modal-body">

                <form id="weekly-report-settings-form" novalidate>
                    @csrf
                    @method('PUT')

                    {{-- ── Sección: Programación ── --}}
                    <div class="wr-section">
                        <div class="wr-section__label">
                            <i class="fas fa-calendar-alt"></i>
                            Programación del envío
                        </div>

                        <div class="wr-fields-row">
                            {{-- Día --}}
                            <div class="wr-field">
                                <label class="wr-label" for="weekly_report_day">Día</label>
                                <select class="wr-select" id="weekly_report_day" name="weekly_report_day">
                                    @php
                                        $dayLabels = [
                                            0 => 'Domingo',
                                            1 => 'Lunes',
                                            2 => 'Martes',
                                            3 => 'Miércoles',
                                            4 => 'Jueves',
                                            5 => 'Viernes',
                                            6 => 'Sábado',
                                        ];
                                    @endphp
                                    @foreach ($dayLabels as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ ($weeklyReportDay ?? 1) === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Hora --}}
                            <div class="wr-field wr-field--sm">
                                <label class="wr-label" for="weekly_report_hour">Hora</label>
                                <div class="wr-time-input">
                                    <input class="wr-input" type="number" id="weekly_report_hour"
                                        name="weekly_report_hour" min="0" max="23" step="1"
                                        placeholder="HH"
                                        value="{{ old('weekly_report_hour', $weeklyReportHour ?? 8) }}">
                                    <span class="wr-time-sep">:</span>
                                    <input class="wr-input" type="number" id="weekly_report_minute"
                                        name="weekly_report_minute" min="0" max="59" step="1"
                                        placeholder="MM"
                                        value="{{ old('weekly_report_minute', $weeklyReportMinute ?? 0) }}">
                                </div>
                                <p id="weekly-report-hour-error" class="wr-field-error" role="alert"></p>
                            </div>
                        </div>

                        <p class="wr-hint">
                            <i class="fas fa-info-circle"></i>
                            El reporte se enviará automáticamente cada semana en el día y hora indicados.
                        </p>
                    </div>

                    {{-- ── Sección: Destinatarios ── --}}
                    <div class="wr-section">
                        <div class="wr-section__label">
                            <i class="fas fa-users"></i>
                            Destinatarios
                        </div>

                        <div id="wr-recipients-list" class="wr-recipients-list">
                            {{-- Se generan dinámicamente desde PHP / JS --}}
                            @php
                                $recipients = $weeklyReportRecipients ?? [];
                                if (empty($recipients)) {
                                    $recipients = [''];
                                }
                            @endphp
                            @foreach ($recipients as $email)
                                <div class="wr-recipient-row">
                                    <div class="wr-recipient-input-wrap">
                                        <i class="fas fa-envelope wr-recipient-icon"></i>
                                        <input class="wr-input wr-recipient-input" type="email"
                                            name="weekly_report_recipients[]" placeholder="correo@ejemplo.com"
                                            value="{{ $email }}" autocomplete="email">
                                    </div>
                                    <button type="button" class="wr-recipient-remove"
                                        aria-label="Eliminar destinatario" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <p id="weekly-report-recipients-error" class="wr-field-error" role="alert"></p>

                        <button type="button" id="wr-add-recipient" class="wr-add-btn">
                            <i class="fas fa-plus-circle"></i>
                            Añadir destinatario
                        </button>
                    </div>

                    <p id="weekly-report-form-error" class="wr-form-error" role="alert"></p>

                    {{-- Footer --}}
                    <div class="wr-modal-footer">
                        <button type="button" class="btn btn-secondary" id="btn-cancel-weekly-report-modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="weekly-report-submit">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>

                </form>

            </div>{{-- /wr-modal-body --}}
        </div>{{-- /wr-modal-panel --}}
    </div>{{-- /wr-modal-overlay --}}


    <script>
        (function() {
            'use strict';

            /* ── DOM refs ─────────────────────────────────────────────────── */
            var modal = document.getElementById('weekly-report-modal');
            var openBtn = document.getElementById('btn-open-weekly-report-modal');
            var closeBtn = document.getElementById('btn-close-weekly-report-modal');
            var cancelBtn = document.getElementById('btn-cancel-weekly-report-modal');
            var form = document.getElementById('weekly-report-settings-form');
            var submitBtn = document.getElementById('weekly-report-submit');
            var formError = document.getElementById('weekly-report-form-error');
            var hourError = document.getElementById('weekly-report-hour-error');
            var rcptError = document.getElementById('weekly-report-recipients-error');
            var recipientList = document.getElementById('wr-recipients-list');
            var addBtn = document.getElementById('wr-add-recipient');
            var actionUrl = '{{ route('admin.orders.settings.weekly-report.update') }}';
            var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            /* ── Toast ────────────────────────────────────────────────────── */
            var toast = document.getElementById('cf4-toast');
            var toastIcon = toast.querySelector('.cf4-toast__icon');
            var toastTitle = toast.querySelector('.cf4-toast__title');
            var toastMsg = toast.querySelector('.cf4-toast__msg');
            var toastClose = toast.querySelector('.cf4-toast__close');
            var toastTimer = null;

            function showToast(type, title, msg) {
                // type: 'success' | 'error'
                toast.className = 'cf4-toast cf4-toast--' + type;
                toastIcon.className = 'cf4-toast__icon fas ' + (type === 'success' ? 'fa-check-circle' :
                    'fa-exclamation-circle');
                toastTitle.textContent = title;
                toastMsg.textContent = msg;

                // Trigger reflow so animation replays
                void toast.offsetWidth;
                toast.classList.add('cf4-toast--visible');

                clearTimeout(toastTimer);
                toastTimer = setTimeout(function() {
                    hideToast();
                }, 5000);
            }

            function hideToast() {
                toast.classList.remove('cf4-toast--visible');
            }

            toastClose.addEventListener('click', function() {
                clearTimeout(toastTimer);
                hideToast();
            });

            /* ── Modal open / close ───────────────────────────────────────── */
            function openModal() {
                modal.classList.add('wr-modal-overlay--active');
                modal.removeAttribute('aria-hidden');
                // Focus first input for accessibility
                var first = modal.querySelector('select, input');
                if (first) setTimeout(function() {
                    first.focus();
                }, 80);
            }

            function closeModal() {
                modal.classList.remove('wr-modal-overlay--active');
                modal.setAttribute('aria-hidden', 'true');
            }

            function clearErrors() {
                [formError, hourError, rcptError].forEach(function(el) {
                    el.textContent = '';
                    el.classList.remove('wr-field-error--visible');
                });
                // Remove per-input error states
                recipientList.querySelectorAll('.wr-recipient-input').forEach(function(inp) {
                    inp.classList.remove('wr-input--error');
                });
            }

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('wr-modal-overlay--active')) closeModal();
            });

            /* ── Recipient rows ───────────────────────────────────────────── */
            function updateRemoveButtons() {
                var rows = recipientList.querySelectorAll('.wr-recipient-row');
                rows.forEach(function(row) {
                    var btn = row.querySelector('.wr-recipient-remove');
                    // Always allow removal (user can leave 0 and we validate on submit)
                    btn.disabled = rows.length === 1;
                    btn.style.opacity = rows.length === 1 ? '0.3' : '1';
                });
            }

            function addRecipientRow(value) {
                var row = document.createElement('div');
                row.className = 'wr-recipient-row wr-recipient-row--new';

                row.innerHTML =
                    '<div class="wr-recipient-input-wrap">' +
                    '<i class="fas fa-envelope wr-recipient-icon"></i>' +
                    '<input class="wr-input wr-recipient-input" type="email" name="weekly_report_recipients[]" ' +
                    'placeholder="correo@ejemplo.com" value="' + (value || '') + '" autocomplete="email">' +
                    '</div>' +
                    '<button type="button" class="wr-recipient-remove" aria-label="Eliminar destinatario" title="Eliminar">' +
                    '<i class="fas fa-trash-alt"></i>' +
                    '</button>';

                recipientList.appendChild(row);

                // Animate in
                requestAnimationFrame(function() {
                    row.classList.remove('wr-recipient-row--new');
                });

                row.querySelector('.wr-recipient-remove').addEventListener('click', function() {
                    removeRow(row);
                });

                updateRemoveButtons();

                var input = row.querySelector('input');
                input.focus();
                return input;
            }

            function removeRow(row) {
                row.classList.add('wr-recipient-row--removing');
                row.addEventListener('transitionend', function() {
                    row.remove();
                    updateRemoveButtons();
                }, {
                    once: true
                });
            }

            // Wire up existing remove buttons (PHP-rendered rows)
            recipientList.querySelectorAll('.wr-recipient-remove').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    removeRow(btn.closest('.wr-recipient-row'));
                });
            });

            updateRemoveButtons();

            addBtn.addEventListener('click', function() {
                addRecipientRow('');
            });

            /* ── Form submit ──────────────────────────────────────────────── */
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                clearErrors();

                // Client-side validation: at least one valid email
                var inputs = recipientList.querySelectorAll('.wr-recipient-input');
                var validEmails = [];
                var hasInvalid = false;

                inputs.forEach(function(inp) {
                    var val = inp.value.trim();
                    if (val === '') return; // skip empty
                    // Basic email regex
                    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                        validEmails.push(val);
                    } else {
                        inp.classList.add('wr-input--error');
                        hasInvalid = true;
                    }
                });

                if (hasInvalid) {
                    showFieldError(rcptError, 'Uno o más correos tienen un formato inválido.');
                    return;
                }

                if (validEmails.length === 0) {
                    showFieldError(rcptError, 'Ingrese al menos un correo electrónico válido.');
                    recipientList.querySelector('.wr-recipient-input')?.focus();
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

                // Build FormData from the form + override recipients with deduplicated values
                var fd = new FormData(form);
                // Remove all recipients[] entries added by FormData (may include empties)
                fd.delete('weekly_report_recipients[]');
                // Re-add only validated unique emails
                var unique = validEmails.filter(function(v, i, a) {
                    return a.indexOf(v) === i;
                });
                unique.forEach(function(email) {
                    fd.append('weekly_report_recipients[]', email);
                });

                // The controller expects a single string field; join to match validation
                fd.delete('weekly_report_recipients');
                fd.append('weekly_report_recipients', unique.join(','));

                try {
                    var response = await fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: fd,
                    });

                    var json = await response.json();

                    if (response.ok) {
                        closeModal();
                        showToast('success', '¡Configuración guardada!', json.message ??
                            'El reporte semanal ha sido actualizado correctamente.');
                    } else {
                        var errors = json.errors ?? {};

                        if (errors.weekly_report_hour || errors.weekly_report_minute) {
                            showFieldError(hourError, (errors.weekly_report_hour ?? errors
                                .weekly_report_minute)[0]);
                        }
                        if (errors.weekly_report_recipients) {
                            showFieldError(rcptError, errors.weekly_report_recipients[0]);
                        }

                        var generalMsg = json.message ?? 'Error al guardar la configuración.';
                        showFieldError(formError, generalMsg);
                        showToast('error', 'Error al guardar', generalMsg);
                    }
                } catch (err) {
                    var netMsg = 'Error de red. Por favor, inténtelo de nuevo.';
                    showFieldError(formError, netMsg);
                    showToast('error', 'Error de conexión', netMsg);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
                }
            });

            function showFieldError(el, msg) {
                el.textContent = msg;
                el.classList.add('wr-field-error--visible');
            }

        })();
    </script>

</body>

</html>
