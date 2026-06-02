@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Características por producto')

@push('styles')
    @vite([
        'resources/css/admin/shell-base.css',
        'resources/css/admin/suppliers/suppliers.css',
    ])
@endpush

@push('vite-body')
    @vite([
        'resources/js/admin/product-classifications/index.js',
    ])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')

    <div class="admin-content-wrapper">

        @component('admin.partials.page-header', [
            'title' => 'Características por producto',
        ])
            <p>
                Consulta y administra los valores asignados a cada producto según sus atributos,
                como color, talla o material. Solo se muestran productos asociados
                a un tipo concreto dentro del catálogo.
            </p>

            @slot('actions')
                <a href="{{ route('inventory') }}" class="btn btn-secondary">
                    <i class="fas fa-box"></i> Inventario
                </a>
            @endslot
        @endcomponent

        @if (session('status'))
            <x-admin.admin-alert
                type="success"
                :message="session('status')"
                dismissible />
        @endif

        @if (session('error'))
            <x-admin.admin-alert
                type="error"
                :message="session('error')" />
        @endif

        <div
            class="form-card"
            @if (! $products->isEmpty())
                style="padding:0;overflow:hidden;"
            @endif>

            <div class="form-body">

                @if ($products->isEmpty())

                    <x-admin.admin-alert
                        type="info"
                        title="No hay registros disponibles para mostrar."
                        dismissible>

                        <div>

                            <p style="margin:0;">

                                <strong>
                                    Todo está bien:
                                </strong>

                                solo faltan productos correctamente ubicados
                                en el catálogo.

                                En <strong>Inventario</strong>, al crear o editar
                                un producto, completa la categoría padre
                                y el tipo concreto
                                (<em>ej. Bicicletas → MTB</em>).

                            </p>

                            <p style="margin-top:.75rem;">

                                Si el producto queda solo en la categoría padre,
                                no entra en esta lista.

                            </p>

                        </div>

                    </x-admin.admin-alert>

                @else

                    <div
                        data-cf4-ajax-pagination
                        data-cf4-ajax-scroll>

                        <div id="cf4-list-fragment">

                            <div class="admin-table-scroll">

                                <table class="admin-table">

                                    <thead>

                                        <tr>
                                            <th>Producto</th>
                                            <th>Categoría → Subcategoría</th>
                                            <th>Atributo → valor</th>
                                            <th class="admin-table__col--actions">
                                                Acciones
                                            </th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        @foreach ($products as $product)

                                            <tr>

                                                <td>
                                                    {{ $product->name }}
                                                </td>

                                                <td>

                                                    @if ($product->category)

                                                        {{ optional($product->category->parent)->name ?? '—' }}

                                                        <span class="text-muted">
                                                            →
                                                        </span>

                                                        {{ $product->category->name }}

                                                    @else

                                                        —

                                                    @endif

                                                </td>

                                                <td>

                                                    @forelse ($product->classificationValues as $cv)

                                                        <span
                                                            style="display:inline-block;margin-right:.5rem;">

                                                            <strong>
                                                                {{ $cv->dimension->label ?? '—' }}:
                                                            </strong>

                                                            {{ $cv->value }}

                                                        </span>

                                                    @empty

                                                        <em>
                                                            Sin asignar
                                                        </em>

                                                    @endforelse

                                                </td>

                                                <td class="admin-table__col--actions">

                                                    <div class="actions-container">
                                                        <a
                                                            href="{{ route('admin.products.classifications.edit', $product) }}"
                                                            class="action-btn edit"
                                                            title="Editar características">
                                                            <i class="fas fa-sliders-h"></i>
                                                        </a>
                                                    </div>

                                                </td>

                                            </tr>

                                        @endforeach

                                    </tbody>

                                </table>

                                <div class="pagination-wrapper">

                                    <x-admin.pagination
                                        :paginator="$products"
                                        label="productos" />

                                </div>

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

@endsection
