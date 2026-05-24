@if(filled($product->description))
    <article class="product-detail-card product-detail-description-card">
        <h2 class="product-detail-card__title">Descripción del producto</h2>
        <div class="product-detail-description__body">
            {!! nl2br(e($product->description)) !!}
        </div>
    </article>
@else
    <article class="product-detail-card product-detail-description-card product-detail-description-card--empty">
        <h2 class="product-detail-card__title">Descripción del producto</h2>
        <p class="product-detail-description__empty">
            Este producto aún no tiene una descripción detallada. Consultá con nuestro equipo o revisá las especificaciones técnicas.
        </p>
    </article>
@endif
