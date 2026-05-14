@extends('client.layouts.app')

@section('title', 'Página no encontrada')

@push('styles')
<style>
    /* Fill viewport below header/footer when this page is shown */
    body.cliente-layout:has(#cf4-error-404) {
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
    }
    body.cliente-layout:has(#cf4-error-404) .cliente-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    #cf4-error-404 {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: min(88dvh, calc(100dvh - 160px));
        padding: 8px 12px 20px;
        box-sizing: border-box;
    }

    .cf4-error-card {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        border-radius: 16px;
        overflow: hidden;
        background: linear-gradient(165deg, #e8f5e9 0%, #c8e6c9 100%);
        box-shadow: 0 14px 48px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .cf4-error-media {
        flex: 1 1 auto;
        position: relative;
        min-height: 280px;
        background: linear-gradient(168deg, #f1f8f4 0%, #dcedc8 55%, #aed581 100%);
    }

    .cf4-error-video,
    .cf4-error-fallback-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .cf4-error-fallback-img {
        display: none;
    }

    /* Same copy as in the MP4, for reduced-motion / broken video */
    .cf4-error-overlay-copy {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 4;
        padding: 20px 16px 28px;
        background: linear-gradient(to top, rgba(241, 248, 244, 0.97) 0%, rgba(241, 248, 244, 0.55) 55%, transparent 100%);
        pointer-events: none;
    }
    .cf4-error-overlay-copy h1 {
        margin: 0;
        font-size: clamp(2.5rem, 10vw, 4.5rem);
        font-weight: 800;
        color: #1b5e20;
        letter-spacing: -0.03em;
        line-height: 1;
        text-shadow: 0 2px 14px rgba(255, 255, 255, 0.85);
    }
    .cf4-error-overlay-copy p {
        margin: 10px 0 0;
        font-size: clamp(1rem, 3.5vw, 1.35rem);
        font-weight: 500;
        color: #2e7d32;
        line-height: 1.45;
        max-width: 32em;
        margin-left: auto;
        margin-right: auto;
        text-shadow: 0 1px 10px rgba(255, 255, 255, 0.9);
    }

    .cf4-error-actions {
        flex: 0 0 auto;
        padding: 16px 16px 20px;
    }

    .cf4-error-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 28px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 1rem;
        font-family: inherit;
        cursor: pointer;
        border: none;
        background: #2e7d32;
        color: #fff;
        box-shadow: 0 8px 24px rgba(46, 125, 50, 0.35);
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .cf4-error-cta:hover {
        background: #1b5e20;
        transform: translateY(-1px);
    }
    .cf4-error-cta:focus-visible {
        outline: 3px solid #a5d6a7;
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .cf4-error-video {
            display: none !important;
        }
        .cf4-error-fallback-img {
            display: block !important;
        }
        .cf4-error-overlay-copy {
            display: block;
        }
        .cf4-error-cta {
            transition: none;
        }
        .cf4-error-cta:hover {
            transform: none;
        }
    }

    .cf4-error-page.is-video-broken .cf4-error-video {
        display: none !important;
    }
    .cf4-error-page.is-video-broken .cf4-error-fallback-img {
        display: block !important;
    }
    .cf4-error-page.is-video-broken .cf4-error-overlay-copy {
        display: block;
    }
</style>
@endpush

@section('content')
<section
    class="cf4-error-page cf4-error-page--404"
    id="cf4-error-404"
    aria-label="Error 404. Parece que esta ruta se salió del camino."
>
    <div class="cf4-error-card">
        <div class="cf4-error-media">
            <video
                id="cf4-404-video"
                class="cf4-error-video"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                poster="{{ asset('images/errors/404-bike.webp') }}"
                aria-hidden="true"
            >
                <source src="{{ asset('videos/errors/404-bike.mp4') }}" type="video/mp4" />
            </video>
            <img
                id="cf4-404-fallback"
                class="cf4-error-fallback-img"
                src="{{ asset('images/errors/404-bike.webp') }}"
                width="1920"
                height="1080"
                alt=""
                loading="lazy"
                role="presentation"
            />
            <div class="cf4-error-overlay-copy" aria-hidden="true">
                <h1>404</h1>
                <p>Parece que esta ruta se salió del camino.</p>
            </div>
        </div>
        <div class="cf4-error-actions">
            <button
                type="button"
                class="cf4-error-cta"
                id="cf4-404-back"
                data-fallback-url="{{ route('clients.catalog') }}"
            >
                Volver atrás
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('cf4-error-404');
    var video = document.getElementById('cf4-404-video');
    var btn = document.getElementById('cf4-404-back');
    if (!root) return;

    function showFallback() {
        root.classList.add('is-video-broken');
    }
    if (video) {
        video.addEventListener('error', showFallback);
        if (typeof video.readyState === 'number' && video.networkState === 3) {
            showFallback();
        }
    }

    if (btn) {
        btn.addEventListener('click', function () {
            var fallback = btn.getAttribute('data-fallback-url') || '/';
            var ref = document.referrer || '';
            var sameOrigin = false;
            try {
                sameOrigin = !!ref && new URL(ref).origin === window.location.origin;
            } catch (e) {}
            if (sameOrigin || window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.assign(fallback);
        });
    }
})();
</script>
@endpush
