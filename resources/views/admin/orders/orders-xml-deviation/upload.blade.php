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

        /* FIX: el layout sales define min-height y padding pensados para listas
           largas; en esta vista solo hay una card pequeña, así que los anulamos. */
        .sales-container {
            min-height: unset !important;
            height: auto !important;
            padding-bottom: 2rem;
        }
    </style>
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
<div class="sales-container">

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

    <nav class="reports-breadcrumb" aria-label="Migas de pan">
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

        <div id="xml-client-error" class="xml-form-error" role="alert" hidden></div>

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

        <details class="xml-formats-help" style="margin-top:1.5rem;">
            <summary style="cursor:pointer;font-size:.875rem;font-weight:500;color:var(--color-text-secondary,#6b7280);">
                <i class="fas fa-info-circle" style="margin-right:.35rem;"></i>Formatos XML aceptados
            </summary>
            <div style="margin-top:.75rem;font-size:.8rem;color:var(--color-text-secondary,#6b7280);display:flex;flex-direction:column;gap:.75rem;">
                <div>
                    <strong style="color:var(--color-text-primary,#111827);">Formato A — genérico <code>&lt;items&gt;</code></strong>
                    <pre style="background:#f3f4f6;border-radius:6px;padding:.6rem .85rem;margin:.35rem 0 0;font-size:.75rem;overflow-x:auto;">&lt;items&gt;
  &lt;item&gt;
    &lt;code&gt;ACT-001&lt;/code&gt;
    &lt;name&gt;Aceite 10W-40&lt;/name&gt;
    &lt;quantity&gt;10&lt;/quantity&gt;
    &lt;unit_price&gt;11200.00&lt;/unit_price&gt;
  &lt;/item&gt;
&lt;/items&gt;</pre>
                </div>
                <div>
                    <strong style="color:var(--color-text-primary,#111827);">Formato B — <code>&lt;products&gt;</code></strong>
                    <pre style="background:#f3f4f6;border-radius:6px;padding:.6rem .85rem;margin:.35rem 0 0;font-size:.75rem;overflow-x:auto;">&lt;products&gt;
  &lt;product&gt;
    &lt;sku&gt;ACT-001&lt;/sku&gt;
    &lt;description&gt;Aceite 10W-40&lt;/description&gt;
    &lt;qty&gt;10&lt;/qty&gt;
    &lt;price&gt;11200.00&lt;/price&gt;
  &lt;/product&gt;
&lt;/products&gt;</pre>
                </div>
                <div>
                    <strong style="color:var(--color-text-primary,#111827);">Formato C — factura electrónica Costa Rica</strong>
                    <pre style="background:#f3f4f6;border-radius:6px;padding:.6rem .85rem;margin:.35rem 0 0;font-size:.75rem;overflow-x:auto;">&lt;invoice&gt;
  &lt;line&gt;
    &lt;CodigoComercial&gt;ACT-001&lt;/CodigoComercial&gt;
    &lt;Detalle&gt;Aceite 10W-40&lt;/Detalle&gt;
    &lt;Cantidad&gt;10&lt;/Cantidad&gt;
    &lt;PrecioUnitario&gt;11200.00&lt;/PrecioUnitario&gt;
  &lt;/line&gt;
&lt;/invoice&gt;</pre>
                </div>
                <p style="margin:0;">El SKU del XML debe coincidir con el SKU del producto en el sistema (o con el código generado <code>BK-{id}</code>).</p>
            </div>
        </details>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.getElementById('xml-upload-form').addEventListener('submit', function (e) {
    const fileInput = document.getElementById('xml_file');
    const errorEl  = document.getElementById('xml-client-error');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        if (errorEl) {
            errorEl.textContent = 'Debes seleccionar un archivo XML antes de analizar.';
            errorEl.hidden = false;
        }
        return;
    }
    if (errorEl) errorEl.hidden = true;
    const btn = document.getElementById('xml-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando…';
});
</script>
@endpush