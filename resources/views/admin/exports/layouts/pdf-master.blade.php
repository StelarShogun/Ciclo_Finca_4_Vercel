<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $pdfTitle ?? 'Reporte' }} — Ciclo Finca 4</title>
    @include('admin.exports.partials.pdf-styles')
</head>
<body>
    @include('admin.exports.partials.pdf-header', [
        'pdfTitle' => $pdfTitle ?? 'Reporte',
        'pdfSubtitle' => $pdfSubtitle ?? 'Sistema Ciclo Finca 4',
        'logoPath' => $logoPath ?? null,
        'generatedFor' => $generatedFor ?? null,
    ])
    @include('admin.exports.partials.pdf-filters', ['filterLines' => $filterLines ?? []])
    @yield('pdf_body')
    <div class="footer">
        <p>Documento generado automáticamente por Ciclo Finca 4 — Administración</p>
    </div>
</body>
</html>
