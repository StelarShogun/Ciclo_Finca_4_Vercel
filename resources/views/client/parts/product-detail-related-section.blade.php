<div class="product-detail-related-scroll">
    <div class="products-grid products-grid--related">
        @foreach($relatedProducts as $related)
            @php
                $relLabel = $related->clientCatalogStockLabel();
                $relCanBuy = $related->isPurchasableByClient();
                $relSku = $related->clientCatalogAssignedSku();
                $relIsFavorite = $favoriteProductIds->contains((int) $related->product_id);
                $relBrand = $related->brands->first();
            @endphp
            <article class="product-card product-card--related @if($relLabel === 'Agotado') product-card--out-of-stock @endif">
                <div class="product-image product-image--related">
                    @auth('clients')
                        <button type="button"
                                class="product-favorite-btn {{ $relIsFavorite ? 'is-active' : '' }}"
                                data-product-favorite-btn
                                data-product-id="{{ $related->product_id }}"
                                aria-pressed="{{ $relIsFavorite ? 'true' : 'false' }}"
                                aria-label="{{ $relIsFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' }}">
                            <i class="{{ $relIsFavorite ? 'fas' : 'far' }} fa-heart" aria-hidden="true"></i>
                        </button>
                    @endauth
                    <a class="product-image__link" href="{{ $related->clientProductUrl() }}"
                       aria-label="Ver producto: {{ $related->name }}">
                        @include('shared.media.product-media', [
                            'product' => $related,
                            'variant' => 'card',
                            'href' => null,
                        ])
                    </a>
                </div>
                <div class="product-info">
                    <div class="product-card-meta-badges">
                        <span class="product-category">{{ $related->category->name ?? 'Sin categoría' }}</span>
                        @if($relBrand)
                            <span class="product-card-brand-badge">{{ $relBrand->name }}</span>
                        @endif
                    </div>
                    <h3 class="product-name">
                        <a href="{{ $related->clientProductUrl() }}">{{ $related->name }}</a>
                    </h3>
                    @php $relRs = $productReviewStats[(int) $related->product_id] ?? null; @endphp
                    @include('client.parts.product-stars-inline', [
                        'avgStars' => (float) data_get($relRs, 'avg', 0),
                        'reviewCount' => (int) data_get($relRs, 'count', 0),
                        'variant' => 'related',
                    ])
                    @if($relSku)
                        <p class="product-card-sku">SKU: {{ $relSku }}</p>
                    @endif
                    <p @class([
                        'product-availability-text',
                        'product-stock-badge',
                        'is-available' => $relLabel === 'En stock',
                        'is-low' => $relLabel === 'Últimas unidades',
                        'is-out' => $relLabel === 'Agotado',
                        'is-na' => $relLabel === 'No disponible',
                    ])>{{ $relLabel }}</p>
                    @if($relCanBuy)
                        <p class="product-stock-qty">{{ number_format((int) ($related->stock_current ?? 0), 0, ',', '.') }} unidades disponibles</p>
                    @endif
                    <div class="product-footer">
                        <div class="product-price">₡{{ number_format($related->sale_price, 0, ',', '.') }}</div>
                        <div class="product-actions">
                            <a href="{{ $related->clientProductUrl() }}" class="btn-product btn-ver-detalles">
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                Ver detalles
                            </a>
                            @if($relCanBuy)
                                @auth('clients')
                                    <button type="button" class="btn-product btn-agregar add-to-cart-btn"
                                            data-purchasable="1"
                                            data-product-id="{{ $related->product_id }}"
                                            data-product-name="{{ $related->name }}"
                                            data-product-price="{{ $related->sale_price }}"
                                            data-product-stock="{{ $related->stock_current }}">
                                        <i class="fas fa-cart-plus" aria-hidden="true"></i>
                                        Agregar
                                    </button>
                                @else
                                    <button type="button" class="btn-product btn-agregar guest-add-btn"
                                            data-purchasable="1"
                                            data-product-stock="{{ $related->stock_current }}">
                                        <i class="fas fa-cart-plus" aria-hidden="true"></i>
                                        Agregar
                                    </button>
                                @endauth
                            @else
                                <button type="button" class="btn-product btn-agotado" disabled>
                                    <i class="fas fa-ban" aria-hidden="true"></i>
                                    {{ $relLabel === 'Agotado' ? 'Agotado' : 'No disponible' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
