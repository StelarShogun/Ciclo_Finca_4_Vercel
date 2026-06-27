@props([
    'id',
    'name' => null,
    'label' => '',
    'accept' => '',
    'multiple' => false,
    'webkitdirectory' => false,
    'hint' => '',
    'variant' => 'default',
    'required' => false,
    'metaId' => null,
    'icon' => 'fa-image',
])

@php
    $inputName = $name ?? $id;
    $metaId = $metaId ?? $id . '-meta';
    $variantClass = $variant === 'compact' ? ' cf-file-upload--compact' : '';
    $directoryAttr = $webkitdirectory ? 'webkitdirectory' : '';
@endphp

<div class="form-group cf-file-upload-field" data-cf-file-upload="{{ $id }}">
    @if ($label !== '')
        <span class="cf-file-upload-field__label">{{ $label }}@if($required) *@endif</span>
    @endif

    <label for="{{ $id }}" class="cf-file-upload{{ $variantClass }}" id="{{ $id }}-trigger">
        <i class="fas {{ $icon }} cf-file-upload__icon" aria-hidden="true"></i>
        <span class="cf-file-upload__text">{{ $slot->isEmpty() ? 'Haz clic o arrastra un archivo aquí' : $slot }}</span>
        @if ($hint !== '')
            <span class="cf-file-upload__hint">{{ $hint }}</span>
        @endif
    </label>

    <input
        type="file"
        id="{{ $id }}"
        name="{{ $inputName }}"
        class="cf-file-upload__input"
        accept="{{ $accept }}"
        @if($multiple) multiple @endif
        {!! $directoryAttr !!}
        @if($required) required @endif
    >

    <div id="{{ $metaId }}" class="cf-file-upload-meta hidden" aria-live="polite"></div>
</div>
