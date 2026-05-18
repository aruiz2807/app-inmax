<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Orden {{ $appointment->id }}</title>
<style>
    @page {
        margin: 14mm 12mm;
        size: letter portrait;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        padding: 0;
        font-family: 'DejaVu Sans', sans-serif;
        color: #0F1F38;
        font-size: 10pt;
        line-height: 1.4;
        background: #FFFFFF;
    }

    .item-container { page-break-inside: avoid; }

    table.layout { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.layout td { vertical-align: top; padding: 0; }

    .sheet {
        border: 1px solid #E5E9F2;
        border-radius: 12px;
        background: #FFFFFF;
        min-height: auto;
    }

    .header {
        background-color: #1B365D;
        color: #FFFFFF;
        padding: 16px 22px;
        border-bottom: 3px solid #4FD1C5;
    }
    .brand-name { font-size: 20pt; font-weight: bold; color: #FFFFFF; }
    .folio-label { font-size: 7pt; text-transform: uppercase; color: #4FD1C5; font-weight: bold; }
    .folio-num { font-size: 16pt; font-weight: bold; color: #FFFFFF; }

    .meta-card {
        margin: 15px 22px 0 22px;
        background: #FFFFFF;
        border: 1px solid #E5E9F2;
        border-radius: 10px;
        padding: 12px 15px;
    }
    .meta-label { color: #6B7689; font-size: 7pt; text-transform: uppercase; font-weight: bold; }
    .meta-name  { color: #1B365D; font-size: 11pt; font-weight: bold; }
    .meta-sub   { color: #3D4A63; font-size: 8.5pt; }

    .section { padding: 0 22px; margin-top: 15px; }
    .eyebrow {
        font-size: 7.5pt;
        text-transform: uppercase;
        color: #6B7689;
        font-weight: bold;
        border-bottom: 1px solid #E5E9F2;
        padding-bottom: 3px;
        margin-bottom: 8px;
    }

    .target-box {
        border: 1px solid #E5E9F2;
        border-radius: 10px;
        padding: 10px;
        color: #1B365D;
        font-size: 10.5pt;
        font-weight: bold;
    }

    .item-table {
        width: 100%;
        margin-bottom: 8px;
        border: 1px solid #E5E9F2;
        border-radius: 10px;
    }
    .item-row td { padding: 10px; }
    .item-title { color: #1B365D; font-weight: bold; font-size: 10.5pt; }

    .signature-block {
        margin: 40px 22px 0 22px;
        text-align: center;
        page-break-inside: avoid;
    }

    .signature-line {
        width: 260px;
        margin: 0 auto;
        border-top: 1px solid #0F1F38;
        height: 1px;
    }

    .signature-name {
        margin-top: 6px;
        font-size: 9pt;
        font-weight: bold;
        color: #1B365D;
    }

    .signature-meta {
        font-size: 8pt;
        color: #6B7689;
    }

    .footer {
        margin-top: 20px;
        background: #F8FAFC;
        border-top: 1px solid #E5E9F2;
        padding: 15px 22px;
        color: #6B7689;
        font-size: 7.5pt;
    }

    .legal-note {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #E5E9F2;
        font-size: 6.8pt;
        line-height: 1.35;
        color: #7D889A;
        text-align: justify;
    }
</style>
</head>
<body>
<div class="sheet">
    <div class="header">
        <table class="layout">
            <tr>
                <td>
                    <div>
                        <img class="logo" src="{{ public_path('/img/logo.png') }}" alt="Logo" style="height: 50px; top: 15px; position: relative;">
                        <span class="brand-name">INMAX</span>
                    </div>
                    <div>
                        <span class="folio-label">Orden expedida para usuarios de la plataforma INMAX</span>
                    </div>
                </td>
                <td style="text-align:right;">
                    <div class="folio-label">Folio de Orden</div>
                    <div class="folio-num">#{{ str_pad($appointment->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <div class="folio-label">Fecha: {{ $appointment->created_at->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-card">
        <table class="layout">
            <tr>
                <td style="width: 50%; border-right: 1px solid #E5E9F2; padding-right: 15px;">
                    <div class="meta-label">Solicitante</div>
                    <div class="meta-name">{{ $appointment->requester->name }}</div>
                    <div class="meta-sub">{{ $appointment->requester->doctor?->specialty->name }}</div>
                    <div class="meta-sub">Cédula: {{ $appointment->requester->doctor?->license }}</div>
                    <div class="meta-sub">{{ $appointment->requester->doctor?->university }}</div>
                    <div class="meta-sub">{{ $appointment->requester->doctor?->address }}</div>
                    <div class="meta-sub">Tel. {{ $appointment->requester->doctor?->user->phone }}</div>
                </td>
                <td style="width: 50%; padding-left: 15px;">
                    <div class="meta-label">Paciente</div>
                    <div class="meta-name">{{ $appointment->user->name }}</div>
                    <div class="meta-sub">Edad: {{ $appointment->user->age }} años</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="eyebrow">Para</div>
        <div class="target-box">{{ $appointment->doctor->user->name }}</div>
    </div>

    <div class="section">
        <div class="eyebrow">Servicios</div>
        @foreach ($appointment->services as $service)
            <div class="item-container">
                <table class="item-table">
                    <tr class="item-row">
                        <td>
                            <div class="item-title">{!! nl2br(e($service->service->name)) !!}</div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>

    <div class="section signature-block">
        <div class="signature-line"></div>
        <div class="signature-name">{{ $appointment->requester->name }}</div>
        <div class="signature-meta">Cédula profesional: {{ $appointment->requester->doctor?->license }}</div>
    </div>

    <div class="footer">
        @php
          $officeAddr  = $appointment->office?->address;
          $officePhone = $appointment->office?->phone_number;
          $footerAddr  = $officeAddr ?: $appointment->doctor?->address;
          $footerPhone = $officePhone ?? '';
        @endphp
        <table class="layout">
            <tr>
                <td>
                    <strong>INMAX</strong><br>
                    @if($footerAddr)
                        {{ $footerAddr }}
                    @endif
                </td>
                <td style="text-align: right;">
                    <strong>Contacto</strong><br>
                    @if($footerPhone)
                        {{ $footerPhone }}<br>
                    @endif
                    contacto@inmax-sure.com
                </td>
            </tr>
        </table>
        <div class="legal-note">
            El contenido de este documento es responsabilidad integral del médico o profesional de la salud que expide la presente orden/receta. INMAX no interviene en la elaboración de este documento, limitándose a facilitar la plataforma tecnológica para su generación digital. Los elementos visuales y logotipos de INMAX son de carácter informativo y publicitario, deslindando a la plataforma de cualquier responsabilidad derivada del acto clínico, diagnóstico o tratamiento prescrito.
        </div>
    </div>
</div>
</body>
</html>
