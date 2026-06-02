{{-- @deprecated Use <x-admin.admin-alert />. --}}
@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'dismissible' => null,
])

<x-admin.admin-alert
    :type="$type"
    :title="$title"
    :message="$message"
    :dismissible="$dismissible"
    {{ $attributes }}
>
    {{ $slot }}
</x-admin.admin-alert>
