@extends('admin.exports.layouts.pdf-master')

@section('pdf_body')
    <div class="section">
        <h2>Detalle</h2>
        @if(count($rows) === 0)
            <div class="empty-state">No hay registros para mostrar.</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        @foreach($headers as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
