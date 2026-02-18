<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'paginator',
    // Texto de contexto opcional: ej. "inventario"
    'label' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'paginator',
    // Texto de contexto opcional: ej. "inventario"
    'label' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    // Cálculos seguros para "Mostrando X–Y de Z"
    $total      = $paginator->total();
    $perPage    = $paginator->perPage();
    $current    = max(1, $paginator->currentPage());
    $from       = $total ? (($current - 1) * $perPage) + 1 : 0;
    $to         = $total ? min($total, $current * $perPage) : 0;

    // Siempre mostramos el bloque, aunque sea 1 página
    $hasPrev = $current > 1;
    $hasNext = $current < max(1, $paginator->lastPage());
?>

<div class="pagination" role="navigation" aria-label="Paginación <?php echo e($label ?? ''); ?>">
    
    <div class="results-info" aria-live="polite">
        Mostrando <?php echo e($from); ?> a <?php echo e($to); ?> de <?php echo e($total); ?> resultados
    </div>

    
    <a
        class="button"
        aria-label="Previous"
        href="<?php echo e($hasPrev ? $paginator->previousPageUrl() : '#'); ?>"
        <?php if(!$hasPrev): ?> aria-disabled="true" tabindex="-1" <?php endif; ?>
        data-page="<?php echo e($current - 1); ?>"
    >
        
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

    
    <span class="button button-primary" aria-current="page">
        <?php echo e($current); ?> / <?php echo e(max(1, $paginator->lastPage())); ?>

    </span>

    
    <a
        class="button"
        aria-label="Next"
        href="<?php echo e($hasNext ? $paginator->nextPageUrl() : '#'); ?>"
        <?php if(!$hasNext): ?> aria-disabled="true" tabindex="-1" <?php endif; ?>
        data-page="<?php echo e($current + 1); ?>"
    >
        
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>

    
    <label class="sr-only" for="goToPageInput">Ir a página</label>
    <input id="goToPageInput" type="number" min="1" step="1" value="<?php echo e($current); ?>" inputmode="numeric" />
    <button class="go-button" id="goToPageBtn" type="button">Ir</button>
</div>
<?php /**PATH /var/www/html/resources/views/components/pagination.blade.php ENDPATH**/ ?>