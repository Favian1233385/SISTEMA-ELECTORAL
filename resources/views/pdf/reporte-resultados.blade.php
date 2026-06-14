<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Resultados - Sistema De Gestión Electoral</title>
    <style>
        @page { margin: 2cm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #1e3a8a;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0;
            font-weight: bold;
            color: #666;
        }
        .info-tabla {
            width: 100%;
            margin-bottom: 20px;
            background-color: #f8fafc;
            padding: 10px;
            border-radius: 8px;
        }
        .info-tabla td {
            padding: 5px;
        }
        table.resultados {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.resultados th {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
            font-size: 10px;
        }
        table.resultados td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .resumen-votos {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .resumen-votos td {
            padding: 8px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
        .firmas {
            margin-top: 60px;
            width: 100%;
        }
        .firma-box {
            text-align: center;
            width: 45%;
        }
        .linea-firma {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }
        .escrutinio-badge {
            color: #1e40af;
            font-weight: bold;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SISTEMA DE GESTIÓN ELECTORAL - ECUADOR</h1>
        <p>REPORTE OFICIAL DE RESULTADOS: {{ strtoupper($dignidadSeleccionada) }}</p>
    </div>

    <table class="info-tabla">
        <tr>
            <td><strong>Fecha de Generación:</strong> {{ $fecha }}</td>
            <td><strong>Jurisdicción:</strong> {{ $lugar }}</td>
        </tr>
        <tr>
            <td><strong>Generado por:</strong> {{ $usuario }}</td>
            <td class="escrutinio-badge">
                <!-- Se corrigió el cálculo ficticio de porcentaje por la variable real calculada de participación -->
                <strong>Escrutinio:</strong> {{ $totalActas }} Actas ({{ $porcentajeParticipacion }}% Participación)
            </td>
        </tr>
    </table>

    <table class="resultados">
        <thead>
            <tr>
                <th>Ranking</th>
                <th>Candidato</th>
                <th>Organización Política</th>
                <th style="text-align: right;">Votos</th>
                <th style="text-align: right;">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $index => $candidato)
                <tr>
                    <td style="width: 50px; text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $candidato->nombre }}</strong></td>
                    <td style="font-style: italic;">{{ $candidato->partido->nombre ?? 'Alianza' }}</td>
                    <td style="text-align: right; font-family: monospace;">{{ number_format($candidato->total_votos) }}</td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ $totalSufragantes > 0 ? number_format(($candidato->total_votos / $totalSufragantes) * 100, 2) : '0.00' }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- BLOQUE 1: Desglose físico de papeletas encontradas dentro de la urna -->
    <table class="resumen-votos">
        <tr>
            <td style="width: 25%;">Votos Válidos: {{ number_format($totalVotosValidos) }}</td>
            <td style="width: 25%;">Blancos: {{ number_format($totalVotosBlancos) }}</td>
            <td style="width: 25%;">Nulos: {{ number_format($totalVotosNulos) }}</td>
            <td style="background-color: #e2e8f0; color: #1e3a8a; width: 25%;">SUFRAGANTES: {{ number_format($totalSufragantes) }}</td>
        </tr>
    </table>

    <!-- BLOQUE 2: Cuadrante de control general (Ausentismo frente al Padrón Total Esperado) -->
    <table class="resumen-votos" style="margin-top: 5px;">
        <tr>
            <td style="width: 50%; color: #b91c1c; background-color: #fef2f2;">
                Ausentismo: {{ number_format($ausentismo) }} ({{ $porcentajeAusentismo }}%)
            </td>
            <td style="background-color: #1e3a8a; color: white; width: 50%; font-size: 13px;">
                TOTAL PADRÓN ELECTORAL: {{ number_format($totalPadron) }}
            </td>
        </tr>
    </table>

    <div class="firmas">
        <table style="width: 100%;">
            <tr>
                <td class="firma-box">
                    <div class="linea-firma">
                        Responsable de Centro de Cómputo<br>
                        <strong>{{ $usuario }}</strong>
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td class="firma-box">
                    <div class="linea-firma">
                        Delegado de Control Electoral<br>
                        Observador
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento es un reporte generado automáticamente por el sistema de Gestión Electoral.<br>
        &copy; {{ date('Y') }} - Sevilla Don Bosco, Morona Santiago.
    </div>

</body>
</html>