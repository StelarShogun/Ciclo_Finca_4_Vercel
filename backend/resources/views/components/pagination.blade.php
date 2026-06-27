{{-- @deprecated Use <x-shared.pagination />. --}}
@props([
    'paginator',
    'label' => null,
])

<x-shared.pagination :paginator="$paginator" :label="$label" {{ $attributes }} />
