<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloReporte }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1a202c;
            font-size: 11px;
            line-height: 1.3;
            background-color: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #1a365d;
            padding-bottom: 5px;
        }
        .header h2 {
            margin: 0;
            color: #1a365d;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0 0 0;
            color: #4a5568;
            font-size: 10px;
            font-weight: 500;
        }
        
        /* Estructura de tabla sólida para controlar las posiciones sin desparramarse */
        .credenciales-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px; /* Espaciado uniforme entre tarjetas */
        }
        
        /* Forzar que cada fila de la tabla evite romperse entre páginas */
        .credenciales-row {
            page-break-inside: avoid;
        }
        
        .credenciales-cell {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }
        
        /* Tarjeta de credencial individual con altura controlada y prohibición de corte interno */
        .credencial-card {
            border: 1px dashed #4a5568;
            background-color: #ffffff;
            padding: 10px;
            box-sizing: border-box;
            height: 180px; /* Altura fija para garantizar consistencia por página */
            page-break-inside: avoid; 
        }
        
        .card-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .card-title {
            font-weight: bold;
            font-size: 9px;
            color: #2b6cb0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-row {
            margin-bottom: 3px;
        }
        .info-label {
            font-weight: bold;
            color: #4a5568;
            display: inline-block;
            width: 75px;
        }
        .info-value {
            color: #1a202c;
        }
        .credenciales-box {
            background-color: #f7fafc;
            padding: 5px 8px;
            margin-top: 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }
        .credenciales-box code {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            font-size: 11px;
            color: #2d3748;
        }
        .badge-mesa {
            background-color: #1a365d;
            color: #ffffff;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 9px;
            border-radius: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $tituloReporte }}</h2>
        <p>{{ $subtitulo }} | Total Credenciales: {{ $digitadores->count() }}</p>
    </div>

    <table class="credenciales-table">
        <tbody>
            @foreach($digitadores->chunk(2) as $par)
                <tr class="credenciales-row">
                    @foreach($par as $user)
                        <td class="credenciales-cell">
                            <div class="credencial-card">
                                <div class="card-header">
                                    <span class="card-title">SISTEMA ELECTORAL DE CONTROL</span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Digitador:</span>
                                    <span class="info-value" style="font-weight: bold; color: #b7791f;">{{ strtoupper($user->dignidad_asignada ?? 'N/A') }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Cantón:</span>
                                    <span class="info-value">{{ $user->mesa->recinto->parroquia->canton->nombre ?? 'N/A' }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Parroquia:</span>
                                    <span class="info-value">{{ $user->mesa->recinto->parroquia->nombre ?? 'N/A' }}</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Recinto:</span>
                                    <span class="info-value" style="font-size: 9px; font-weight: 600; color: #2d3748;">
                                        {{ $user->mesa->recinto->nombre ?? 'N/A' }}
                                    </span>
                                </div>

                                <div class="info-row" style="margin-top: 5px;">
                                    <span class="info-label">Junta / Mesa:</span>
                                    <span class="badge-mesa">#{{ $user->mesa->numero ?? 'N/A' }} ({{ $user->mesa->genero ?? 'N/A' }})</span>
                                </div>

                                <div class="credenciales-box">
                                    <div style="margin-bottom: 2px;"><strong>Usuario:</strong> <code>{{ $user->email }}</code></div>
                                   <div><strong>Contraseña:</strong> <code>{{ $user->password_plain }}</code></div>
                                </div>
                            </div>
                        </td>
                    @endforeach

                    {{-- Si el último grupo es impar, agregamos una celda vacía para mantener la simetría perfecta --}}
                    @if($par->count() == 1)
                        <td class="credenciales-cell"></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>