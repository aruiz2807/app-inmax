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

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-confirmation-page');
    }

    public function mount()
    {
        $noteId = 2;//session('appointment_note_id');

        abort_unless($noteId, 404);

        $this->note = AppointmentNote::where('id', $noteId)->firstOrFail();
    }

    public function print()
    {
        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $this->note,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$this->note->id}.pdf"
        );
    }

    public function getSubtotalProperty()
    {
        return number_format($this->note->appointment->subtotal, 2);
    }

    public function getDiscountProperty()
    {
        return number_format($this->note->appointment->subtotal * ($this->note->appointment->doctor->discount / 100), 2);
    }

    public function getPaymentProperty()
    {
        return number_format($this->note->appointment->subtotal - floatval(str_replace(',', '', $this->getDiscountProperty())), 2);
    }

    public function getTotalProperty()
    {
        return number_format(floatval(str_replace(',', '', $this->getSubtotalProperty())) - floatval(str_replace(',', '', $this->getDiscountProperty())) - floatval(str_replace(',', '', floatval(str_replace(',', '', $this->getCommissionProperty())))), 2);
    }

    public function getCommissionProperty()
    {
        return number_format($this->note->appointment->subtotal * ($this->note->appointment->doctor->commission / 100), 2);
    }
}
