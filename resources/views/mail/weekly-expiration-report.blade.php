<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Semanal de Alertas de Vencimiento</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; line-height: 1.5;">
    <div style="max-width: 700px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <div style="background-color: #0f172a; padding: 24px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.025em;">TIFAH — Distribuidora Farmacéutica</h1>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #94a3b8;">Reporte Consolidado de Alertas de Vencimiento</p>
        </div>

        <div style="padding: 24px;">
            <p style="margin-top: 0; font-size: 15px;">
                Estimado(a) <strong>{{ $admin->name }}</strong>,
            </p>
            <p style="font-size: 14px; color: #475569;">
                A continuación se presenta el informe semanal automático de lotes de medicamentos que se encuentran dentro de la ventana de alerta de vencimiento (próximos a vencer en 30, 60 o 90 días) al día <strong>{{ $reportData['generatedAt']->format('d/m/Y H:i') }}</strong>.
            </p>

            <!-- Metrics Summary Cards -->
            <div style="margin: 20px 0; background-color: #f1f5f9; border-radius: 6px; padding: 16px;">
                <h2 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; color: #334155; letter-spacing: 0.05em;">
                    Resumen Financiero y Operativo
                </h2>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b;">Total de Lotes en Alerta:</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #0f172a;">{{ $reportData['metrics']['total_lots'] }} lotes ({{ number_format($reportData['metrics']['total_units'], 0, ',', '.') }} uds)</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #dc2626; font-weight: 600;">Críticos (≤ 30 días):</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #dc2626;">{{ $reportData['metrics']['critical_count'] }} lotes — ${{ number_format($reportData['metrics']['critical_monetary_risk'], 0, ',', '.') }} COP</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #ea580c; font-weight: 600;">Advertencia (31 a 60 días):</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #ea580c;">{{ $reportData['metrics']['warning_count'] }} lotes — ${{ number_format($reportData['metrics']['warning_monetary_risk'], 0, ',', '.') }} COP</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #d97706; font-weight: 600;">Atención (61 a 90 días):</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: 700; color: #d97706;">{{ $reportData['metrics']['attention_count'] }} lotes — ${{ number_format($reportData['metrics']['attention_monetary_risk'], 0, ',', '.') }} COP</td>
                    </tr>
                    <tr style="border-top: 1px solid #cbd5e1;">
                        <td style="padding: 10px 0 0 0; font-size: 15px; font-weight: 700; color: #0f172a;">Valor Monetario Total en Riesgo:</td>
                        <td style="padding: 10px 0 0 0; text-align: right; font-size: 16px; font-weight: 800; color: #b91c1c;">
                            ${{ number_format($reportData['metrics']['total_monetary_risk'], 0, ',', '.') }} COP
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Itemized Table (Top Critical & Warning Batches) -->
            <h2 style="margin: 24px 0 12px 0; font-size: 15px; font-weight: 700; color: #0f172a;">
                Lotes Prioritarios de Salida
            </h2>

            @if(count($reportData['criticalLots']) > 0 || count($reportData['warningLots']) > 0 || count($reportData['attentionLots']) > 0)
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                            <th style="padding: 8px; color: #475569;">Medicamento</th>
                            <th style="padding: 8px; color: #475569;">Lote</th>
                            <th style="padding: 8px; color: #475569; text-align: center;">Vence</th>
                            <th style="padding: 8px; color: #475569; text-align: center;">Días</th>
                            <th style="padding: 8px; color: #475569; text-align: right;">Stock</th>
                            <th style="padding: 8px; color: #475569; text-align: right;">Valor Riesgo (COP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['criticalLots'] as $lot)
                            @php
                                $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                $risk = (int) round((float) $lot->current_quantity * (float) $lot->unit_purchase_price);
                            @endphp
                            <tr style="border-bottom: 1px solid #fee2e2; background-color: #fef2f2;">
                                <td style="padding: 8px; font-weight: 600; color: #991b1b;">
                                    {{ $lot->medicine->name }}
                                    <div style="font-size: 11px; font-weight: normal; color: #b91c1c;">{{ $lot->medicine->generic_name }}</div>
                                </td>
                                <td style="padding: 8px; font-family: monospace; font-weight: 600; color: #991b1b;">{{ $lot->batch_number }}</td>
                                <td style="padding: 8px; text-align: center; color: #991b1b;">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</td>
                                <td style="padding: 8px; text-align: center; font-weight: 700; color: #b91c1c;">{{ $days }}d (Rojo)</td>
                                <td style="padding: 8px; text-align: right; font-weight: 600; color: #991b1b;">{{ number_format($lot->current_quantity, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right; font-weight: 700; color: #991b1b;">${{ number_format($risk, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @foreach($reportData['warningLots'] as $lot)
                            @php
                                $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                $risk = (int) round((float) $lot->current_quantity * (float) $lot->unit_purchase_price);
                            @endphp
                            <tr style="border-bottom: 1px solid #ffedd5; background-color: #fff7ed;">
                                <td style="padding: 8px; font-weight: 600; color: #9a3412;">
                                    {{ $lot->medicine->name }}
                                    <div style="font-size: 11px; font-weight: normal; color: #c2410c;">{{ $lot->medicine->generic_name }}</div>
                                </td>
                                <td style="padding: 8px; font-family: monospace; font-weight: 600; color: #9a3412;">{{ $lot->batch_number }}</td>
                                <td style="padding: 8px; text-align: center; color: #9a3412;">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</td>
                                <td style="padding: 8px; text-align: center; font-weight: 700; color: #c2410c;">{{ $days }}d (Naranja)</td>
                                <td style="padding: 8px; text-align: right; font-weight: 600; color: #9a3412;">{{ number_format($lot->current_quantity, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right; font-weight: 700; color: #9a3412;">${{ number_format($risk, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach

                        @foreach($reportData['attentionLots'] as $lot)
                            @php
                                $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                $risk = (int) round((float) $lot->current_quantity * (float) $lot->unit_purchase_price);
                            @endphp
                            <tr style="border-bottom: 1px solid #fef3c7; background-color: #fffbeb;">
                                <td style="padding: 8px; font-weight: 600; color: #92400e;">
                                    {{ $lot->medicine->name }}
                                    <div style="font-size: 11px; font-weight: normal; color: #b45309;">{{ $lot->medicine->generic_name }}</div>
                                </td>
                                <td style="padding: 8px; font-family: monospace; font-weight: 600; color: #92400e;">{{ $lot->batch_number }}</td>
                                <td style="padding: 8px; text-align: center; color: #92400e;">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</td>
                                <td style="padding: 8px; text-align: center; font-weight: 700; color: #b45309;">{{ $days }}d (Amarillo)</td>
                                <td style="padding: 8px; text-align: right; font-weight: 600; color: #92400e;">{{ number_format($lot->current_quantity, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right; font-weight: 700; color: #92400e;">${{ number_format($risk, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size: 14px; color: #16a34a; background-color: #f0fdf4; padding: 12px; border-radius: 6px;">
                    ✓ No hay lotes activos próximos a vencer en los próximos 90 días.
                </p>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
                Para gestionar o priorizar la salida de estos productos mediante ventas FEFO o tramitar devoluciones con los proveedores correspondientes, ingrese al módulo de <strong>Alertas de Vencimiento</strong> en la plataforma.
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8;">
            © {{ date('Y') }} TIFAH - Sistema de Gestión y Trazabilidad Farmacéutica. Mensaje generado automáticamente.
        </div>
    </div>
</body>
</html>
