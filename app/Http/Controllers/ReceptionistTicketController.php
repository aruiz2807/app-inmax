<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReceptionistTicketController extends Controller
{
    public function __invoke(Appointment $appointment, $type)
    {
        abort_unless(in_array($type, ['partner', 'member'], true), 404);
        abort_unless(! is_null($appointment->subtotal), 403);

        $user = Auth::user();
        $doctor = $user->doctor;
        $isAssignedDoctor = $doctor && (
            (int) $appointment->doctor_id === (int) $doctor->id
            || $doctor->offices()->whereKey($appointment->office_id)->exists()
        );
        $isAssignedStaff = $appointment->doctor_id
            && $user->staffDoctors()->whereKey($appointment->doctor_id)->exists();

        if (! $isAssignedDoctor && ! $isAssignedStaff) {
            abort(403);
        }

        $appointment->load([
            'note',
            'services.service:id,name',
            'doctor.user:id,name',
            'user:id,name',
        ]);

        // Citas liquidadas antes de tener nota (ej. cerradas desde recepcion) la generan aqui
        $note = $appointment->note ?: AppointmentNote::firstOrCreate(['appointment_id' => $appointment->id]);

        $pdf = Pdf::loadView('pdf.ticket', [
            'note' => $note,
            'subtotal' => $this->formatServiceAmount($appointment, 'subtotal'),
            'coupon_discount' => $this->formatServiceAmount($appointment, 'coupon_discount'),
            'payment' => $this->formatServiceAmount($appointment, 'user_payment'),
            'commision' => $this->formatServiceAmount($appointment, 'commission'),
            'total' => $this->formatServiceAmount($appointment, 'total'),
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.mx',
            'type' => $type,
        ])->setPaper([0, 0, 226, 567], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "ticket-{$note->id}-{$type}.pdf"
        );
    }

    private function formatServiceAmount(Appointment $appointment, string $column): string
    {
        $completedServices = $appointment->services->where('status', 'Completed');

        $hasServiceAmounts = $completedServices->contains(fn ($service) => $service->{$column} !== null);
        $amount = $hasServiceAmounts
            ? $completedServices->sum($column)
            : $appointment->{$column};

        return number_format((float) $amount, 2);
    }
}
