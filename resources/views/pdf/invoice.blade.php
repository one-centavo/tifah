<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura de Venta {{ $bill->invoice_number ?: 'FAC-' . str_pad((string) $bill->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: letter;
            margin: 15mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .company-logo {
            font-size: 24px;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: -0.5px;
        }

        .company-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .invoice-title-card {
            text-align: right;
        }

        .invoice-number-badge {
            display: inline-block;
            font-size: 18px;
            font-weight: 800;
            color: #1e3a8a;
            font-family: monospace;
            background: #eff6ff;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid #bfdbfe;
        }

        .invoice-status-annulled {
            color: #dc2626;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid #dc2626;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 4px;
            text-transform: uppercase;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-grid td {
            padding: 12px 16px;
            vertical-align: top;
        }

        .info-section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }

        .info-subvalue {
            font-size: 11px;
            color: #475569;
            margin-top: 2px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 10px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 8px;
        }

        .totals-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            float: right;
            width: 280px;
        }

        .grand-total {
            font-size: 16px;
            font-weight: 800;
            color: #1e3a8a;
            border-top: 2px solid #cbd5e1;
            padding-top: 6px;
            margin-top: 6px;
        }

        .footer-note {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }

        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .btn-print {
            background: #84cc16;
            color: #0f172a;
            font-weight: bold;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }

        @media print {
            .no-print-bar {
                display: none;
            }
            .invoice-container {
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-container">
        <!-- Print Action Bar (hidden when printing) -->
        <div class="no-print-bar">
            <span>Comprobante generado para impresión / exportación en PDF</span>
            <button class="btn-print" onclick="window.print()">Imprimir Factura</button>
        </div>

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div class="company-logo">TIFAH</div>
                    <div class="company-subtitle"><strong>Distribuidora y Trazabilidad Farmacéutica</strong></div>
                    <div class="info-subvalue">NIT: 901.234.567-8 &bull; Régimen Común</div>
                    <div class="info-subvalue">Dirección: Av. El Dorado # 68C-61, Bodega 12 &bull; Bogotá D.C.</div>
                    <div class="info-subvalue">Tel: (601) 745-8900 &bull; Email: ventas@tifah.com.co</div>
                </td>
                <td style="width: 40%; vertical-align: top;" class="invoice-title-card">
                    <div class="invoice-number-badge">
                        {{ $bill->invoice_number ?: 'FAC-' . str_pad((string) $bill->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                    <div class="info-subvalue" style="margin-top: 6px;">
                        <strong>Fecha de Emisión:</strong> {{ $bill->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div class="info-subvalue">
                        <strong>Atendido por:</strong> {{ $bill->creator?->name ?? 'Auxiliar de Bodega' }}
                    </div>

                    @if($bill->status === 'annulled')
                        <div class="invoice-status-annulled">FACTURA ANULADA</div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Customer & Payment Metadata -->
        <table class="info-grid">
            <tr>
                <td style="width: 50%; border-right: 1px solid #e2e8f0;">
                    <div class="info-section-title">Datos del Cliente</div>
                    <div class="info-value">{{ $bill->customer?->name ?? 'Cliente General' }}</div>
                    <div class="info-subvalue"><strong>NIT:</strong> {{ $bill->customer?->nit }}-{{ $bill->customer?->dv }}</div>
                    <div class="info-subvalue"><strong>Dirección:</strong> {{ $bill->customer?->address }}, {{ $bill->customer?->city }}</div>
                    <div class="info-subvalue"><strong>Teléfono:</strong> {{ $bill->customer?->phone_number }}</div>
                    <div class="info-subvalue"><strong>Email:</strong> {{ $bill->customer?->email }}</div>
                </td>
                <td style="width: 50%;">
                    <div class="info-section-title">Condiciones de Pago</div>
                    <div class="info-value">
                        @if($bill->payment_method === 'cash')
                            Efectivo de Contado
                        @elseif($bill->payment_method === 'transfer')
                            Transferencia Bancaria
                        @elseif($bill->payment_method === 'credit')
                            Crédito Comercial
                        @else
                            {{ ucfirst($bill->payment_method) }}
                        @endif
                    </div>

                    @if($bill->payment_method === 'credit' && $bill->payment_due_date)
                        <div class="info-subvalue" style="color: #b45309; font-weight: 600;">
                            <strong>Fecha Límite de Pago:</strong> {{ $bill->payment_due_date->format('d/m/Y') }}
                        </div>
                    @endif

                    <div class="info-subvalue" style="margin-top: 6px;">
                        <strong>Estado:</strong> 
                        <span style="font-weight: bold; color: {{ $bill->status === 'active' ? '#15803d' : '#dc2626' }};">
                            {{ $bill->status === 'active' ? 'ACTIVA / APROBADA' : 'ANULADA' }}
                        </span>
                    </div>

                    @if($bill->status === 'annulled')
                        <div class="info-subvalue" style="color: #dc2626;">
                            <strong>Motivo Anulación:</strong> {{ $bill->annulled_reason }}
                            <br><small>Por {{ $bill->annuller?->name ?? 'Sistema' }} el {{ $bill->annulled_at?->format('d/m/Y H:i') }}</small>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Itemized Products Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 40%;">Descripción del Medicamento</th>
                    <th style="width: 15%;">Lote</th>
                    <th style="width: 12%;">Vence</th>
                    <th style="width: 8%;" class="text-center">Cant.</th>
                    <th style="width: 10%;" class="text-right">Precio Unit.</th>
                    <th style="width: 10%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bill->details as $index => $detail)
                    <tr>
                        <td class="text-center text-slate-400">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $detail->lot?->medicine?->name ?? 'Medicamento' }}</strong>
                            <div class="info-subvalue">{{ $detail->lot?->medicine?->presentation ?? '' }}</div>
                        </td>
                        <td class="font-mono"><strong>{{ $detail->lot?->batch_number ?? 'N/A' }}</strong></td>
                        <td>{{ $detail->lot?->expiration_date ?? 'N/A' }}</td>
                        <td class="text-center font-mono"><strong>{{ $detail->quantity }}</strong></td>
                        <td class="text-right font-mono">${{ number_format((float) $detail->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right font-mono"><strong>${{ number_format((float) $detail->subtotal, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                            Sin renglones registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Totals Summary -->
        <div style="width: 100%; overflow: hidden;">
            <div class="totals-box">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal Productos:</td>
                        <td class="text-right font-mono">${{ number_format((float) $bill->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Ajuste / Redondeo:</td>
                        <td class="text-right font-mono">$0</td>
                    </tr>
                    <tr class="grand-total">
                        <td>TOTAL A PAGAR:</td>
                        <td class="text-right font-mono">${{ number_format((float) $bill->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            <p>Esta factura de venta y salida de mercancía cumple con las normas de Buenas Prácticas de Distribución (BPD) y trazabilidad por lote de medicamentos.</p>
            <p>TIFAH Software de Gestión y Trazabilidad Farmacéutica &bull; Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

</body>
</html>
