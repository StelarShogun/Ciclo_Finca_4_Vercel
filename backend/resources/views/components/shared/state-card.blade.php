@props([
    'variant' => 'fullpage',
    'eyebrow' => null,
    'code' => null,
    'title',
    'message',
    'scene' => null,
    'staticVisual' => false,
    'visualPlain' => false,
    'titleTag' => 'h1',
    'bare' => false,
])

@php
    $isEmbed = $variant === 'embed';
    $allowedTitleTags = ['h1', 'h2', 'h3', 'p'];
    $titleTagResolved = in_array($titleTag, $allowedTitleTags, true) ? $titleTag : 'h1';
    $layoutClasses = 'cf4-state-layout cf4-state-card cf4-transition';
    if ($isEmbed) {
        $layoutClasses .= ' cf4-state-layout--embed';
    }
    if ($staticVisual) {
        $layoutClasses .= ' cf4-state-layout--static-visual';
    }
    if ($bare) {
        $layoutClasses .= ' cf4-state-layout--bare';
    }
    $sceneBoxClass = 'cf4-bike-scene cf4-state-card__visual' . ($visualPlain ? ' cf4-bike-scene--plain' : '');
@endphp

<div {{ $attributes->class($layoutClasses) }}>
    @if ($isEmbed && isset($visual))
        <div class="cf4-state-visual-wrap" @if(! $staticVisual) aria-hidden="true" @endif>
            <div
                class="{{ $sceneBoxClass }}"
                @if ($scene && ! $staticVisual) data-cf4-scene="{{ $scene }}" @endif
            >
                {{ $visual }}
                @isset($fallback)
                    {{ $fallback }}
                @endisset
            </div>
        </div>
    @endif

    <div class="cf4-state-copy">
        @if ($eyebrow)
            <span class="cf4-state-eyebrow">
                <span class="cf4-state-eyebrow-dot" aria-hidden="true"></span>
                {{ $eyebrow }}
            </span>
        @endif

        @if ($code !== null && $code !== '')
            <p class="cf4-state-code">{{ $code }}</p>
        @endif

        <<?php echo $titleTagResolved; ?> class="cf4-state-title">{{ $title }}</<?php echo $titleTagResolved; ?>>
        <p class="cf4-state-message">{{ $message }}</p>

        @isset($actions)
            <div class="cf4-state-actions">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @if (! $isEmbed && isset($visual))
        <div class="cf4-state-visual-wrap" @if(! $staticVisual) aria-hidden="true" @endif>
            <div
                class="{{ $sceneBoxClass }}"
                @if ($scene && ! $staticVisual) data-cf4-scene="{{ $scene }}" @endif
            >
                {{ $visual }}
                @isset($fallback)
                    {{ $fallback }}
                @endisset
            </div>
        </div>
    @endif
</div>
