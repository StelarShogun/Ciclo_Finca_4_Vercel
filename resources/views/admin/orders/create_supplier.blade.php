@extends('admin.layouts.sales')

@section('Titulo pagina', 'Nuevo Pedido a Proveedor - Ciclo Finca 4 Admin')

@push('styles')
    @vite([
        'resources/css/admin/shell-base.css',
        'resources/css/admin/sales/sales.css',
        'resources/css/admin/orders/orders.css',
        'resources/css/admin/orders/supplier-order-create.css',
    ])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    <div class="sales-container cf4-orders-module cf4-supplier-orders-module">

        <nav class="orders-breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('admin.supplier-orders.index') }}">Pedidos a proveedor</a>
            <span class="sep">/</span>
            <span>Nuevo pedido</span>
        </nav>

        <header class="sales-header">
            <div>
                <h1>Nuevo pedido a proveedor</h1>
                <p>Crea un pedido de compra seleccionando proveedor y productos.</p>
            </div>
            <div class="sales-actions">
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </header>

        <form id="supplier-order-create-form" method="POST" action="{{ route('admin.supplier-orders.store') }}" class="supplier-order-create">
            @csrf

            <div class="create-grid">
                {{-- Supplier --}}
                <section class="create-card" aria-labelledby="supplier-card-title">
                    <div class="create-card-head">
                        <h2 id="supplier-card-title"><i class="fas fa-truck"></i> Proveedor</h2>
                        <span class="required-pill">Obligatorio</span>
                    </div>

                    <div class="form-group">
                        <label for="supplier-search">Proveedor</label>
                        <div class="product-combobox" id="supplier-combobox">
                            <input type="text" id="supplier-search" class="product-combobox-input"
                                   placeholder="Escribe para buscar un proveedor…" autocomplete="off"
                                   aria-label="Proveedor del pedido">
                            <span class="product-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                            <div class="product-combobox-dropdown" id="supplier-dropdown" role="listbox"></div>
                        </div>
                        <input type="hidden" id="supplier_id" name="supplier_id" value="{{ old('supplier_id') }}" required>
                        @error('supplier_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="supplier-preview" class="supplier-preview" hidden></div>
                </section>

                {{-- Items --}}
                <section class="create-card create-card-wide" aria-labelledby="items-card-title">
                    <div class="create-card-head">
                        <h2 id="items-card-title"><i class="fas fa-box"></i> Productos</h2>
                        <span class="required-pill">Obligatorio</span>
                    </div>

                    <div class="items-toolbar">
                        <div class="product-combobox" id="product-combobox">
                            <input id="product-search" type="text" class="product-combobox-input"
                                   placeholder="Selecciona un proveedor primero…" autocomplete="off" disabled>
                            <span class="product-combobox-chevron"><i class="fa-solid fa-chevron-down"></i></span>
                            <div class="product-combobox-dropdown" id="product-search-dropdown"></div>
                        </div>
                    </div>

                    <div class="items-table-wrap">
                        <table class="items-table" aria-label="Líneas del pedido">
                            <thead>
                                <tr>
                                    <th style="width:46%;">Producto</th>
                                    <th class="num" style="width:16%;">Cantidad</th>
                                    <th class="num" style="width:19%;">Precio unit.</th>
                                    <th class="num" style="width:19%;">Total</th>
                                    <th style="width:1%;"></th>
                                </tr>
                            </thead>

                            <tbody id="items-body">
                                {{-- JS renders rows --}}
                            </tbody>
                        </table>
                    </div>

                    <div class="items-footer">
                        <div class="items-errors" id="items-errors" aria-live="polite">
                            @error('items')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="items-summary">
                            <div class="summary-line">
                                <span>Líneas</span>
                                <strong id="summary-lines">0</strong>
                            </div>

                            <div class="summary-line">
                                <span>Total</span>
                                <strong id="summary-total">₡0</strong>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="create-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar borrador
                </button>
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            window.__CF4_SUPPLIERS__ = @json($suppliers);
        </script>

        @vite(['resources/js/admin/shell.js', 'resources/js/admin/orders/supplier-order-create.js'])
    @endpush
@endsection

