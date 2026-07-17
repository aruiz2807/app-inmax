<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReceptionistTicketController extends Controller
{
    public function __invoke(Appointment $appointment, $type)
    {
        if (! Auth::user()->staffDoctors()->whereKey($appointment->doctor_id)->exists()) {
            abort(403);
        }

        $appointment->load([
            'note',
            'services.service:id,name',
            'doctor.user:id,name',
            'user:id,name',
        ]);

        $note = $appointment->note;

        if (! $note) {
            abort(404, 'No existe nota para esta consulta.');
        }

        $pdf = Pdf::loadView('pdf.ticket', [
            'note' => $note,
            'subtotal' => number_format((float) $appointment->subtotal, 2),
            'coupon_discount' => number_format((float) $appointment->coupon_discount, 2),
            'payment' => number_format((float) $appointment->user_payment, 2),
            'commision' => number_format((float) $appointment->commission, 2),
            'total' => number_format((float) $appointment->total, 2),
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com',
            'type' => $type,
        ])->setPaper([0, 0, 226, 567], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "ticket-{$note->id}-{$type}.pdf"
        );
    }
}
