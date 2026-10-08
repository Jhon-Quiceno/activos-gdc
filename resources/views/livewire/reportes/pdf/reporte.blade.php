<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 2px 4px; text-align: left; }
        th { background: #e5e5e5; }
    </style>
</head>
<body>
    <h2>{{ $titulo }}</h2>
    <p>Gobernación de Córdoba · Dirección TIC · Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <table>
        <thead>
            <tr>@foreach ($encabezados as $e)<th>{{ $e }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                <tr>@foreach ($fila as $celda)<td>{{ $celda }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>