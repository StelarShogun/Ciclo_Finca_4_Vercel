{{-- @deprecated Use <x-shared.file-upload />. --}}
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

<x-shared.file-upload
    :id="$id"
    :name="$name"
    :label="$label"
    :accept="$accept"
    :multiple="$multiple"
    :webkitdirectory="$webkitdirectory"
    :hint="$hint"
    :variant="$variant"
    :required="$required"
    :meta-id="$metaId"
    :icon="$icon"
    {{ $attributes }}
>
    {{ $slot }}
</x-shared.file-upload>
