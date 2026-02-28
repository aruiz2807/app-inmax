<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Medica</title>
    <style>
        @page {
            size: letter;
            margin: 14mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111111;
        }

        .sheet {
            width: 100%;
        }

        .line {
            border-top: 2px solid #000000;
            height: 0;
        }

        .space-10 { margin-top: 10px; }
        .space-12 { margin-top: 12px; }
        .space-16 { margin-top: 16px; }
        .space-20 { margin-top: 20px; }
        .space-28 { margin-top: 28px; }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-logo {
            width: 28%;
        }

        .header-center {
            width: 52%;
            text-align: center;
            line-height: 1.35;
        }

        .header-right {
            width: 20%;
        }

        .logo {
            width: 145px;
            height: auto;
        }

        .doctor-name {
            font-size: 15px;
            font-weight: 700;
        }

        .doctor-sub {
            font-size: 11px;
            font-weight: 600;
        }

        .doctor-text {
            font-size: 10px;
        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
        }

        .patient-table td {
            width: 33.33%;
            border-right: 1px solid #cccccc;
            padding: 7px 10px;
            vertical-align: top;
            word-break: break-word;
        }

        .patient-table td:last-child {
            border-right: 0;
        }

        .label {
            font-weight: 700;
        }

        .block-title {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .product-row {
            border: 1px solid #000000;
            padding: 8px 10px;
            font-weight: 700;
            line-height: 1.35;
            margin-bottom: 8px;
            word-break: break-word;
            page-break-inside: avoid;
        }

        .text-block {
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .text-title {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .state {
            border: 1px solid #000000;
            padding: 2px 8px;
            font-size: 9px;
            font-weight: 700;
            display: inline-block;
            margin-left: 8px;
        }

        .text-content {
            line-height: 1.5;
            word-break: break-word;
        }

        .signature {
            margin-top: 42px;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-line {
            width: 210px;
            margin: 0 auto;
            border-top: 2px solid #000000;
            height: 0;
        }

        .signature-label {
            margin-top: 6px;
            font-weight: 700;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 24px;
        }

        .footer-left {
            width: 20%;
            font-size: 22px;
            font-weight: 800;
            color: #0C385A;
        }

        .footer-right {
            width: 80%;
            text-align: right;
            color: #0C385A;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.45;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="line"></div>

        <table class="header-table space-12">
            <tr>
                <td class="header-logo">
                    <img class="logo" src="{{ public_path('/img/LogoINMAXSUP.png') }}" alt="Logo">
                </td>
                <td class="header-center">
                    <div class="doctor-name">{{$note->appointment->doctor->user->name}}</div>
                    <div class="doctor-sub">{{$note->appointment->doctor->specialty->name}}</div>
                    <div class="doctor-sub">CEDULA PROFESIONAL: {{$note->appointment->doctor->license}}</div>
                    <div class="doctor-text">{{$note->appointment->doctor->university}}</div>
                    <div class="doctor-sub space-10"></div>
                    <div class="doctor-text">{{$note->appointment->doctor->address}}</div>
                    <div class="doctor-sub">TEL. {{$note->appointment->doctor->user->phone}}</div>
                </td>
                <td class="header-right"></td>
            </tr>
        </table>

        <div class="line space-10"></div>

        <table class="patient-table space-16">
            <tr>
                <td><span class="label">Fecha:</span> {{$note->created_at}}</td>
                <td><span class="label">Nombre:</span> {{$note->appointment->user->name}}</td>
                <td><span class="label">Edad:</span> {{$note->appointment->user->age}}</td>
            </tr>
        </table>

        <div class="space-16">
            <div class="block-title">Productos:</div>
            <div class="product-row">{{$note->treatment}}</div>
        </div>

        <div class="text-block">
            <div class="text-title">
                SINTOMAS:
            </div>
            <div class="text-content">{{$note->symptoms}}</div>
        </div>

        <div class="text-block">
            <div class="text-title">DIAGNOSTICO:</div>
            <div class="text-content">{{$note->diagnosis}}</div>
        </div>

        <div class="text-block">
            <div class="text-title">NOTAS:</div>
            <div class="text-content">{{$note->notes}}</div>
        </div>

        <div class="signature">
            <div class="signature-line"></div>
            <div class="signature-label">FIRMA</div>
        </div>

        <table class="footer-table">
            <tr>
                <td class="footer-left">INMAX</td>
                <td class="footer-right">
                    TEL: <br>
                    DIR: <br>
                    EMAIL: <br>
                    WEB:
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
