<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos - {{ $fecha_exportacion }}</title>
    @vite(['resources/css/admin/products/products-pdf.css'])
</head>
<body>

    {{-- Report header --}}
    <div class="header">
        <h1>Reporte de Inventario de Productos</h1>
        <p class="subtitle">Sistema de Gestión de Inventario - Ciclo Finca 4</p>
    </div>

    {{-- Export metadata --}}
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha de Exportación:</span>
            <span class="info-value">{{ $fecha_exportacion }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total de Productos:</span>
            <span class="info-value">{{ $total }} productos</span>
        </div>
        <div class="info-row">
            <span class="info-label">Generado por:</span>
            <span class="info-value">Sistema de Inventario</span>
        </div>
    </div>

    {{-- Products table --}}
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 25%;">Producto</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Proveedor</th>
                <th style="width: 8%;">Stock</th>
                <th style="width: 8%;">Mínimo</th>
                <th style="width: 10%;">Precio Compra</th>
                <th style="width: 10%;">Precio Venta</th>
                <th style="width: 8%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td class="text-center">{{ $product->id }}</td>

                {{-- Product name with optional truncated description --}}
                <td>
                    <strong>{{ $product->name }}</strong>
                    @if($product->description && $product->description !== 'No description')
                        <br><small style="color: #666;">{{ Str::limit($product->description, 50) }}</small>
                    @endif
                </td>

                <td>{{ $product->category }}</td>
                <td>{{ $product->supplier }}</td>

                {{-- Stock level: umbral = stock mínimo por producto (CF4-50) --}}
                <td class="text-center">
                    @php $tier = \App\Models\Product::adminStockExportTier((int) $product->stock_current, (int) $product->stock_minimum); @endphp
                    @if($tier === 'high')
                        <span class="stock-high">{{ $product->stock_current }}</span>
                    @elseif($tier === 'medium')
                        <span class="stock-medium">{{ $product->stock_current }}</span>
                    @else
                        <span class="stock-low">{{ $product->stock_current }}</span>
                    @endif
                </td>

                <td class="text-center">{{ $product->stock_minimum }}</td>
                <td class="price">₡{{ $product->purchase_price }}</td>
                <td class="price">₡{{ $product->sale_price }}</td>

                {{-- Product status badge --}}
                <td class="text-center">
                    @if($product->status === 'Active')
                        <span class="status-active">{{ $product->status }}</span>
                    @elseif($product->status === 'Inactive')
                        <span class="status-inactive">{{ $product->status }}</span>
                    @else
                        <span class="status-discontinued">{{ $product->status }}</span>
                    @endif
                </td>
            </tr>
            @empty
            {{-- Fallback row when no products are available --}}
            <tr>
                <td colspan="9" class="text-center">No hay productos para mostrar</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Report footer --}}
    <div class="footer">
        <p class="mb-0">Este reporte fue generado automáticamente el {{ $fecha_exportacion }}</p>
        <p class="mb-0">Sistema de Gestión de Inventario - Ciclo Finca 4</p>
    </div>

</body>
</html>