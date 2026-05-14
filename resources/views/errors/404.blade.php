@extends('layouts.error')

@section('title', 'Página no encontrada — Ciclo Finca 4')

@push('styles')
<style>
    /* ================================================================
       404 PAGE — two-column layout (copy + video)
       ================================================================ */

    .cf4-404-card {
        width: min(1100px, 100%);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: center;
        padding: 52px 52px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.72);
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.13);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    /* ---- copy column ---- */
    .cf4-404-eyebrow {
        display: inline-block;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--brand-medium);
        background: rgba(142, 182, 155, 0.22);
        border: 1px solid rgba(142, 182, 155, 0.4);
        border-radius: 999px;
        padding: 4px 14px;
        margin-bottom: 18px;
    }

    .cf4-404-headline {
        font-size: clamp(88px, 13vw, 160px);
        font-weight: 800;
        line-height: 0.9;
        margin: 0 0 18px;
        color: var(--brand-darkest);
        letter-spacing: -6px;
    }

    .cf4-404-title {
        font-size: clamp(1.3rem, 3vw, 1.9rem);
        font-weight: 700;
        color: var(--brand-medium-dark);
        margin: 0 0 12px;
        line-height: 1.2;
    }

    .cf4-404-body {
        font-size: 1rem;
        line-height: 1.65;
        color: #34524a;
        margin: 0 0 32px;
        max-width: 440px;
    }

    .cf4-404-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .cf4-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 48px;
        padding: 0 26px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border: none;
        background: var(--brand-medium);
        color: #fff;
        box-shadow: 0 8px 24px rgba(35, 83, 71, 0.35);
        transition: background 0.2s ease, transform 0.18s ease;
    }

    .cf4-btn-primary:hover {
        background: var(--brand-medium-dark);
        transform: translateY(-2px);
    }

    .cf4-btn-primary:focus-visible {
        outline: 3px solid var(--brand-light);
        outline-offset: 3px;
    }

    .cf4-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 48px;
        padding: 0 24px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: 1.5px solid var(--brand-light);
        background: rgba(142, 182, 155, 0.14);
        color: var(--brand-medium);
        transition: background 0.2s ease, border-color 0.2s ease, transform 0.18s ease;
    }

    .cf4-btn-secondary:hover {
        background: rgba(142, 182, 155, 0.28);
        border-color: var(--brand-medium);
        transform: translateY(-1px);
    }

    .cf4-btn-secondary:focus-visible {
        outline: 3px solid var(--brand-light);
        outline-offset: 3px;
    }

    /* ---- visual column ---- */
    .cf4-404-visual {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(35, 83, 71, 0.2);
        background: linear-gradient(168deg, #f1f8f4 0%, #dcedc8 55%, #aed581 100%);
        aspect-ratio: 16 / 9;
    }

    .cf4-404-video,
    .cf4-404-fallback {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .cf4-404-fallback {
        display: none;
    }

    /* ---- reduced motion / broken video ---- */
    @media (prefers-reduced-motion: reduce) {
        .cf4-404-video {
            display: none !important;
        }
        .cf4-404-fallback {
            display: block !important;
        }
        .cf4-btn-primary,
        .cf4-btn-secondary {
            transition: none;
        }
        .cf4-btn-primary:hover,
        .cf4-btn-secondary:hover {
            transform: none;
        }
    }

    .cf4-404-card.is-video-broken .cf4-404-video {
        display: none !important;
    }
    .cf4-404-card.is-video-broken .cf4-404-fallback {
        display: block !important;
    }

    /* ---- responsive (≤ 800px) ---- */
    @media (max-width: 800px) {
        .cf4-404-card {
            grid-template-columns: 1fr;
            padding: 32px 24px 36px;
            text-align: center;
            gap: 28px;
        }

        .cf4-404-body {
            margin-left: auto;
            margin-right: auto;
        }

        .cf4-404-actions {
            justify-content: center;
        }

        .cf4-404-headline {
            letter-spacing: -4px;
        }
    }

    @media (max-width: 480px) {
        .cf4-404-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .cf4-btn-primary,
        .cf4-btn-secondary {
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="cf4-404-card" id="cf4-404-card">

    {{-- Copy column --}}
    <div class="cf4-404-copy">
        <span class="cf4-404-eyebrow">Página no encontrada</span>

        <h1 class="cf4-404-headline">404</h1>
        <h2 class="cf4-404-title">Esta ruta se salió del camino</h2>
        <p class="cf4-404-body">
            No encontramos la página que buscabas. Puede que el enlace haya cambiado
            o que la dirección esté escrita incorrectamente.
        </p>

        <div class="cf4-404-actions">
            <button
                type="button"
                class="cf4-btn-primary"
                id="cf4-404-back"
                data-fallback-url="{{ route('clients.catalog') }}"
            >
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Volver atrás
            </button>

            <a href="{{ route('clients.home') }}" class="cf4-btn-secondary">
                <i class="fas fa-home" aria-hidden="true"></i>
                Ir al inicio
            </a>
        </div>
    </div>

    {{-- Visual column — video decorativo --}}
    <div class="cf4-404-visual">
        <video
            id="cf4-404-video"
            class="cf4-404-video"
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
            class="cf4-404-fallback"
            src="{{ asset('images/errors/404-bike.webp') }}"
            width="1920"
            height="1080"
            alt=""
            role="presentation"
            loading="lazy"
        />
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var card  = document.getElementById('cf4-404-card');
    var video = document.getElementById('cf4-404-video');
    var btn   = document.getElementById('cf4-404-back');

    if (video && card) {
        video.addEventListener('error', function () {
            card.classList.add('is-video-broken');
        });
        if (video.networkState === 3) {
            card.classList.add('is-video-broken');
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
