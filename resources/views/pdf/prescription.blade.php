<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Receta {{ $note->folio ?? $note->id }}</title>
<style>
  /* ─────────────────────────────────────────────────────────────
     OPTIMIZED FOR DOMPDF
     ───────────────────────────────────────────────────────────── */
  @page { 
    margin: 14mm 12mm; 
    size: letter portrait;
  }

  * { box-sizing: border-box; }
  
  body {
    margin: 0; padding: 0;
    font-family: 'DejaVu Sans', sans-serif;
    color: #0F1F38;
    font-size: 10pt;
    line-height: 1.4;
    background: #FFFFFF;
  }

  /* Force page breaks to avoid cutting medication cards in half */
  .med-container { page-break-inside: avoid; }

  table.layout { width: 100%; border-collapse: collapse; table-layout: fixed; }
  table.layout td { vertical-align: top; padding: 0; }

  .sheet {
    border: 1px solid #E5E9F2;
    border-radius: 12px;
    background: #FFFFFF;
    min-height: auto;
  }

  /* === HEADER === */
  .header {
    background-color: #1B365D;
    color: #FFFFFF;
    padding: 16px 22px;
    border-bottom: 3px solid #4FD1C5;
  }
  .brand-name { font-size: 20pt; font-weight: bold; color: #FFFFFF; }
  .folio-label { font-size: 7pt; text-transform: uppercase; color: #4FD1C5; font-weight: bold; }
  .folio-num { font-size: 16pt; font-weight: bold; color: #FFFFFF; }

  /* === DOCTOR + PATIENT CARD === */
  .meta-card {
    margin: 15px 22px 0 22px;
    background: #FFFFFF;
    border: 1px solid #E5E9F2;
    border-radius: 10px;
    padding: 12px 15px;
    /* dompdf handles box-shadow poorly, using border instead */
  }
  .meta-label { color: #6B7689; font-size: 7pt; text-transform: uppercase; font-weight: bold; }
  .meta-name  { color: #1B365D; font-size: 11pt; font-weight: bold; }
  .meta-sub   { color: #3D4A63; font-size: 8.5pt; }
  .meta-pill  {
    display: inline-block;
    padding: 2px 6px;
    background: #F5F7FB;
    border-radius: 4px;
    font-size: 7.5pt;
    color: #3D4A63;
  }

  /* === SECTIONS === */
  .section { padding: 0 22px; margin-top: 15px; }
  .eyebrow { font-size: 7.5pt; text-transform: uppercase; color: #6B7689; font-weight: bold; border-bottom: 1px solid #E5E9F2; padding-bottom: 3px; margin-bottom: 8px; }

  /* === MEDICATION CARDS === */
  .med-table {
    width: 100%;
    margin-bottom: 8px;
    border: 1px solid #E5E9F2;
    border-radius: 10px;
  }
  .med-row td { padding: 10px; }
  .med-idx {
    width: 24px;
    height: 24px;
    line-height: 24px; 
    background: #1B365D;
    color: #FFFFFF;
    border-radius: 5px;
    margin: 0 auto; /* Keeps it centered horizontally within the TD */
  }
  
  .med-title { color: #1B365D; font-weight: bold; font-size: 10.5pt; }
  .med-details { color: #6B7689; font-size: 8.5pt; margin-top: 2px; }
  .v-teal { color: #2C9A95; font-weight: bold; }

  .route-tag {
    background: #E6F8F6;
    color: #2C9A95;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 7pt;
    font-weight: bold;
    text-transform: uppercase;
  }
  
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

  /* === FOOTER === */
  .footer {
    osition: static;
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
            <span class="folio-label">Receta expedida para usuarios de la plataforma INMAX</span>
          </div>
        </td>
        <td style="text-align:right;">
          <div class="folio-label">Folio de Receta</div>
          <div class="folio-num">#{{ str_pad($note->folio ?? $note->id, 5, '0', STR_PAD_LEFT) }}</div>
          <div class="folio-label">Fecha: {{ $note->created_at->format('d/m/Y') }}</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="meta-card">
    <table class="layout">
      <tr>
        <td style="width: 50%; border-right: 1px solid #E5E9F2; padding-right: 15px;">
          <div class="meta-label">Médico</div>
          <div class="meta-name">{{ $note->appointment->doctor->user->name }}</div>
          <div class="meta-sub">{{ $note->appointment->doctor->university }}</div>
          <div class="meta-sub">Cedula: {{ $note->appointment->doctor->license }}</div>
        </td>
        <td style="width: 50%; padding-left: 15px;">
          <div class="meta-label">Paciente</div>
          <div class="meta-name">{{ $note->appointment->user->name }}</div>
          <div class="meta-sub">{{ $note->appointment->user->age }} años</div>
        </td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="eyebrow">Diagnóstico</div>
    <div style="font-weight: bold; color: #1B365D;">
      {{ $note->diagnosis }} 
      @if($note->diagnosis_code) <small style="color: #6B7689; font-weight: normal;">({{ $note->diagnosis_code }})</small> @endif
    </div>
  </div>

  <div class="section">
    <div class="eyebrow">Tratamiento</div>
    @foreach ($note->appointment->prescriptions as $i => $med)
      <div class="med-container">
        <table class="med-table">
          <tr class="med-row">            
            <td>
              <div class="med-title">{{ $med->medication->name }} ({{ $med->medication->active_substance }})</div>
              <div class="med-details">
                Tomar <span class="v-teal">{{ $med->quantity }} {{ $med->dose }}</span> cada <span class="v-teal"> {{ $med->frequency }}</span> durante <span class="v-teal">{{ $med->duration }}</span>.
              </div>
            </td>
            {{--<td style="width: 100px; text-align: right; vertical-align: middle;">
              <span class="route-tag">{{ $med->route ?? 'Oral' }}</span>
            </td>--}}
          </tr>
        </table>
      </div>
    @endforeach
  </div>

  @if($note->notes)
  <div class="section">
    <div class="eyebrow">Indicaciones Adicionales</div>
    <div style="font-size: 9pt; color: #3D4A63;">{!! nl2br(e($note->notes)) !!}</div>
  </div>
  @endif

  <div class="section signature-block">
    <div class="signature-line"></div>
    <div class="signature-name">
      {{ $note->appointment->doctor->user->name }}
    </div>
    <div class="signature-meta">
      Cédula profesional: {{ $note->appointment->doctor->license }}
    </div>
  </div>
  
  <div class="footer">
    @php
      $appt        = $note->appointment;
      $officeAddr  = $appt->office?->address;
      $officePhone = $appt->office?->phone_number;
      $footerAddr  = $officeAddr ?: $appt->doctor?->address;
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
          contacto@inmax-sure.mx
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