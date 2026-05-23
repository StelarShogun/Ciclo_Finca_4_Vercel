@php
    $cf4FlashPayload = [
        'status' => session('status'),
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ];

    $cf4FlashPayload = array_filter($cf4FlashPayload, fn ($value) => filled($value));
@endphp

@if (! empty($cf4FlashPayload))
    <script>
        window.__cf4Flash = @json($cf4FlashPayload);
    </script>
@endif
