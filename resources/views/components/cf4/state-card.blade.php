{{-- @deprecated Use <x-shared.state-card />. --}}
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

<x-shared.state-card
    :variant="$variant"
    :eyebrow="$eyebrow"
    :code="$code"
    :title="$title"
    :message="$message"
    :scene="$scene"
    :static-visual="$staticVisual"
    :visual-plain="$visualPlain"
    :title-tag="$titleTag"
    :bare="$bare"
    {{ $attributes }}
>
    @isset($visual)
        <x-slot:visual>{{ $visual }}</x-slot:visual>
    @endisset

    @isset($fallback)
        <x-slot:fallback>{{ $fallback }}</x-slot:fallback>
    @endisset

    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset

    {{ $slot }}
</x-shared.state-card>
