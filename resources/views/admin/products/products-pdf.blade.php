@extends('admin.exports.layouts.pdf-master')

@section('pdf_body')
    <div class="section">
        <h2>Resumen</h2>
        <p class="pdf-meta" style="margin:0 0 8px;">
            Fecha de exportación: {{ $fecha_exportacion }}.
            Productos en este PDF: <strong>{{ $total }}</strong>
            @if(isset($totalMatching) && (int) $totalMatching !== (int) $total)
                (coincidencias con filtros: {{ $totalMatching }})
            @endif
        </p>
    </div>

    <div class="section">
        <h2>Detalle</h2>
        @if($products->count() === 0)
            <div class="empty-state">No hay productos que coincidan con los filtros seleccionados.</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Proveedor</th>
                        <th class="num">Stock</th>
                        <th class="num">Mín.</th>
                        <th class="num">P. compra</th>
                        <th class="num">P. venta</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                @if($product->description && $product->description !== 'No description')
                                    <br><small style="color:#666;">{{ \Illuminate\Support\Str::limit($product->description, 48) }}</small>
                                @endif
                            </td>
                            <td>{{ $product->category }}</td>
                            <td>{{ $product->supplier }}</td>
                            <td class="num">{{ $product->stock_current }}</td>
                            <td class="num">{{ $product->stock_minimum }}</td>
                            <td class="num">₡{{ $product->purchase_price }}</td>
                            <td class="num">₡{{ $product->sale_price }}</td>
                            <td>{{ $product->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
