<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guía Física de Retiro y Marcación de Lotes Próximos a Vencer — TIFAH</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background-color: #ffffff;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .guide-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 16px;
        }

        .no-print {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: background 0.15s ease;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
            margin-left: 8px;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
        }

        .company-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.05em;
            padding: 6px 8px;
            border: 1px solid #0f172a;
            text-align: left;
        }

        .data-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            display: inline-block;
        }

        .badge-warning {
            background-color: #ffedd5;
            color: #9a3412;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            display: inline-block;
        }

        .badge-attention {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9.5px;
            display: inline-block;
        }

        .checkbox-box {
            width: 14px;
            height: 14px;
            border: 1.5px solid #475569;
            border-radius: 2px;
            display: inline-block;
            margin: 0 auto;
        }

        .signatures-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .sig-line {
            border-top: 1px solid #0f172a;
            margin-top: 40px;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 600;
            text-align: center;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }

            .guide-container {
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="guide-container">
        <!-- Print Toolbar -->
        <div class="no-print">
            <div>
                <strong>Guía Física de Control y Marcación de Lotes</strong> — Documento para auditoría en bodega.
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    🖨️ Imprimir / Guardar como PDF
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.close()">
                    Cerrar
                </button>
            </div>
        </div>

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="company-title">TIFAH — Distribuidora y Trazabilidad Farmacéutica</div>
                    <div style="font-size: 11px; color: #475569; margin-top: 2px;">
                        Control de Calidad, Almacenamiento y Buenas Prácticas de Distribución (BPD)
                    </div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div class="doc-title">Guía de Retiro y Marcación</div>
                    <div style="font-size: 10.5px; color: #64748b;">
                        Generado: {{ $generatedAt->format('d/m/Y H:i') }} | Por: {{ $generatedBy?->name ?? 'Sistema' }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Metadata & Summary Bar -->
        <div class="meta-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 25%;"><strong>Lotes a Inspeccionar:</strong> {{ $lots->count() }}</td>
                    <td style="width: 25%;"><span class="badge-critical">Crítico (≤30d):</span> {{ $metrics['critical_count'] }}</td>
                    <td style="width: 25%;"><span class="badge-warning">Advertencia (31-60d):</span> {{ $metrics['warning_count'] }}</td>
                    <td style="width: 25%;"><span class="badge-attention">Atención (61-90d):</span> {{ $metrics['attention_count'] }}</td>
                </tr>
            </table>
        </div>

        <!-- Table of Batches -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%; text-align: center;">#</th>
                    <th style="width: 9%; text-align: center;">Urgencia</th>
                    <th style="width: 27%;">Medicamento / Genérico</th>
                    <th style="width: 13%;">Presentación</th>
                    <th style="width: 11%;">Lote</th>
                    <th style="width: 9%; text-align: center;">Vence</th>
                    <th style="width: 6%; text-align: center;">Días</th>
                    <th style="width: 6%; text-align: right;">Stock</th>
                    <th style="width: 7%; text-align: center;">Físico OK</th>
                    <th style="width: 8%; text-align: center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lots as $index => $lot)
                    @php
                        $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                        $tier = $days <= 30 ? 'critical' : ($days <= 60 ? 'warning' : 'attention');
                    @endphp
                    <tr>
                        <td style="text-align: center; color: #64748b;">{{ $index + 1 }}</td>
                        <td style="text-align: center;">
                            @if($tier === 'critical')
                                <span class="badge-critical">30d · Rojo</span>
                            @elseif($tier === 'warning')
                                <span class="badge-warning">60d · Naranja</span>
                            @else
                                <span class="badge-attention">90d · Amarillo</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $lot->medicine->name }}</strong>
                            <div style="font-size: 9.5px; color: #64748b;">{{ $lot->medicine->generic_name }}</div>
                        </td>
                        <td>{{ $lot->medicine->presentation }}</td>
                        <td style="font-family: monospace; font-weight: 700;">{{ $lot->batch_number }}</td>
                        <td style="text-align: center;">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $days }}d</td>
                        <td style="text-align: right; font-weight: 700;">{{ number_format($lot->current_quantity, 0, ',', '.') }}</td>
                        <td style="text-align: center;">
                            <div class="checkbox-box"></div>
                        </td>
                        <td style="font-size: 9px; color: #64748b; text-align: center;">
                            [ ] Marcar / [ ] Retirar
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px; color: #64748b;">
                            No hay lotes que requieran inspección física para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures & Audit Footer -->
        <table class="signatures-table">
            <tr>
                <td style="width: 45%; padding-right: 20px;">
                    <div class="sig-line">
                        Firma Auxiliar de Bodega / Responsable de Inspección
                        <div style="font-weight: normal; font-size: 10px; color: #64748b; margin-top: 2px;">
                            Nombre: _____________________________________
                        </div>
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 45%; padding-left: 20px;">
                    <div class="sig-line">
                        Firma Supervisor de Calidad / Director Técnico
                        <div style="font-weight: normal; font-size: 10px; color: #64748b; margin-top: 2px;">
                            Nombre: _____________________________________
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
