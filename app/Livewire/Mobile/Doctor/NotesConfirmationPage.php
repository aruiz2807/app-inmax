<?php

namespace App\Livewire\Mobile\Doctor;

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
        $noteId = session('appointment_note_id');

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
}
