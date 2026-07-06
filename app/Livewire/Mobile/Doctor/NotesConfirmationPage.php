<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;

class NotesConfirmationPage extends Component
{
    public $note;
    public bool $hasReceptionistAssigned = false;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-confirmation-page');
    }

    public function mount()
    {
        $noteId = session('appointment_note_id');

        if (!$noteId) {
            return redirect()->route('doctor.home');
        }

        $this->note = AppointmentNote::with(['appointment.prescriptions.medication', 'appointment.doctor.user', 'appointment.doctor.specialty', 'appointment.user'])
            ->where('id', $noteId)
            ->firstOrFail();

        $this->hasReceptionistAssigned = $this->note->appointment->doctor
            ?->staff()
            ->where('profile', 'Receptionist')
            ->exists() ?? false;
    }

    public function schedule()
    {
        return $this->redirectRoute('doctor.schedule', [
            'appointment' => $this->note->appointment->id,
        ]);
    }

    public function print()
    {
        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $this->note,
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$this->note->id}.pdf"
        );
    }

    public function print_ticket()
    {
        $pdf = Pdf::loadView('pdf.ticket', [
            'note' => $this->note,
            'subtotal' => $this->getSubtotalProperty(),
            'coupon_discount' => $this->getCouponDiscountProperty(),
            'payment' => $this->getPaymentProperty(),
            'commision' => $this->getCommissionProperty(),
            'total' => $this->getTotalProperty(),
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
        ])->setPaper([0, 0, 226, 567], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "ticket-{$this->note->id}.pdf"
        );
    }

    public function getSubtotalProperty()
    {
        return number_format($this->note->appointment->subtotal, 2);
    }

    public function getCouponDiscountProperty()
    {
        return number_format($this->note->appointment->coupon_discount, 2);
    }

    public function getDiscountProperty()
    {
        return number_format($this->note->appointment->subtotal * ($this->note->appointment->doctor->discount / 100), 2);
    }

    public function getPaymentProperty()
    {
        return number_format($this->note->appointment->user_payment, 2);
    }

    public function getTotalProperty()
    {
        return number_format($this->note->appointment->total, 2);
    }

    public function getCommissionProperty()
    {
        return number_format($this->note->appointment->commission, 2);
    }
}
