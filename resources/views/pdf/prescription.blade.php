<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta digital</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .title { font-size: 22px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="title">Receta {{ $note->id }}</div>
    <p>Paciente: {{ $note->appointment->user->name }}</p>
    <p>Medico: {{ $note->appointment->doctor->user->name }}</p>
    <p>Especialidad: {{ $note->appointment->doctor->specialty->name }}</p>
    <p>Cedula: {{ $note->appointment->doctor->license }}</p>
    <p>Universidad: {{ $note->appointment->doctor->university }}</p>
    <p>Consultorio: {{ $note->appointment->doctor->address }}</p>
    <p>Diagnostico: {{ $note->diagnosis }}</p>
    <p>Tratamiento: {{ $note->treatment }}</p>
</body>
</html>
