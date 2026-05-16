@extends('admin.layouts.sales')

@section('Titulo pagina', 'Nuevo Pedido a Proveedor - Ciclo Finca 4 Admin')

@push('styles')
    @vite([
        'resources/css/admin/sales/sales.css',
        'resources/css/admin/orders/orders.css',
        'resources/css/admin/orders/supplier-order-create.css'
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

        @component('admin.partials.page-header', [
            'title' => 'Nuevo pedido a proveedor',
            'description' => 'Crea un pedido de compra seleccionando proveedor, productos y una fecha estimada de entrega.',
        ])
            @slot('actions')
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            @endslot
        @endcomponent

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
                        <label for="supplier_id">Proveedor</label>
                        <select id="supplier_id" name="supplier_id" required>
                            <option value="">Selecciona un proveedor…</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->supplier_id }}" {{ old('supplier_id') == $s->supplier_id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('supplier_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="supplier-preview" class="supplier-preview" hidden></div>
                </section>

                {{-- Delivery date --}}
                <section class="create-card" aria-labelledby="date-card-title">
                    <div class="create-card-head">
                        <h2 id="date-card-title"><i class="fas fa-calendar-alt"></i> Entrega estimada</h2>
                        <span class="required-pill">Obligatorio</span>
                    </div>

                    <div class="form-group">
                        <label for="estimated_delivery_date">Fecha estimada</label>
                        <input 
                            type="date" 
                            id="estimated_delivery_date" 
                            name="estimated_delivery_date"
                            value="{{ old('estimated_delivery_date') }}"
                            min="{{ now()->addDay()->toDateString() }}" 
                            required
                        >

                        @error('estimated_delivery_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Items --}}
                <section class="create-card create-card-wide" aria-labelledby="items-card-title">
                    <div class="create-card-head">
                        <h2 id="items-card-title"><i class="fas fa-box"></i> Productos</h2>
                        <span class="required-pill">Obligatorio</span>
                    </div>

                    <div class="items-toolbar">
                        <div class="product-combobox" id="product-combobox">
                            <input 
                                id="product-search" 
                                type="text" 
                                class="product-combobox-input"
                                placeholder="Selecciona un proveedor primero…" 
                                autocomplete="off" 
                                disabled
                            >

                            <span class="product-combobox-chevron">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>

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

                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            window.__CF4_SUPPLIERS__ = @json($suppliers);
        </script>

        @vite(['resources/js/admin/orders/supplier-order-create.js'])
    @endpush
@endsection
