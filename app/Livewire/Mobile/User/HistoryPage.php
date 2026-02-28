<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Mobile\User\ScheduleCancellationPage;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class HistoryPage extends Component
{
    public $upcomingAppointments = null;
    public $pastAppointments = null;
    public $appointmentId = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.history-page');
    }

    public function mount()
    {
        $this->loadAppointments();
    }

    public function loadAppointments()
    {
        $this->upcomingAppointments = Appointment::where([
                ['status', 'Booked'],
                ['user_id', Auth::user()->id],
            ])
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $this->pastAppointments = Appointment::where([
                ['status', '!=', 'Booked'],
                ['user_id', Auth::user()->id],
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function cancel($id)
    {
        $this->appointmentId = $id;

        //open modal
        $this->dispatch('open-cancel-modal');
    }

    public function open($id)
    {
        $this->appointmentId = $id;

        //open modal
    }

    public function confirmCancel()
    {
        $appointment = Appointment::findOrFail($this->appointmentId);

        $appointment->update([
            'status' => 'Cancelled',
        ]);

        //close modal
        $this->dispatch('close-cancel-modal');

        session()->flash('appointment_cancellation_id', $appointment->id);

        return $this->redirect(ScheduleCancellationPage::class);
    }

    public function notes($id)
    {
        return $this->redirectRoute('user.notes', ['appointment' => $id]);
    }

    public function print($id)
    {
        $note = Appointment::findOrFail($id)->note;

        $pdf = Pdf::loadView('pdf.prescription', [
            'note' => $note,
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "prescription-{$note->id}.pdf"
        );
    }
}
