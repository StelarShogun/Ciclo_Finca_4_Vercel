@extends('layouts.error')

@section('title', 'Página no encontrada — Ciclo Finca 4')

@push('styles')
<style>
    /* ================================================================
       CF4 · 404 PAGE
       Two-column: copy (left) + video decoration (right)
       ================================================================ */

    .cf4-404-card {
        width: min(1100px, 100%);
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 44px;
        align-items: center;
        padding: 56px 56px;
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.68);
        border: 1px solid rgba(255, 255, 255, 0.75);
        box-shadow:
            0 2px 0 rgba(255, 255, 255, 0.9) inset,
            0 32px 80px rgba(11, 43, 38, 0.16),
            0 8px 24px rgba(11, 43, 38, 0.08);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    /* ── copy column ──────────────────────────────────────────────── */
    .cf4-404-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        color: var(--brand-medium);
        background: rgba(142, 182, 155, 0.18);
        border: 1px solid rgba(142, 182, 155, 0.45);
        border-radius: 999px;
        padding: 5px 14px 5px 10px;
        margin-bottom: 20px;
    }

    .cf4-404-eyebrow-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--brand-light);
    }

    .cf4-404-headline {
        font-size: clamp(96px, 14vw, 172px);
        font-weight: 800;
        line-height: 0.88;
        margin: 0 0 20px;
        /* subtle gradient on the number */
        background: linear-gradient(150deg, var(--brand-darkest) 0%, var(--brand-medium) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -6px;
    }

    .cf4-404-title {
        font-size: clamp(1.25rem, 2.8vw, 1.75rem);
        font-weight: 700;
        color: var(--brand-medium-dark);
        margin: 0 0 14px;
        line-height: 1.25;
    }

    .cf4-404-body {
        font-size: 1rem;
        line-height: 1.7;
        color: #3a5244;
        margin: 0 0 36px;
        max-width: 420px;
    }

    /* ── action buttons ─────────────────────────────────────────────  */
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
        min-height: 50px;
        padding: 0 28px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border: none;
        background: linear-gradient(135deg, var(--brand-medium) 0%, var(--brand-medium-dark) 100%);
        color: #fff;
        box-shadow: 0 6px 20px rgba(35, 83, 71, 0.38), 0 2px 0 rgba(255,255,255,0.12) inset;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .cf4-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(35, 83, 71, 0.45), 0 2px 0 rgba(255,255,255,0.12) inset;
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
        min-height: 50px;
        padding: 0 24px;
        border-radius: 999px;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: 1.5px solid rgba(142, 182, 155, 0.55);
        background: rgba(142, 182, 155, 0.1);
        color: var(--brand-medium);
        transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }

    .cf4-btn-secondary:hover {
        background: rgba(142, 182, 155, 0.22);
        border-color: var(--brand-medium);
        transform: translateY(-1px);
    }

    .cf4-btn-secondary:focus-visible {
        outline: 3px solid var(--brand-light);
        outline-offset: 3px;
    }

    /* ── video column ───────────────────────────────────────────────  */
    .cf4-404-visual-wrap {
        position: relative;
        /* green glow halo behind the video */
        filter: drop-shadow(0 0 32px rgba(56, 142, 60, 0.28));
    }

    .cf4-404-visual-wrap::before {
        content: '';
        position: absolute;
        inset: -16px;
        border-radius: 36px;
        background: radial-gradient(ellipse at 50% 60%, rgba(129, 199, 132, 0.35) 0%, transparent 70%);
        z-index: 0;
        pointer-events: none;
    }

    .cf4-404-visual {
        position: relative;
        z-index: 1;
        border-radius: 24px;
        overflow: hidden;
        aspect-ratio: 16 / 9;
        background: linear-gradient(168deg, #f0f7f2 0%, #d4edda 55%, #a8d5b5 100%);
        box-shadow:
            0 20px 56px rgba(11, 43, 38, 0.22),
            0 4px 12px rgba(11, 43, 38, 0.1);
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

    /* ── accessibility ──────────────────────────────────────────────  */
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

    /* ── responsive ─────────────────────────────────────────────────  */
    @media (max-width: 860px) {
        .cf4-404-card {
            grid-template-columns: 1fr;
            padding: 36px 28px 40px;
            gap: 32px;
            text-align: center;
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
        /* video goes below on mobile */
        .cf4-404-visual-wrap {
            order: 2;
        }
        .cf4-404-copy {
            order: 1;
        }
    }

    @media (max-width: 480px) {
        .cf4-404-card {
            padding: 28px 20px 32px;
        }
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

    {{-- ── copy column ─────────────────────────────────────── --}}
    <div class="cf4-404-copy">

        <span class="cf4-404-eyebrow">
            <span class="cf4-404-eyebrow-dot" aria-hidden="true"></span>
            Página no encontrada
        </span>

        <h1 class="cf4-404-headline">404</h1>
        <h2 class="cf4-404-title">Esta ruta se salió del camino</h2>
        <p class="cf4-404-body">
            No encontramos la página que buscabas. Puedes volver al catálogo
            y seguir explorando bicicletas, repuestos y accesorios disponibles.
        </p>

        <div class="cf4-404-actions">
            <a href="{{ route('clients.catalog') }}" class="cf4-btn-primary">
                <i class="fas fa-bicycle" aria-hidden="true"></i>
                Explorar catálogo
            </a>

            <a href="{{ route('clients.home') }}" class="cf4-btn-secondary">
                <i class="fas fa-home" aria-hidden="true"></i>
                Ir al inicio
            </a>
        </div>

    </div>

    {{-- ── video column (decorative) ───────────────────────── --}}
    <div class="cf4-404-visual-wrap">
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

</div>
@endsection

@push('scripts')
<script>
(function () {
    var card  = document.getElementById('cf4-404-card');
    var video = document.getElementById('cf4-404-video');
    if (video && card) {
        video.addEventListener('error', function () { card.classList.add('is-video-broken'); });
        if (video.networkState === 3) { card.classList.add('is-video-broken'); }
    }
})();
</script>
@endpush
