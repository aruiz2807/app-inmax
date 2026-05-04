<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DRScheduleConfirmationPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.schedule-confirmation-page');
    }

    public function mount()
    {
        $appointmentId = session('appointment_confirmation_id');

        abort_unless($appointmentId, 404);

        $this->appointment = Appointment::where('id', $appointmentId)->firstOrFail();

        session()->forget('appointment_confirmation_id');
    }

    public function order($id)
    {
        $appointment = Appointment::findOrFail($id);

        $pdf = Pdf::loadView('pdf.order', [
            'appointment' => $appointment,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order-{$appointment->id}.pdf"
        );
    }
}
