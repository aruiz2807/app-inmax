<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket membresia</title>
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

        .ticket {
            width: 100%;
            padding: 0;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .logo {
            width: 50mm;
            height: auto;
            margin: 0 auto 5px auto;
            display: block;
        }

        .header-text {
            font-size: 12px;
            font-weight: bold;
        }

        .small-text {
            font-size: 9px;
        }

        .items {
            width: 100%;
            margin-top: 5px;
        }

        .items td {
            padding: 2px 0;
        }

        .items .desc {
            width: 60%;
        }

        .items .price {
            width: 40%;
            text-align: right;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <img class="logo" src="{{ public_path('/img/LogoINMAXSUP.png') }}" alt="Logo">

        <div class="center header-text">INMAX</div>
        <div class="center small-text">Torre Medica, Av. Plan de San Luis #1831, San Bernardo</div>
        <div class="center small-text">TEL: 3313666626</div>
        <div class="line"></div>

        <div class="small-text">
            <strong>Folio membresia:</strong> {{ $policy->id }}<br>
            <strong>No. membresia:</strong> {{ $policy->number }}<br>
            <strong>Fecha registro:</strong> {{ optional($policy->created_at)->format('d/m/Y H:i') }}<br>
            <strong>Titular:</strong> {{ $policy->user?->name ?? 'N/A' }}<br>
            <strong>Empresa:</strong> {{ $policy->user?->company?->name ?? 'N/A' }}<br>
        </div>

        <div class="line"></div>

        <table class="items">
            <tr>
                <td class="desc">Plan contratado</td>
                <td class="price">{{ $policy->plan?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="desc">Tipo membresia</td>
                <td class="price">{{ $policy->type }}</td>
            </tr>
            <tr>
                <td class="desc">Vigencia inicio</td>
                <td class="price">{{ optional($policy->start_date)->format('d/m/Y') ?? 'Pendiente' }}</td>
            </tr>
            <tr>
                <td class="desc">Vigencia fin</td>
                <td class="price">{{ optional($policy->end_date)->format('d/m/Y') ?? 'Pendiente' }}</td>
            </tr>
            <tr>
                <td class="desc">Metodo pago</td>
                <td class="price">{{ $paymentMethodLabel }}</td>
            </tr>
            <tr>
                <td class="desc">Monto plan</td>
                <td class="price">${{ $planPrice }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="footer">
            Gracias por su preferencia<br>
            {{ $contactEmail }}<br>
            www.inmax.com
        </div>
    </div>
</body>
</html>
