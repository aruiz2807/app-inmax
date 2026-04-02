<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket</title>
    <style>
        @page {
            size: 80mm auto; /* ticket width, automatic height */
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
            width: 50mm; /* bigger, almost the full ticket width */
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

        .items .qty {
            width: 15%;
        }

        .items .desc {
            width: 65%;
        }

        .items .price {
            width: 20%;
            text-align: right;
        }

        .total {
            font-weight: bold;
            margin-top: 5px;
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
        <div class="center small-text">Torre Médica, Av. Plan de San Luis #1831, San Bernardo</div>
        <div class="center small-text">TEL: 3313666626</div>
        <div class="line"></div>

        <div class="small-text">
            <strong>Folio:</strong> {{$note->id}}<br>
            <strong>Fecha:</strong> {{$note->created_at}}<br>
            <strong>Paciente:</strong> {{$note->appointment->user->name}}<br>
        </div>

        <div class="line"></div>

        <table class="items">
            @foreach ($note->appointment->services as $service)
                @if($service->status === 'Completed')
                <tr>
                    <td class="qty">1</td>
                    <td class="desc">{!! nl2br(e($service->service->name)) !!}</td>
                    <td class="price">{{ $service->covered ? 'Incluido' : 'Adicional' }}</td>
                </tr>
                @endif
            @endforeach
        </table>

        <div class="line"></div>

        <div class="total">
            <table class="items">
                <tr>
                    <td class="desc">Total cuenta: </td>
                    <td class="price">${{ $subtotal }} </td>
                </tr>

                <tr>
                    <td class="desc">Cobro al paciente: </td>
                    <td class="price">${{ $payment }} </td>
                </tr>

                <tr>
                    <td class="desc">Comision Inmax: </td>
                    <td class="price">${{ $commision }} </td>
                </tr>

                <tr>
                    <td class="desc">Ganancia del proveedor: </td>
                    <td class="price">${{ $total }} </td>
                </tr>
            </table>
        </div>

        <div class="line"></div>

        <div class="footer">
            Gracias por su preferencia<br>
            www.inmax-sure.com
        </div>
    </div>
</body>
</html>
