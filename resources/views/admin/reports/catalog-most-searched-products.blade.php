@extends('admin.layouts.admin-shell')

@section('Titulo pagina', 'Productos más buscados - Reportes')

@push('styles')
    @vite(['resources/css/admin/reports/reports-hub.css'])
@endpush

@section('aside')
    @include('admin.parts.aside')
@endsection

@section('contenido')
    @php
        $period = $period ?? '30d';
        $periodShort = match ($period) {
            '7d' => '7 días',
            '90d' => '90 días',
            default => '30 días',
        };
        $periodLabelLong = match ($period) {
            '7d' => 'Últimos 7 días',
            '90d' => 'Últimos 90 días',
            default => 'Últimos 30 días',
        };
        $maxHits = max(1, (int) ($maxHits ?? 1));
        $hasRows = isset($rows) && $rows->isNotEmpty();
    @endphp

    <div class="catalog-search-report">
        <nav class="reports-breadcrumb" aria-label="Migas de pan">
            <a href="{{ route('admin.reports.index') }}">Reportes</a>
            <span class="sep">/</span>
            <span>Productos más buscados</span>
        </nav>

        @component('admin.partials.page-header', ['title' => 'Productos más buscados'])
            <p class="csr-page-subtitle">
                Consulta los productos que aparecen con mayor frecuencia en las búsquedas del catálogo público.
            </p>
        @endcomponent

        <div class="csr-period-bar">
            <nav class="csr-period-tabs" role="tablist" aria-label="Periodo del reporte">
                <a href="{{ route('admin.reports.catalog-search-products', ['period' => '7d']) }}"
                    class="csr-period-tab {{ $period === '7d' ? 'is-active' : '' }}" role="tab"
                    aria-selected="{{ $period === '7d' ? 'true' : 'false' }}">7 días</a>
                <a href="{{ route('admin.reports.catalog-search-products', ['period' => '30d']) }}"
                    class="csr-period-tab {{ $period === '30d' ? 'is-active' : '' }}" role="tab"
                    aria-selected="{{ $period === '30d' ? 'true' : 'false' }}">30 días</a>
                <a href="{{ route('admin.reports.catalog-search-products', ['period' => '90d']) }}"
                    class="csr-period-tab {{ $period === '90d' ? 'is-active' : '' }}" role="tab"
                    aria-selected="{{ $period === '90d' ? 'true' : 'false' }}">90 días</a>
            </nav>
        </div>

        <div class="csr-kpi-grid">
            <article class="csr-kpi-card">
                <div class="csr-kpi-card-head">
                    <div>
                        <p class="csr-kpi-label">Apariciones totales</p>
                        <p class="csr-kpi-value">{{ number_format((int) ($totalEvents ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <span class="csr-kpi-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                </div>
            </article>

            <article class="csr-kpi-card">
                <div class="csr-kpi-card-head">
                    <div>
                        <p class="csr-kpi-label">Productos distintos</p>
                        <p class="csr-kpi-value">{{ number_format((int) ($uniqueProducts ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <span class="csr-kpi-icon csr-kpi-icon--muted" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                </div>
            </article>

            <article class="csr-kpi-card">
                <div class="csr-kpi-card-head">
                    <div>
                        <p class="csr-kpi-label">Líder</p>
                        @if ($topProductName)
                            <p class="csr-kpi-leader-name" title="{{ $topProductName }}">{{ $topProductName }}</p>
                            <p class="csr-kpi-sub csr-kpi-sub--pill">
                                <span
                                    class="csr-hit-pill csr-hit-pill--compact">{{ number_format((int) ($topProductHits ?? 0), 0, ',', '.') }}
                                    apariciones</span>
                            </p>
                        @else
                            <p class="csr-kpi-leader-empty">—</p>
                        @endif
                    </div>
                    <span class="csr-kpi-icon csr-kpi-icon--accent" aria-hidden="true"><i class="fas fa-trophy"></i></span>
                </div>
            </article>

            <article class="csr-kpi-card">
                <div class="csr-kpi-card-head">
                    <div>
                        <p class="csr-kpi-label">Periodo</p>
                        <p class="csr-kpi-period-text">{{ $periodShort }}</p>
                        <p class="csr-kpi-period-long">{{ $periodLabelLong }}</p>
                    </div>
                    <span class="csr-kpi-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                </div>
            </article>
        </div>

        <section class="csr-ranking-panel" aria-labelledby="csr-ranking-heading">
            <div class="csr-ranking-panel-head">
                <h2 id="csr-ranking-heading" class="csr-ranking-panel-title">Del más al menos buscado</h2>
                <p class="csr-ranking-panel-meta">{{ $periodLabelLong }}</p>
            </div>

            @if ($hasRows)
                <div class="csr-ranking-list" role="list">
                    @foreach ($rows as $idx => $row)
                        @php
                            $rank = $idx + 1;
                            $hits = (int) $row->hit_count;
                            $pct = round(($hits / $maxHits) * 100);
                            $rankClass = match ($rank) {
                                1 => 'csr-ranking-row--rank1',
                                2 => 'csr-ranking-row--rank2',
                                3 => 'csr-ranking-row--rank3',
                                default => '',
                            };
                            $badgeClass = match ($rank) {
                                1 => 'csr-rank-badge--1',
                                2 => 'csr-rank-badge--2',
                                3 => 'csr-rank-badge--3',
                                default => 'csr-rank-badge--n',
                            };
                        @endphp
                        <div class="csr-ranking-row {{ $rankClass }}" role="listitem">
                            <span class="csr-rank-badge {{ $badgeClass }}"
                                aria-label="Puesto {{ $rank }}">{{ $rank }}</span>
                            <div class="csr-ranking-main">
                                <p class="csr-ranking-name">{{ $row->name }}</p>
                                <p class="csr-ranking-sku">{{ \App\Models\Product::skuFromId((int) $row->product_id) }}</p>
                                <div class="csr-popularity-track" aria-hidden="true">
                                    <div class="csr-popularity-fill" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                            <div class="csr-ranking-side">
                                <span class="csr-hit-pill">{{ number_format($hits, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="csr-empty">
                    <div class="csr-empty-icon" aria-hidden="true"><i class="fas fa-search"></i></div>
                    <p class="csr-empty-title">Aún no hay datos</p>
                    <p class="csr-empty-text">Cuando los visitantes busquen en el catálogo, aquí aparecerá la lista.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
