<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Crear Subcategoría - Ciclo Finca 4 Admin</title>

    @vite(['resources/css/admin/components/page-header.css', 'resources/css/admin/suppliers/suppliers.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-layout">
    @include('admin.parts.aside')

    <main class="admin-main">
        <div class="form-container">
            <nav class="admin-breadcrumb" aria-label="Migas de pan">
                <a href="{{ route('inventory') }}">Inventario</a>
                <span class="sep">/</span>
                <span>Crear subcategoría</span>
            </nav>

            @component('admin.partials.page-header', ['title' => 'Crear subcategoría'])
                <p>
                    Registra una subcategoría dentro de una categoría principal para clasificar los productos
                    con mayor precisión.
                </p>
            @endcomponent

            <div class="form-card">
                @if (session('status'))
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('categories.subcategories.store') }}" method="POST" class="form-body">
                    @csrf

                    <div class="form-group">
                        <label for="name">Nombre de la subcategoría *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            placeholder="e.g., MT (Mountain)">
                        <div class="error-message">{{ $errors->first('name') }}</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="3"
                            placeholder="Opcional. {{ old('description') ? '' : 'Describe brevemente la subcategoría.' }}">{{ old('description') }}</textarea>
                        <div class="error-message">{{ $errors->first('description') }}</div>
                    </div>

                    <div class="form-group">
                        <label for="parent_category_id">Categoría padre *</label>
                        <select id="parent_category_id" name="parent_category_id" required>
                            <option value="">Selecciona una categoría padre</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}" @selected(old('parent_category_id') == $category->category_id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="error-message">{{ $errors->first('parent_category_id') }}</div>
                        <small class="form-text text-muted">
                            ¿Falta una categoría padre?
                            <a href="{{ route('categories.parents.create') }}">Crear categoría padre</a>.
                        </small>
                    </div>

                    <div class="form-group optional">
                        <label>Subcategorías actuales del padre seleccionado</label>
                        <div id="parent-subcategories-hint" class="info-section">
                            <p>Selecciona una categoría padre para ver sus subcategorías actuales.</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Guardar subcategoría
                        </button>

                        <a href="{{ route('inventory') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>

            <div class="form-card" style="margin-top: 18px;">
                <div class="table-header"
                    style="padding: 0 0 12px 0; border-bottom: 1px solid var(--border-color); margin-bottom: 12px;">
                    <h3 style="margin: 0;"><i class="fas fa-sitemap"></i> Jerarquía de categorías</h3>
                </div>

                <div class="sales-table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Categoría padre</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categoriesHierarchy as $row)
                                <tr>
                                    <td>
                                        @if (is_null($row->parent_category_id))
                                            <strong>{{ $row->name }}</strong>
                                        @else
                                            — {{ $row->name }}
                                        @endif
                                    </td>
                                    <td>{{ $row->parent->name ?? '—' }}</td>
                                    <td>{{ is_null($row->parent_category_id) ? 'Padre' : 'Subcategoría' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay categorías registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($categoriesHierarchy->total() > 0)
                    <div class="category-hierarchy-pagination" style="margin-top: 12px;">
                        <x-pagination :paginator="$categoriesHierarchy" label="categorías" />
                    </div>
                @endif
            </div>
        </div>
    </main>

    <script>
        (function() {
            const parentSelect = document.getElementById('parent_category_id');
            const hintBox = document.getElementById('parent-subcategories-hint');
            const tree = @json($subcategoriesByParent);

            function renderSubcategories() {
                const parentId = parentSelect ? parentSelect.value : '';

                if (!hintBox) {
                    return;
                }

                if (!parentId) {
                    hintBox.innerHTML = '<p>Selecciona una categoría padre para ver sus subcategorías actuales.</p>';
                    return;
                }

                const key = String(parentId);
                const num = Number(parentId);
                let subs = tree[key] || tree[parentId] || (Number.isFinite(num) ? tree[num] : []) || [];

                if (!subs.length) {
                    for (const k of Object.keys(tree)) {
                        if (String(k) === key || Number(k) === num) {
                            subs = tree[k] || [];
                            break;
                        }
                    }
                }

                if (!subs.length) {
                    hintBox.innerHTML = '<p>No hay subcategorías registradas para esta categoría padre.</p>';
                    return;
                }

                const items = subs.map((sub) => `<li>${sub.name}</li>`).join('');
                hintBox.innerHTML = `<p>Subcategorías existentes:</p><ul>${items}</ul>`;
            }

            if (parentSelect) {
                parentSelect.addEventListener('change', renderSubcategories);
                renderSubcategories();
            }

            const pagination = document.querySelector('.category-hierarchy-pagination .pagination');
            if (pagination) {
                const goInput = pagination.querySelector('#goToPageInput');
                const goBtn = pagination.querySelector('#goToPageBtn');
                const pageParam = 'hierarchy_page';

                pagination.querySelectorAll('.button[aria-label]').forEach((link) => {
                    if (link.getAttribute('aria-disabled') === 'true') {
                        link.addEventListener('click', (e) => e.preventDefault());
                    }
                });

                function goToPage() {
                    const totalSpan = pagination.querySelector('.button.button-primary');
                    if (!totalSpan || !goInput) return;

                    const parts = totalSpan.textContent.trim().split('/');
                    const lastPage = Math.max(1, parseInt((parts[1] || '1').trim(), 10));
                    let target = parseInt(String(goInput.value || '1').trim(), 10);
                    if (Number.isNaN(target)) target = 1;
                    target = Math.min(Math.max(1, target), lastPage);

                    const url = new URL(window.location.href);
                    url.searchParams.set(pageParam, String(target));
                    window.location.assign(url.toString());
                }

                goBtn?.addEventListener('click', goToPage);
                goInput?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        goToPage();
                    }
                });
            }
        })();
    </script>
</body>

</html>
