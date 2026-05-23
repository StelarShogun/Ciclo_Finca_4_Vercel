@extends('admin.layouts.sales')

@section('Titulo pagina', 'Importar XML de proveedor – Admin')

@push('styles')
    @vite(['resources/css/admin/shell-base.css', 'resources/css/admin/sales/sales.css', 'resources/css/admin/orders/orders.css'])
    <style>
        /* ── Upload card ── */
        .xml-upload-card {
            background: var(--color-surface, #fff);
            border: 1px solid var(--color-border, #e5e7eb);
            border-radius: var(--radius-md, 10px);
            padding: 2rem 2.25rem;
            max-width: 560px;
            margin: 0 auto;
        }
        .xml-upload-card h2 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: .25rem;
            color: var(--color-text-primary, #111827);
        }
        .xml-upload-card p.subtitle {
            font-size: .875rem;
            color: var(--color-text-secondary, #6b7280);
            margin-bottom: 1.5rem;
        }
        .xml-field-group {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .xml-field-group label {
            font-size: .875rem;
            font-weight: 500;
            color: var(--color-text-primary, #111827);
            display: block;
            margin-bottom: .35rem;
        }
        .xml-field-group input[type="file"],
        .xml-field-group input[type="number"],
        .xml-field-group select {
            width: 100%;
            padding: .55rem .75rem;
            border: 1px solid var(--color-border, #d1d5db);
            border-radius: var(--radius-sm, 6px);
            font-size: .9rem;
            background: var(--color-input-bg, #f9fafb);
            color: var(--color-text-primary, #111827);
            box-sizing: border-box;
        }
        .xml-field-group .field-hint {
            font-size: .8rem;
            color: var(--color-text-secondary, #6b7280);
            margin-top: .3rem;
        }
        .xml-form-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: var(--radius-sm, 6px);
            padding: .65rem 1rem;
            color: #b91c1c;
            font-size: .875rem;
            margin-bottom: 1rem;
        }
        .xml-actions {
            display: flex;
            gap: .75rem;
            align-items: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
    </style>
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('header')
    @component('admin.partials.page-header', [
        'title' => 'Importar XML de proveedor',
        'description' => 'Carga un archivo XML del proveedor para comparar los precios de compra actuales antes de aplicar cambios.',
    ])
        @slot('actions')
            <div class="sales-header-actions">
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver a pedidos
                </a>
            </div>
        @endslot
    @endcomponent
@endsection

@section('contenido')
<div class="sales-container" style="padding-top: 1.5rem;">

    <nav class="orders-breadcrumb" aria-label="Migas de pan">
        <a href="{{ route('admin.supplier-orders.index') }}">Pedidos a proveedor</a>
        <span class="sep">/</span>
        <span>Importar XML</span>
    </nav>

    <div class="xml-upload-card">
        <h2><i class="fas fa-file-import" style="color:var(--color-primary,#2563eb);margin-right:.4rem;"></i>Seleccionar archivo XML</h2>
        <p class="subtitle">
            El sistema comparará los precios del XML contra el precio de compra actual de cada
            producto y le mostrará las diferencias antes de aplicar cualquier cambio.
        </p>

        @if ($errors->any())
            <div class="xml-form-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.supplier-orders.xml-deviation.analyse') }}"
            enctype="multipart/form-data"
            id="xml-upload-form"
        >
            @csrf

            <div class="xml-field-group">

                <x-cf-file-upload
                    id="xml_file"
                    name="xml_file"
                    label="Archivo XML del proveedor"
                    accept=".xml,text/xml,application/xml"
                    :required="true"
                    icon="fa-file-code"
                    meta-id="xml_file-meta"
                    hint="Tamaño máximo: 5 MB. Solo archivos .xml.">
                    Haz clic o arrastra el archivo XML aquí
                </x-cf-file-upload>

                {{-- Threshold --}}
                <div>
                    <label for="threshold">Umbral de desvío (%) <span style="color:#ef4444;">*</span></label>
                    <input
                        type="number"
                        id="threshold"
                        name="threshold"
                        value="{{ old('threshold', 10) }}"
                        min="0"
                        max="100"
                        step="0.5"
                        required
                    >
                    <p class="field-hint">
                        Variación mínima para marcar un producto como desvío. Por ejemplo, <strong>10</strong>
                        significa que sólo se resaltarán productos con un cambio de precio ≥ 10%.
                    </p>
                </div>

            </div>{{-- /.xml-field-group --}}

            <div class="xml-actions">
                <button type="submit" class="btn btn-primary" id="xml-submit-btn">
                    <i class="fas fa-search"></i> Analizar XML
                </button>
                <a href="{{ route('admin.supplier-orders.index') }}" class="btn btn-ghost">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.getElementById('xml-upload-form').addEventListener('submit', function () {
    const btn = document.getElementById('xml-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando…';
});
</script>
@endpush
