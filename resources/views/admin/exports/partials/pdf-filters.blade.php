@php
    /** @var array<int, string> $filterLines */
    $filterLines = $filterLines ?? [];
@endphp
@if(count($filterLines) > 0)
    <div class="filters-block">
        <h2>Filtros aplicados</h2>
        <ul>
            @foreach($filterLines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
@endif
