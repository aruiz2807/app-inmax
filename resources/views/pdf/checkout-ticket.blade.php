<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de receta</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 5mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111111;
        }

        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        .logo { width: 50mm; height: auto; margin: 0 auto 5px auto; display: block; }
        .header-text { font-size: 12px; font-weight: bold; }
        .small-text { font-size: 9px; }
        .items { width: 100%; margin-top: 5px; border-collapse: collapse; }
        .items td { padding: 2px 0; vertical-align: top; }
        .qty { width: 12%; }
        .desc { width: 58%; }
        .price { width: 30%; text-align: right; }
        .totals { width: 100%; margin-top: 6px; border-collapse: collapse; }
        .totals td { padding: 2px 0; }
        .totals .label { text-align: left; }
        .totals .value { text-align: right; }
        .total-row { font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    <img class="logo" src="{{ public_path('/img/LogoINMAXSUP.png') }}" alt="Logo">

    <div class="center header-text">INMAX</div>
    <div class="center small-text">Ticket de receta de medicamentos</div>
    <div class="line"></div>

    <div class="small-text">
        <strong>Cita:</strong> {{ $appointment->id }}<br>
        <strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Paciente:</strong> {{ $appointment->user?->name ?? 'Sin paciente' }}<br>
        <strong>Atiende:</strong> {{ $appointment->doctor?->user?->name ?? 'Sin medico' }}<br>
        <strong>Beneficio:</strong> {{ $benefitLabel }}
    </div>

    <div class="line"></div>

    <table class="items">
        @foreach($rows as $row)
            <tr>
                <td class="qty">{{ $row['quantity'] }}</td>
                <td class="desc">{{ $row['name'] }} ({{ $row['trade_name'] }})</td>
                <td class="price">${{ number_format($row['line_total'], 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td class="label">Subtotal (precio publico):</td>
            <td class="value">${{ $subtotalPublic }}</td>
        </tr>

        <tr>
            <td class="label">Descuento aplicado:</td>
            <td class="value">{{ $discountApplied ? 'Si' : 'No' }}</td>
        </tr>

        <tr>
            <td class="label">Tipo descuento:</td>
            <td class="value">{{ $discountType }}</td>
        </tr>

        <tr>
            <td class="label">Subtotal con beneficio:</td>
            <td class="value">${{ $subtotalCharged }}</td>
        </tr>

        <tr>
            <td class="label">Monto descuento:</td>
            <td class="value">-${{ $discount }}</td>
        </tr>

        <tr class="total-row">
            <td class="label">Total a pagar:</td>
            <td class="value">${{ $total }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="footer">
        Gracias por su preferencia<br>
        www.inmax-sure.com
    </div>
</body>
</html>
