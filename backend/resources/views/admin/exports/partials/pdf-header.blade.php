@php
    $pdfTitle = $pdfTitle ?? 'Reporte';
    $pdfSubtitle = $pdfSubtitle ?? 'Sistema Ciclo Finca 4';
    $canEmbedLogoPng = extension_loaded('gd') && ! empty($logoPath) && is_string($logoPath) && is_file($logoPath);
@endphp
<div class="pdf-brand-row">
    <div class="logo">
        @if($canEmbedLogoPng)
            <img src="{{ $logoPath }}" alt="Logo">
        @else
            <span style="font-size:11px;font-weight:bold;color:#2d6a4f;">CF4</span>
        @endif
    </div>
    <div class="titles">
        <h1>{{ $pdfTitle }}</h1>
        <p class="subtitle">{{ $pdfSubtitle }}</p>
    </div>
</div>
<p class="pdf-meta">
    Generado el {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
    @if(!empty($generatedFor))
        · {{ $generatedFor }}
    @endif
</p>
